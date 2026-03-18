<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;
use Carbon\Carbon;

class MetrcPushController extends Controller
{
    /**
     * ⚠️ METRC vendor "username" (NOT the org/user API key).
     * Do not change auth method: we continue to use vendor username + org admin apiKey.
     */
    private string $vendorUsername = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    // =============================================================================
    // Public endpoints — push, batch, window, link-by-time, repush (void→recreate)
    // =============================================================================

    /** POST /metrc/push/sale { sale_id, corrected?:bool, hard?:bool, organization_id?:int } */
    public function pushSale(Request $req)
    {
        $saleId = (int) $req->input('sale_id');
        if (!$saleId) return response()->json(['error' => 'sale_id is required'], 422);

        $opts = [
            'corrected'       => (bool) $req->boolean('corrected'),
            'hard'            => (bool) $req->boolean('hard', true),
            'organization_id' => $req->input('organization_id'),
        ];

        try {
            $result = $this->performMetrcPush($saleId, $opts);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'sale_id' => $saleId], 500);
        }
    }

    /** POST /metrc/push/batch { sale_ids:[...], corrected?:bool, hard?:bool, organization_id?:int } */
    public function pushBatch(Request $req)
    {
        $ids = $req->input('sale_ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'sale_ids[] is required'], 422);
        }

        $opts = [
            'corrected'       => (bool) $req->boolean('corrected'),
            'hard'            => (bool) $req->boolean('hard', true),
            'organization_id' => $req->input('organization_id'),
        ];

        $out = ['pushed' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($ids as $sid) {
            $sid = (int) $sid;
            try {
                $res = $this->performMetrcPush($sid, $opts);
                if (!empty($res['metrc_payload']['Transactions'])) $out['pushed']++; else $out['skipped']++;
            } catch (\Throwable $e) {
                $out['skipped']++;
                $out['errors'][] = ['sale_id' => $sid, 'error' => $e->getMessage()];
            }
        }

        return response()->json($out);
    }

    /** POST /metrc/push/corrected { sale_id, organization_id?:int } */
    public function pushCorrected(Request $req)
    {
        $req->merge(['corrected' => true, 'hard' => true]);
        return $this->pushSale($req);
    }

    /** POST /metrc/push/corrected-batch { sale_ids:[...], organization_id?:int } */
    public function repushCorrectedBatch(Request $req)
    {
        $req->merge(['corrected' => true, 'hard' => true]);
        return $this->pushBatch($req);
    }

    /**
     * POST /metrc/push-and-sync { start:'YYYY-MM-DD', end?:'YYYY-MM-DD', organization_id?:int }
     * Push eligible local sales in the date-of-sale window (store-local), not "date pushed".
     */
    public function pushAndSync(Request $req)
    {
        $orgId = $this->resolveOrgIdFromRequest($req);
        $start = $req->input('start');
        $end   = $req->input('end') ?: $start;

        if (!$start) return response()->json(['error' => 'start is required'], 422);

        $storeTz = $this->storeTz();
        $startTs = Carbon::parse($start, $storeTz)->startOfDay()->timezone('UTC');
        $endTs   = Carbon::parse($end,   $storeTz)->endOfDay()->timezone('UTC');

        // Select by DATE OF SALE (created_at), active only
        $salesQ = DB::table('sales')
            ->select('id')
            ->whereBetween('created_at', [$startTs, $endTs]);

        if (Schema::hasColumn('sales','status')) {
            $salesQ->where('status', 1);
        }

        $saleIds = $salesQ->pluck('id')->all();

        $considered = count($saleIds);
        $pushed = 0; $skipped = 0; $errors = [];

        foreach ($saleIds as $sid) {
            try {
                $res = $this->performMetrcPush((int)$sid, [
                    'corrected'       => false,
                    'hard'            => true,
                    'organization_id' => $orgId ?: null,
                ]);
                if (!empty($res['metrc_payload']['Transactions'])) $pushed++; else $skipped++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = ['sale_id' => $sid, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'ok'         => true,
            'considered' => $considered,
            'pushed'     => $pushed,
            'skipped'    => $skipped,
            'errors'     => $errors,
            'window'     => ['start'=>$start, 'end'=>$end, 'store_tz'=>$storeTz],
        ]);
    }

    /**
     * POST /metrc/link-by-time { sale_id, tolerance_minutes?:int }
     * Find the remote METRC receipt near this sale’s local time, prefer label overlap,
     * then link it locally (metrc_receipts + optional sales.metrc_receipt_id).
     */
    public function linkByTime(Request $req)
    {
        $saleId = (int) $req->input('sale_id');
        $tolMin = (int) ($req->input('tolerance_minutes') ?: 15);
        if (!$saleId) return response()->json(['error' => 'sale_id is required'], 422);

        $sale = $this->loadSaleRow($saleId);
        if (!$sale) return response()->json(['error' => "Sale #$saleId not found"], 404);

        $org        = $this->resolveOrgFromContext($req, $sale);
        $keys       = $this->resolveOrgAuth($org, $sale);
        if (!$keys['licenseNumber'] || !$keys['vendorKey']) {
            return response()->json(['error' => 'Missing org license or API key'], 422);
        }

        $storeTz    = $this->storeTz();
        $localWhen  = $this->saleLocalMoment((object)$sale, $storeTz);
        $items      = $this->loadSaleItemsRich($saleId);
        $localLabs  = $this->labelsFromItems($items);

        $remote = $this->fetchReceiptsNear($this->baseUrlForOrgObj($org), $keys['licenseNumber'], $keys['vendorKey'], $localWhen, $tolMin);
        if (empty($remote)) {
            return response()->json(['ok'=>false, 'linked'=>false, 'reason'=>'No remote receipts returned in window.']);
        }

        $best = $this->pickBestReceiptMatch($remote, $localWhen, $localLabs, $storeTz);
        if (!$best) {
            return response()->json(['ok'=>false, 'linked'=>false, 'reason'=>'No suitable match found.']);
        }

        $this->upsertLocalReceipt($sale, $best, null, $localWhen); // totals null (link only)
        $this->maybeWriteSaleLink($sale, $best);

        return response()->json(['ok'=>true, 'linked'=>true, 'matched'=>$best]);
    }

    /**
     * POST /metrc/repush/hard { sale_id, tolerance_minutes?:int }
     * 1) Find existing remote receipt (local or by time), VOID it.
     * 2) Re-create by calling v2 push with force_post (no PUT).
     */
    public function repushHard(Request $req)
    {
        $saleId = (int) $req->input('sale_id');
        $tolMin = (int) ($req->input('tolerance_minutes') ?: 15);
        if (!$saleId) return response()->json(['error' => 'sale_id is required'], 422);

        $sale = $this->loadSaleRow($saleId);
        if (!$sale) return response()->json(['error' => "Sale #$saleId not found"], 404);

        $org        = $this->resolveOrgFromContext($req, $sale);
        $keys       = $this->resolveOrgAuth($org, $sale);
        if (!$keys['licenseNumber'] || !$keys['vendorKey']) {
            return response()->json(['error' => 'Missing org license or API key'], 422);
        }

        $baseUrl   = rtrim($this->baseUrlForOrgObj($org), '/');
        $storeTz   = $this->storeTz();
        $localWhen = $this->saleLocalMoment((object)$sale, $storeTz);

        // Attempt to locate an existing remote receipt (by local table first; else by window search)
        $localRow = $this->findLocalMetrcReceiptForSale($sale, $this->bestExternalNumber($sale), $localWhen->format('Y-m-d H:i:s'));
        $metrcId  = $localRow && !empty($localRow->metrc_id) ? (int)$localRow->metrc_id : null;

        $voidAttempted = false;
        $voidOk = false;

        if ($metrcId) {
            $voidAttempted = true;
            $voidOk = $this->voidReceipt($baseUrl, $keys['licenseNumber'], $keys['vendorKey'], $metrcId);
        } else {
            $remote = $this->fetchReceiptsNear($baseUrl, $keys['licenseNumber'], $keys['vendorKey'], $localWhen, $tolMin);
            $best   = $this->pickBestReceiptMatch($remote, $localWhen, $this->labelsFromItems($this->loadSaleItemsRich($saleId)), $storeTz);
            if ($best && isset($best['Id'])) {
                $voidAttempted = true;
                $voidOk = $this->voidReceipt($baseUrl, $keys['licenseNumber'], $keys['vendorKey'], (int)$best['Id']);
            }
        }

        // Recreate (force POST, skip PUT)
        $res = $this->performMetrcPush($saleId, [
            'force_post'       => true,
            'corrected'        => false,
            'organization_id'  => $org->id ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'void_attempted' => $voidAttempted,
            'void_ok' => $voidOk,
            'recreated' => true,
            'result' => $res,
        ]);
    }

    // =============================================================================
    // NEW: Relink all sales in a window by exact timestamp against ACTIVE + INACTIVE receipts
    // =============================================================================

    /**
     * POST /metrc/relink/timestamp-window-inline
     * Body: {
     *   start_date:'YYYY-MM-DD', end_date:'YYYY-MM-DD',
     *   tolerance_seconds?:0, pre_abs_tolerance?:0.05, pre_pct_tolerance?:0.02,
     *   unlink_first?:true, steal?:true, hard?:true, organization_id?:int
     * }
     * NOTE: pulls both ACTIVE and INACTIVE receipts for the date-of-sale window (store-local).
     */
    public function relinkTimestampWindowInline(Request $req)
    {
        $start = $req->input('start_date');
        $end   = $req->input('end_date') ?: $start;
        if (!$start) return response()->json(['error' => 'start_date is required'], 422);

        $tolSec   = (int)($req->input('tolerance_seconds', 0));
        $absTol   = (float)($req->input('pre_abs_tolerance', 0.05));
        $pctTol   = (float)($req->input('pre_pct_tolerance', 0.02));
        $unlink   = (bool)$req->boolean('unlink_first', true);
        $steal    = (bool)$req->boolean('steal', true);

        // Resolve org + auth
        $org  = $this->resolveOrgFromContext($req, null);
        $keys = $this->resolveOrgAuth($org, null);
        if (!$keys['licenseNumber'] || !$keys['vendorKey']) {
            return response()->json(['error' => 'Missing org license or API key'], 422);
        }
        $baseUrl = rtrim($this->baseUrlForOrgObj($org), '/');

        // Window in store-local (for both DB and METRC v2 list)
        $storeTz   = $this->storeTz();
        $startLoc  = Carbon::parse($start, $storeTz)->startOfDay();
        $endLoc    = Carbon::parse($end,   $storeTz)->endOfDay();

        // Fetch ACTIVE + INACTIVE receipts for the local window (use POST fallback when GET returns 405)
        $client = $this->httpFor($keys['vendorKey']);
        $active   = $this->listReceiptsV2Window($client, $baseUrl, $keys['licenseNumber'], $startLoc, $endLoc, 'active');
        $inactive = $this->listReceiptsV2Window($client, $baseUrl, $keys['licenseNumber'], $startLoc, $endLoc, 'inactive');
        $all      = array_merge($active, $inactive);

        // Build map by exact local timestamp string "Y-m-d H:i:s"
        $rcptByLocalYmdHis = [];
        foreach ($all as $r) {
            $salesTime = $this->parseMetrcLocalTime($r['SalesDateTime'] ?? '', $storeTz);
            if (!$salesTime) continue;
            $key = $salesTime->format('Y-m-d H:i:s');
            // prefer the one with more transactions if duplicates
            if (!isset($rcptByLocalYmdHis[$key]) || count($r['Transactions'] ?? []) > count($rcptByLocalYmdHis[$key]['Transactions'] ?? [])) {
                $rcptByLocalYmdHis[$key] = $r;
            }
        }

        // Sales in window (DATE OF SALE = created_at in store-local converted to UTC for query)
        $startUtc = $startLoc->copy()->timezone('UTC');
        $endUtc   = $endLoc->copy()->timezone('UTC');

        $salesQ = DB::table('sales')
            ->select('id','user_id','created_at','subtotal','status')
            ->whereBetween('created_at', [$startUtc, $endUtc]);

        if (Schema::hasColumn('sales','status')) {
            $salesQ->where('status', 1); // we only consider completed sales
        }

        $sales = $salesQ->get();

        $considered = 0;
        $linked = 0;
        $unlinkedFirst = 0;
        $skipped = 0;
        $pairs = [];

        foreach ($sales as $sale) {
            $considered++;
            $saleLocal = $this->saleLocalMoment((object)$sale, $storeTz);
            $key       = $saleLocal->format('Y-m-d H:i:s');
            $rcpt      = $rcptByLocalYmdHis[$key] ?? null;

            if (!$rcpt) { $skipped++; continue; }

            // Amount guard (pre-tax compare)
            $remotePre = $this->sumTransactionsPretax($rcpt['Transactions'] ?? []);
            $localPre  = (float)($sale->subtotal ?? 0.0);

            $okAmt = false;
            $diff  = abs($localPre - $remotePre);
            if ($diff <= $absTol) $okAmt = true;
            else {
                $den = max(0.01, $remotePre ?: $localPre);
                $pct = $diff / $den;
                if ($pct <= $pctTol) $okAmt = true;
            }
            if (!$okAmt) { $skipped++; continue; }

            // unlink first if requested
            if ($unlink && Schema::hasColumn('sales','metrc_receipt_id')) {
                $cur = DB::table('sales')->where('id',$sale->id)->value('metrc_receipt_id');
                if ($cur) {
                    DB::table('sales')->where('id',$sale->id)->update(['metrc_receipt_id'=>null]);
                    $unlinkedFirst++;
                }
            }

            // Upsert local receipt + link
            $this->upsertLocalReceipt($sale, [
                'Id'            => $rcpt['Id'] ?? null,
                'ReceiptNumber' => $rcpt['ReceiptNumber'] ?? null
            ], $remotePre, $saleLocal);

            $this->maybeWriteSaleLink($sale, ['Id' => $rcpt['Id'] ?? null]);

            $linked++;
            $pairs[] = [
                'sale_id' => (int)$sale->id,
                'metrc_id'=> (int)($rcpt['Id'] ?? 0),
                'sales_date_time' => $key,
                'remote_pre' => round($remotePre,2),
                'local_pre'  => round($localPre,2),
            ];
        }

        return response()->json([
            'ok'           => true,
            'window'       => ['start'=>$start, 'end'=>$end, 'store_tz'=>$storeTz],
            'considered'   => $considered,
            'linked'       => $linked,
            'unlinked_first'=> $unlinkedFirst,
            'skipped'      => $skipped,
            'pairs'        => $pairs,
        ]);
    }

    // =============================================================================
    // SYNC (inline): fetch and cache receipts in window (ACTIVE then INACTIVE)
    // =============================================================================

    /** POST /metrc/sync-inline/init */
    public function syncInlineInit(Request $req)
    {
        $org  = $this->resolveOrgFromContext($req, null);
        $keys = $this->resolveOrgAuth($org, null);
        if (!$keys['licenseNumber'] || !$keys['vendorKey']) {
            return response()->json(['error' => 'Missing org license or API key'], 422);
        }

        $storeTz = $this->storeTz();
        $start   = $req->input('start');
        $end     = $req->input('end') ?: $start;
        if (!$start) return response()->json(['error'=>'start is required'], 422);

        $startLocal = Carbon::parse($start, $storeTz)->startOfDay();
        $endLocal   = Carbon::parse($end,   $storeTz)->endOfDay();

        return response()->json([
            'ok'     => true,
            'mode'   => 'sales',
            'license'=> $keys['licenseNumber'],
            'paging' => ['page_size' => (int)$req->input('page_size', 10)],
            'sales'  => [
                'start_local' => $startLocal->format('Y-m-d H:i:s'),
                'end_local'   => $endLocal->format('Y-m-d H:i:s'),
                'start_utc'   => $startLocal->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                'end_utc'     => $endLocal->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z'),
            ],
        ]);
    }

    /** POST /metrc/sync-inline/chunk */
    public function syncInlineChunk(Request $req)
    {
        $kind     = $req->input('kind', 'active');   // 'active' → then 'inactive'
        $page     = max(1, (int)$req->input('page', 1));
        $pageSize = max(1, (int)$req->input('page_size', 10));

        $org  = $this->resolveOrgFromContext($req, null);
        $keys = $this->resolveOrgAuth($org, null);
        if (!$keys['licenseNumber'] || !$keys['vendorKey']) {
            return response()->json(['error' => 'Missing org license or API key'], 422);
        }

        $storeTz = $this->storeTz();
        $startUtcIso = $req->input('sales.start_utc');
        $endUtcIso   = $req->input('sales.end_utc');

        if (!$startUtcIso || !$endUtcIso) {
            return response()->json(['error'=>'sales.start_utc and sales.end_utc required'], 422);
        }

        // Convert the UTC ISO window to store-local Carbons
        $startLocal = Carbon::parse($startUtcIso, 'UTC')->timezone($storeTz);
        $endLocal   = Carbon::parse($endUtcIso,   'UTC')->timezone($storeTz);

        $baseUrl = rtrim($this->baseUrlForOrgObj($org), '/');
        $client  = $this->httpFor($keys['vendorKey']);

        try {
            $all = $this->listReceiptsV2Window($client, $baseUrl, $keys['licenseNumber'], $startLocal, $endLocal, $kind);

            // paginate locally
            $total = count($all);
            $pages = max(1, (int)ceil($total / $pageSize));
            $slice = array_slice($all, ($page-1)*$pageSize, $pageSize);

            // upsert each receipt locally
            foreach ($slice as $r) {
                $salesTime = $this->parseMetrcLocalTime($r['SalesDateTime'] ?? '', $storeTz);
                if (!$salesTime) continue;
                $sum = $this->sumTransactionsPretax($r['Transactions'] ?? []);
                $this->upsertLocalReceipt((object)[
                    'id' => null,
                    'user_id' => auth()->id(),
                ], $r, $sum, $salesTime);
            }

            $doneKind = ($page >= $pages);
            return response()->json([
                'ok'          => true,
                'kind'        => $kind,
                'page'        => $page,
                'page_size'   => $pageSize,
                'total'       => $total,
                'done_kind'   => $doneKind,
                'next_kind'   => $doneKind ? ($kind === 'active' ? 'inactive' : 'done') : $kind,
                'next_page'   => $doneKind ? null : ($page+1),
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Surface throttling hint if present
            if (stripos($msg, '429') !== false || stripos($msg, 'Too Many') !== false) {
                return response()->json([
                    'throttled'  => true,
                    'retry_after'=> 5,
                    'error'      => $msg,
                ], 429);
            }
            return response()->json(['error' => $msg], 500);
        }
    }

    // =============================================================================
    // Core push (v2) — with correct totals via UnitPrice math
    // =============================================================================

    protected function performMetrcPush(int $saleId, array $opts = []): array
    {
        $sale = $this->loadSaleRow($saleId);
        if (!$sale) throw new \RuntimeException("Sale #{$saleId} not found.");

        $org   = $this->resolveOrgFromContext(null, $sale);
        $keys  = $this->resolveOrgAuth($org, $sale);
        $vendorKey     = $keys['vendorKey'];
        $licenseNumber = $keys['licenseNumber'];
        $baseUrl       = rtrim($this->baseUrlForOrgObj($org), '/');

        if (!$licenseNumber) throw new \RuntimeException('Missing organization license number for METRC.');
        if (!$vendorKey)     throw new \RuntimeException('Missing org admin API key (password) for METRC.');

        $storeTz       = $this->storeTz();
        $salesLocal    = $this->saleLocalMoment((object)$sale, $storeTz);
        $salesLocalIso = $this->fmtMetrcLocal($salesLocal);

        $items = $this->loadSaleItemsRich($saleId);

        $eligibleTxns = [];
        $pretaxSub = 0.0;
        $itemDiscTotal = 0.0;

        foreach ($items as $it) {
            $pkg = $this->resolvePackageLabel($it);
            if ($pkg === '') continue;

            [$qty, $unitPrice, $lineAfterInline, $discInline] = $this->priceMath($it);
            $uom = $this->determineUomFromItem($it);

            $pretaxSub     += $lineAfterInline;
            $itemDiscTotal += $discInline;

            $eligibleTxns[] = [
                'PackageLabel'  => $pkg,
                'Quantity'      => round(max($qty, 0.0001), 3),
                'UnitOfMeasure' => $uom,
                'UnitPrice'     => round(max($unitPrice, 0.0), 2),
            ];
        }

        if (empty($eligibleTxns)) {
            throw new \RuntimeException('No transactable lines with resolvable PackageLabel for this sale.');
        }

        $customerType  = ucfirst((string)($sale->customer_type ?? 'Consumer'));
        if (!in_array($customerType, ['Consumer','Patient','Caregiver'], true)) $customerType = 'Consumer';

        $orderDiscType  = $sale->order_discount_type  ?? null;
        $orderDiscValue = (float)($sale->order_discount_value ?? 0);
        $orderDiscount  = $this->computeOrderDiscount($pretaxSub, $orderDiscType, $orderDiscValue);

        $taxablePortion = 1.0;
        $taxPct      = $this->combinedTaxPercent();
        $estimatedTax= (($customerType === 'Consumer') ? (($pretaxSub - $orderDiscount * $taxablePortion) * ($taxPct/100)) : 0.0);
        $grandTotal  = max(0.0, $pretaxSub - $orderDiscount + $estimatedTax);

        $external = $this->bestExternalNumber($sale);

        $receipt = [
            'SalesDateTime'     => $salesLocalIso,
            'CustomerType'      => $customerType,
            'SalesCustomerType' => $customerType,
            'ReceiptNumber'     => $external,
            'IsFinal'           => !(bool)($opts['corrected'] ?? false),
            'Transactions'      => $eligibleTxns,
        ];

        // If force_post, skip PUT path even when we have a local id
        $existingLocal = $this->findLocalMetrcReceiptForSale($sale, $external, $salesLocal->format('Y-m-d H:i:s'));
        $existingMetrcId = (!empty($opts['force_post'])) ? null
            : ($existingLocal && isset($existingLocal->metrc_id) ? (int)$existingLocal->metrc_id : null);

        [$respJson, $httpResp] = $this->upsertReceiptV2($baseUrl, $licenseNumber, $vendorKey, $receipt, $existingMetrcId);

        if (!$httpResp || !$httpResp->ok()) {
            $body = $httpResp ? $httpResp->body() : null;
            throw new \RuntimeException("METRC v2 push failed (" . ($httpResp ? $httpResp->status() : 'NA') . '): ' . mb_substr(is_string($body) ? $body : json_encode($body), 0, 400));
        }

        $metrcOut = $respJson ?? [];
        if (isset($metrcOut[0])) $metrcOut = $metrcOut[0];

        $this->upsertLocalReceipt($sale, is_array($metrcOut) ? $metrcOut : [], $pretaxSub, $salesLocal);

        return [
            'ok' => true,
            'metrc_response' => $metrcOut,
            'metrc_payload'  => $receipt,
            'totals' => [
                'pretax_subtotal'      => round($pretaxSub, 2),
                'item_discount_total'  => round($itemDiscTotal, 2),
                'order_discount_type'  => $orderDiscType,
                'order_discount_value' => $orderDiscValue,
                'order_discount'       => round($orderDiscount, 2),
                'estimated_tax_pct'    => round($taxPct, 2),
                'estimated_tax_amount' => round($estimatedTax, 2),
                'grand_total_estimate' => round($grandTotal, 2),
            ],
            'sales_date_time'    => $salesLocalIso,
            'external_reference' => $external,
        ];
    }

    // =============================================================================
    // HTTP (v2) + VOID support
    // =============================================================================

    protected function httpFor(string $vendorKey): PendingRequest
    {
        return Http::withBasicAuth($this->vendorUsername, $vendorKey)->acceptJson()->retry(2, 500);
    }

    protected function upsertReceiptV2(string $baseUrl, string $licenseNumber, string $vendorKey, array $receipt, ?int $existingMetrcId = null): array
    {
        $client = $this->httpFor($vendorKey);

        if ($existingMetrcId) {
            $toPut = $receipt; $toPut['Id'] = $existingMetrcId;
            $put = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
                ->put("{$baseUrl}/sales/v2/receipts", [ $toPut ]);
            if ($put->ok()) return [$put->json() ?? [], $put];
        }

        $post = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
            ->post("{$baseUrl}/sales/v2/receipts", [ $receipt ]);
        if ($post->ok()) return [$post->json() ?? [], $post];

        // optional best-effort VOID→POST if we discover an existing by external/time
        $ext = (string)($receipt['ReceiptNumber'] ?? '');
        $ldtLocalSql = str_replace('T', ' ', (string)($receipt['SalesDateTime'] ?? ''));
        $localExisting = $this->findLocalMetrcReceiptByExtOrTime($ext, $ldtLocalSql);

        if ($localExisting && !empty($localExisting->metrc_id)) {
            $rid = (int)$localExisting->metrc_id;
            $this->voidReceipt($baseUrl, $licenseNumber, $vendorKey, $rid);

            $post2 = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
                ->post("{$baseUrl}/sales/v2/receipts", [ $receipt ]);
            if ($post2->ok()) return [$post2->json() ?? [], $post2];

            return [null, $post2];
        }

        return [null, $post];
    }

    /** Try both v2 void shapes, return true if either succeeds */
    protected function voidReceipt(string $baseUrl, string $licenseNumber, string $vendorKey, int $id): bool
    {
        $client = $this->httpFor($vendorKey);

        $void1 = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
            ->post("{$baseUrl}/sales/v2/receipts/void", [ ['Id' => $id] ]);

        if ($void1->ok()) return true;

        $void2 = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
            ->post("{$baseUrl}/sales/v2/receipts/{$id}/void", []);
        return $void2->ok();
    }

    /**
     * Helper: GET first; if not OK (e.g., 405 in OR), try POST with identical params.
     */
    protected function getOrPostJson(PendingRequest $client, string $url, array $query, array $body)
    {
        $resp = $client->withOptions(['query' => $query])->get($url);
        if ($resp->ok()) return $resp;

        return $client->withOptions(['query' => $query])->post($url, $body);
    }

    /**
     * Fetch receipts for a window from v2 API.
     * Tries multiple shapes and both ACTIVE/INACTIVE sets.
     * $kind: 'active' | 'inactive'
     * Returns array of receipts.
     */
    protected function listReceiptsV2Window(PendingRequest $client, string $baseUrl, string $licenseNumber, Carbon $startLocal, Carbon $endLocal, string $kind = 'active'): array
    {
        $isoLocalStart = $this->fmtMetrcLocal($startLocal);
        $isoLocalEnd   = $this->fmtMetrcLocal($endLocal);

        $q = ['licenseNumber' => $licenseNumber];
        $body = [
            'licenseNumber' => $licenseNumber,
            // Try the common v2 window keys:
            'salesDateStart'   => $isoLocalStart,
            'salesDateEnd'     => $isoLocalEnd,
            'start'            => $isoLocalStart,
            'end'              => $isoLocalEnd,
        ];

        $urls = [
            "{$baseUrl}/sales/v2/receipts/{$kind}",
            "{$baseUrl}/sales/v2/receipts/{$kind}/list",
            "{$baseUrl}/sales/v2/receipts/list",   // some states use a single "list" with filters
            "{$baseUrl}/sales/v2/receipts",        // generic list
        ];

        foreach ($urls as $u) {
            try {
                $resp = $this->getOrPostJson($client, $u, $q, $body);
                if ($resp->ok()) {
                    $data = $resp->json();
                    if (is_array($data)) {
                        // Normalize list: sometimes returns single receipt obj for POST create — guard against that.
                        $arr = array_values(array_filter(is_array($data) ? $data : [] , fn($x)=>is_array($x)));
                        return $arr;
                    }
                }
            } catch (\Throwable $e) {
                // keep trying the next shape
                continue;
            }
        }

        return [];
    }

    // =============================================================================
    // Receipt discovery (by time) + matching
    // =============================================================================

    /**
     * Fetch receipts around a local moment (± minutes). Tries several common query shapes;
     * returns the first successful array payload.
     */
    protected function fetchReceiptsNear(string $baseUrl, string $licenseNumber, string $vendorKey, Carbon $localWhen, int $minutes = 15): array
    {
        $client = $this->httpFor($vendorKey);

        $startLocal = $localWhen->copy()->subMinutes($minutes);
        $endLocal   = $localWhen->copy()->addMinutes($minutes);

        $isoLocalStart = $this->fmtMetrcLocal($startLocal);
        $isoLocalEnd   = $this->fmtMetrcLocal($endLocal);

        $isoUtcStartZ = $startLocal->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
        $isoUtcEndZ   = $endLocal->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

        $tries = [
            ['salesDateStart' => $isoLocalStart, 'salesDateEnd' => $isoLocalEnd],
            ['lastModifiedStart' => $isoUtcStartZ, 'lastModifiedEnd' => $isoUtcEndZ],
            ['start' => $isoLocalStart, 'end' => $isoLocalEnd],
        ];

        foreach ($tries as $params) {
            // GET first
            $resp = $client->withOptions(['query' => array_merge(['licenseNumber' => $licenseNumber], $params)])
                ->get("{$baseUrl}/sales/v2/receipts");
            if ($resp->ok()) {
                $data = $resp->json();
                if (is_array($data)) return $data;
            }

            // POST fallback (OR requires POST for some list ops)
            $resp2 = $client->withOptions(['query' => ['licenseNumber' => $licenseNumber]])
                ->post("{$baseUrl}/sales/v2/receipts/list", $params);
            if ($resp2->ok()) {
                $data = $resp2->json();
                if (is_array($data)) return $data;
            }
        }
        return [];
    }

    /** Choose the closest-in-time receipt, weighted by PackageLabel overlap if available. */
    protected function pickBestReceiptMatch(array $receipts, Carbon $localWhen, array $localLabels, string $storeTz): ?array
    {
        $best = null;
        $bestScore = -INF;

        foreach ($receipts as $r) {
            if (!is_array($r)) continue;
            $salesTime = isset($r['SalesDateTime']) ? $this->parseMetrcLocalTime($r['SalesDateTime'], $storeTz) : null;
            if (!$salesTime) continue;

            $delta = abs($salesTime->diffInSeconds($localWhen));
            $overlap = 0;

            if (!empty($localLabels) && !empty($r['Transactions']) && is_array($r['Transactions'])) {
                $remoteLabels = [];
                foreach ($r['Transactions'] as $t) {
                    if (!empty($t['PackageLabel'])) $remoteLabels[] = (string)$t['PackageLabel'];
                }
                $overlap = count(array_intersect($localLabels, array_unique($remoteLabels)));
            }

            // prioritize overlap, then recency
            $score = ($overlap * 100000) - $delta;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $r;
            }
        }

        return $best;
    }

    protected function parseMetrcLocalTime(string $s, string $storeTz): ?Carbon
    {
        $s = trim($s);
        if ($s === '') return null;
        try {
            // v2 returns local wall time without offset
            return Carbon::parse($s, $storeTz)->timezone($storeTz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function labelsFromItems(array $items): array
    {
        $labels = [];
        foreach ($items as $it) {
            $lab = $this->resolvePackageLabel($it);
            if ($lab !== '') $labels[] = $lab;
        }
        return array_values(array_unique($labels));
    }

    protected function sumTransactionsPretax(array $txns): float
    {
        $sum = 0.0;
        foreach ($txns as $t) {
            $q = (float)($t['Quantity'] ?? 0);
            $p = (float)($t['UnitPrice'] ?? 0);
            $sum += ($q * $p);
        }
        return round($sum, 2);
    }

    // =============================================================================
    // Data loading + math (same as in prior working version)
    // =============================================================================

    protected function loadSaleRow(int $saleId)
    {
        return DB::table('sales')->where('id', $saleId)->first();
    }

    protected function loadSaleItemsRich(int $saleId): array
    {
        if (!Schema::hasTable('sale_items')) return [];

        $siHas = fn($c) => Schema::hasColumn('sale_items', $c);
        $invHas = fn($c) => Schema::hasTable('inventories') && Schema::hasColumn('inventories', $c);
        $mpAvail = Schema::hasTable('metrc_packages') && Schema::hasColumn('metrc_packages','Label');

        $select = [
            'si.id as sale_item_id',
            'si.product_id',
            'si.quantity', 'si.qty',
            'si.unit_price', 'si.price',
            'si.line_total', 'si.total',
            'si.price_is_line_total',
            'si.inline_discount_type','si.inline_discount_value',
            'si.metrc_package_label','si.metrc_package',
            'si.package_id',
        ];
        $q = DB::table('sale_items as si')->where('si.sale_id', $saleId);

        if (Schema::hasTable('inventories')) {
            $invLabelCol = $invHas('Label') ? 'Label' : ($invHas('label') ? 'label' : ($invHas('metrc_package') ? 'metrc_package' : null));
            $invCols = ['inv.id as inv_id','inv.product_id as inv_product_id'];
            if ($invLabelCol) $invCols[] = "inv.$invLabelCol as inv_label";
            if ($invHas('category_id')) $invCols[] = 'inv.category_id';
            $q->leftJoin('inventories as inv', 'inv.id', '=', 'si.product_id')->addSelect($invCols);
        }

        if ($mpAvail && $siHas('package_id')) {
            $q->leftJoin('metrc_packages as mp', 'mp.Label', '=', 'si.package_id')
              ->addSelect('mp.Label as mp_label');
        }

        $rows = $q->get();

        return array_map(fn($r) => (array) $r, $rows->all());
    }

    protected function resolvePackageLabel(array $it): string
    {
        foreach (['metrc_package_label','metrc_package'] as $c) {
            $v = trim((string)($it[$c] ?? ''));
            if ($v !== '') return $v;
        }
        $mp = trim((string)($it['mp_label'] ?? ''));
        if ($mp !== '') return $mp;

        $inv = trim((string)($it['inv_label'] ?? ''));
        if ($inv !== '') return $inv;

        $pid = trim((string)($it['package_id'] ?? ''));
        if ($pid !== '' && preg_match('/^[A-Z0-9\-#]{8,36}$/', $pid)) return $pid;

        return '';
    }

    protected function priceMath(array $it): array
    {
        $qty = (float) (
            ($it['quantity'] ?? null) !== null ? $it['quantity']
            : (($it['qty'] ?? null) !== null ? $it['qty'] : 0)
        );
        if ($qty <= 0) $qty = 1.0;

        $unit = (float) (
            ($it['unit_price'] ?? null) !== null ? $it['unit_price']
            : (($it['price'] ?? null) !== null ? $it['price'] : 0)
        );

        $line = (float) (
            ($it['line_total'] ?? null) !== null ? $it['line_total']
            : (($it['total'] ?? null) !== null ? $it['total'] : ($unit * $qty))
        );

        $priceIsLine = !empty($it['price_is_line_total']);
        if ($priceIsLine || $unit <= 0) {
            $unit = ($qty > 0) ? ($line / $qty) : $unit;
        } elseif ($line <= 0) {
            $line = $unit * $qty;
        }

        $discType = $it['inline_discount_type'] ?? null;
        $discVal  = (float)($it['inline_discount_value'] ?? 0);
        $discAmt  = 0.0;

        if ($discVal > 0 && $discType) {
            if ($discType === 'percent')      $discAmt = $line * ($discVal / 100);
            elseif ($discType === 'fixed')    $discAmt = min($line, $discVal);
        }
        $lineAfter = max(0.0, $line - $discAmt);
        $effUnit   = ($qty > 0) ? ($lineAfter / $qty) : $unit;

        return [round($qty,3), round($effUnit,2), round($lineAfter,2), round($discAmt,2)];
    }

    protected function computeOrderDiscount(float $pretaxSub, ?string $type, float $val): float
    {
        if ($val <= 0 || !$type) return 0.0;
        if ($type === 'percent') return max(0.0, $pretaxSub * $val/100);
        if ($type === 'fixed')   return max(0.0, min($val, $pretaxSub));
        return 0.0;
    }

    protected function combinedTaxPercent(): float
    {
        try {
            $county = (float) (function_exists('setting_by_key') ? (setting_by_key('county_tax') ?: 0) : 0);
            $city   = (float) (function_exists('setting_by_key') ? (setting_by_key('CityTax')    ?: 0) : 0);
            $state  = (float) (function_exists('setting_by_key') ? (setting_by_key('StateTax')   ?: 0) : 0);
            return $county + $city + $state;
        } catch (\Throwable $e) { return 0.0; }
    }

    protected function determineUomFromItem(array $it): string
    {
        if (isset($it['category_id']) && (int)$it['category_id'] === 1) return 'Grams';
        return 'Each';
    }

    // =============================================================================
    // Local TZ + receipt stubs + linking helpers
    // =============================================================================

    protected function storeTz(): string
    {
        try {
            if (function_exists('setting_by_key')) {
                $tz = (string) (setting_by_key('store_timezone') ?? '');
                if ($tz !== '') return $tz;
            }
        } catch (\Throwable $e) {}
        return (string) (config('app.timezone') ?: 'UTC');
    }

    protected function saleLocalMoment(object $sale, string $storeTz): Carbon
    {
        $localFields = [
            'receipt_at','receipt_time','receipt_printed_at',
            'closed_at','completed_at','paid_at',
            'pos_time','pos_local_time'
        ];

        foreach ($localFields as $c) {
            if (Schema::hasColumn('sales', $c) && !empty($sale->{$c})) {
                return Carbon::parse((string)$sale->{$c}, $storeTz)->timezone($storeTz);
            }
        }

        $base = Schema::hasColumn('sales','created_at') && !empty($sale->created_at)
            ? (string)$sale->created_at
            : 'now';

        // created_at stored as UTC; convert to store-local
        return Carbon::parse($base, 'UTC')->timezone($storeTz);
    }

    protected function fmtMetrcLocal(Carbon $dtLocal): string
    {
        return $dtLocal->format('Y-m-d\TH:i:s');
    }

    protected function bestExternalNumber($sale): string
    {
        $candidates = [
            'external_reference',
            'external_receipt_number','invoice_number','invoice_no','order_number','order_no'
        ];
        foreach ($candidates as $c) {
            if (Schema::hasColumn('sales', $c)) {
                $v = trim((string)($sale->{$c} ?? ''));
                if ($v !== '') return Str::upper(mb_substr($v, 0, 60));
            }
        }
        return 'INV-' . (int) $sale->id;
    }

    protected function findLocalMetrcReceiptForSale($sale, string $external, string $storeLocalYmdHis)
    {
        if (!Schema::hasTable('metrc_receipts')) return null;

        $q = DB::table('metrc_receipts');
        if (Schema::hasColumn('metrc_receipts','organization_id')) {
            $orgId = $this->orgIdFromSaleOrAuth($sale);
            if ($orgId) $q->where('organization_id', $orgId);
        }

        $row = null;
        if ($external !== '' && Schema::hasColumn('metrc_receipts','external_receipt_number')) {
            $row = (clone $q)->where('external_receipt_number', $external)->orderByDesc('id')->first();
        }
        if (!$row && Schema::hasColumn('metrc_receipts','sales_date_time')) {
            $row = (clone $q)->where('sales_date_time', $storeLocalYmdHis)->orderByDesc('id')->first();
        }
        return $row;
    }

    protected function findLocalMetrcReceiptByExtOrTime(string $external, string $localYmdHis)
    {
        if (!Schema::hasTable('metrc_receipts')) return null;
        $q = DB::table('metrc_receipts');

        if ($external !== '' && Schema::hasColumn('metrc_receipts','external_receipt_number')) {
            $row = (clone $q)->where('external_receipt_number', $external)->orderByDesc('id')->first();
            if ($row) return $row;
        }
        if (Schema::hasColumn('metrc_receipts','sales_date_time')) {
            return (clone $q)->where('sales_date_time', $localYmdHis)->orderByDesc('id')->first();
        }
        return null;
    }

    /** Insert-or-update local receipt row keyed by (external_receipt_number, sales_date_time). */
    protected function upsertLocalReceipt($sale, array $metrcResp, ?float $sum, Carbon $salesLocal): void
    {
        if (!Schema::hasTable('metrc_receipts')) return;

        $fields = Schema::getColumnListing('metrc_receipts');

        $payload = [
            'metrc_id'               => $metrcResp['Id'] ?? $metrcResp['id'] ?? null,
            'receipt_number'         => $metrcResp['ReceiptNumber'] ?? null,
            'external_receipt_number'=> $this->bestExternalNumber($sale),
            'total_price'            => $sum != null ? round($sum, 2) : null,
            'sales_date_time'        => $salesLocal->format('Y-m-d H:i:s'),
            'is_final'               => 1,
        ];
        if (!in_array('metrc_id',$fields,true)) unset($payload['metrc_id']);
        if (!in_array('receipt_number',$fields,true)) unset($payload['receipt_number']);
        if (!in_array('external_receipt_number',$fields,true)) unset($payload['external_receipt_number']);
        if (!in_array('total_price',$fields,true)) unset($payload['total_price']);
        if (!in_array('sales_date_time',$fields,true)) unset($payload['sales_date_time']);
        if (!in_array('is_final',$fields,true)) unset($payload['is_final']);
        if (in_array('organization_id',$fields,true)) {
            $orgId = $this->orgIdFromSaleOrAuth($sale);
            if ($orgId) $payload['organization_id'] = (int)$orgId;
        }
        if (in_array('user_id',$fields,true) && isset($sale->user_id)) {
            $payload['user_id'] = (int)$sale->user_id;
        } elseif (in_array('user_id',$fields,true) && auth()->id()) {
            $payload['user_id'] = (int)auth()->id();
        }

        $q = DB::table('metrc_receipts');
        if (isset($payload['external_receipt_number'])) $q->where('external_receipt_number', $payload['external_receipt_number']);
        if (isset($payload['sales_date_time']))        $q->where('sales_date_time', $payload['sales_date_time']);

        $existing = $q->first();

        if ($existing) {
            $upd = $payload;
            unset($upd['organization_id'], $upd['user_id']);
            DB::table('metrc_receipts')->where('id',$existing->id)->update(array_filter($upd, fn($v)=>$v!==null));
        } else {
            DB::table('metrc_receipts')->insert($payload);
        }

        // also attempt to write sales.metrc_receipt_id if present
        $this->maybeWriteSaleLink($sale, $metrcResp);
    }

    /** If sales.metrc_receipt_id exists, set it to the METRC Id we know. */
    protected function maybeWriteSaleLink($sale, array $metrcResp): void
    {
        if (!Schema::hasColumn('sales','metrc_receipt_id')) return;
        $rid = $metrcResp['Id'] ?? $metrcResp['id'] ?? null;
        if (!$rid) return;

        DB::table('sales')->where('id',(int)$sale->id)->update(['metrc_receipt_id' => (int)$rid]);
    }

    // =============================================================================
    // Org / Auth resolution (license from org of the user; vendorKey from org admin apiKey)
    // =============================================================================

    protected function resolveOrgAuth($org = null, $sale = null): array
    {
        if (!$org) $org = $this->resolveOrgFromContext(null, $sale);

        $licenseNumber = $org->license_number ?? null;

        // Find an org admin (role_id = 2) and use their apiKey as the vendor password
        $vendorKey = null;
        if ($org && isset($org->id)) {
            $admin = DB::table('users')
                ->where('organization_id', $org->id)
                ->where('role_id', 2)
                ->orderBy('id')
                ->first();

            if ($admin && !empty($admin->apiKey)) {
                $vendorKey = $admin->apiKey;
            }
        }

        return ['vendorKey' => $vendorKey, 'licenseNumber' => $licenseNumber];
    }

    protected function resolveOrgFromContext(?Request $req, $sale = null)
    {
        // 1) explicit organization_id on request
        $orgId = $req ? (int)($req->input('organization_id') ?: 0) : 0;

        // 2) from sale's cashier
        if (!$orgId && $sale && isset($sale->user_id) && Schema::hasTable('users')) {
            $orgId = (int)(DB::table('users')->where('id',$sale->user_id)->value('organization_id') ?: 0);
        }

        // 3) from auth user
        if (!$orgId && auth()->check()) {
            $orgId = (int)(auth()->user()->organization_id ?? 0);
        }

        // 4) if exactly one organization exists, use it
        if (!$orgId && Schema::hasTable('organizations')) {
            $orgs = DB::table('organizations')->select('id')->orderBy('id')->limit(2)->get();
            if ($orgs->count() === 1) $orgId = (int)$orgs[0]->id;
        }

        if ($orgId && Schema::hasTable('organizations')) {
            return DB::table('organizations')->where('id', $orgId)->first() ?: (object)[];
        }
        return (object)[];
    }

    protected function resolveOrgIdFromRequest(Request $req): ?int
    {
        $org = $this->resolveOrgFromContext($req, null);
        return isset($org->id) ? (int)$org->id : null;
    }

    protected function orgIdFromSaleOrAuth($sale): ?int
    {
        if ($sale && isset($sale->user_id) && Schema::hasTable('users')) {
            $oid = DB::table('users')->where('id',$sale->user_id)->value('organization_id');
            if ($oid) return (int)$oid;
        }
        if (auth()->check()) {
            $oid = auth()->user()->organization_id ?? null;
            if ($oid) return (int)$oid;
        }
        return null;
    }

    protected function baseUrlForOrgObj($org): string
    {
        if ($org && !empty($org->metrc_base_url)) return $org->metrc_base_url;

        $map = [
            'OR' => 'https://api-or.metrc.com',
            'CA' => 'https://api-ca.metrc.com',
            'MI' => 'https://api-mi.metrc.com',
            'CO' => 'https://api-co.metrc.com',
            'NV' => 'https://api-nv.metrc.com',
            'OK' => 'https://api-ok.metrc.com',
            'MO' => 'https://api-mo.metrc.com',
            'MT' => 'https://api-mt.metrc.com',
            'LA' => 'https://api-la.metrc.com',
            'MA' => 'https://api-ma.metrc.com',
        ];
        $state = strtoupper((string)($org->state ?? ''));
        return $map[$state] ?? 'https://api-or.metrc.com';
    }
}
