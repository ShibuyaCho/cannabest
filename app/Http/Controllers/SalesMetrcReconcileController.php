<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SalesMetrcReconcileController extends Controller
{
    /* =========================================================
     |  Config & throttling
     |=========================================================*/

    /** METRC vendor username for Basic Auth */
    private const VENDOR_USER = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    /** Spacing between calls (ms) */
    private const MIN_INTERVAL_MS = 350;

    /** Guard against runaway paging */
    private const MAX_PAGES_PER_KIND = 2500;

    /** Page size bounds */
    private const MIN_PAGE_SIZE = 5;
    private const MAX_PAGE_SIZE = 25;

    /** Last call time, for throttle */
    private float $lastCallAt = 0.0;

    /* =========================================================
     |  Timezones
     |=========================================================*/

    private function storeTz(): string
    {
        try {
            if (function_exists('setting_by_key')) {
                $tz = setting_by_key('store_timezone');
                if ($tz) return (string)$tz;
            }
        } catch (\Throwable $e) {}
        // Oregon default
        return (string)(config('app.timezone') ?: 'America/Los_Angeles');
    }

    /** How sales.created_at should be interpreted before localizing */
    private function salesAssumeTz(): string { return 'UTC'; }

    /* =========================================================
     |  Org / User helpers
     |=========================================================*/

    private function currentOrgId(): ?int
    {
        return optional(Auth::user())->organization_id;
    }

    private function resolveOrgId(Request $r): ?int
    {
        $fromReq = (int)($r->input('organization_id') ?? $r->query('organization_id') ?? 0);
        return $fromReq ?: $this->currentOrgId();
    }

    private function resolveApiKey(): ?string
    {
        $u = Auth::user();
        if ($u) {
            foreach (['apiKey','api_key','metrc_api_key','metrc_key','metrcVendorKey'] as $k) {
                if (!empty($u->{$k})) return (string)$u->{$k};
            }
            if (property_exists($u,'meta') && is_array($u->meta ?? null)) {
                foreach (['apiKey','api_key','metrc_api_key'] as $k) {
                    if (!empty($u->meta[$k])) return (string)$u->meta[$k];
                }
            }
            if (method_exists($u,'organization') && $u->organization) {
                foreach (['metrc_api_key','metrc_key','api_key'] as $k) {
                    if (!empty($u->organization->{$k})) return (string)$u->organization->{$k};
                }
            }
        }
        try {
            if (function_exists('setting_by_key')) {
                $s = setting_by_key('metrc_api_key') ?? setting_by_key('api_key');
                if ($s) return trim((string)$s);
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private function resolveLicense(Request $r): ?string
    {
        foreach (['licenseNumber','license_number','license'] as $k) {
            $v = trim((string)($r->input($k, $r->query($k, ''))));
            if ($v !== '') return $v;
        }
        $u = Auth::user();
        if ($u && method_exists($u, 'organization') && $u->organization) {
            foreach (['metrc_license_number','license_number','metrc_license','license'] as $k) {
                $v = trim((string)($u->organization->{$k} ?? ''));
                if ($v !== '') return $v;
            }
        }
        try {
            if (function_exists('setting_by_key')) {
                $s = setting_by_key('metrc_license_number') ?? setting_by_key('license_number');
                if ($s) return trim((string)$s);
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /* =========================================================
     |  Region / base URL
     |=========================================================*/

    private function inferRegion(): string
    {
        // Prefer configured value; default to OR to avoid api-gov weirdness
        $cfg = strtoupper((string)(config('services.metrc.region', env('METRC_REGION', 'OR'))));
        return $cfg ?: 'OR';
    }

    private function baseUrl(): string
    {
        $explicit = rtrim((string) config('services.metrc.base', env('METRC_BASE_URL', '')), '/');
        if ($explicit) return $explicit;

        return match ($this->inferRegion()) {
            'OR','OREGON'        => 'https://api-or.metrc.com',
            'CA','CALIFORNIA'    => 'https://api-ca.metrc.com',
            'CO','COLORADO'      => 'https://api-co.metrc.com',
            'MI','MICHIGAN'      => 'https://api-mi.metrc.com',
            'MO','MISSOURI'      => 'https://api-mo.metrc.com',
            'OK','OKLAHOMA'      => 'https://api-ok.metrc.com',
            'OH','OHIO'          => 'https://api-oh.metrc.com',
            'NV','NEVADA'        => 'https://api-nv.metrc.com',
            'MA','MASSACHUSETTS' => 'https://api-ma.metrc.com',
            default              => 'https://api-or.metrc.com',
        };
    }

    /* =========================================================
     |  HTTP + throttle
     |=========================================================*/

    private function throttle(int $ms = self::MIN_INTERVAL_MS): void
    {
        $now = microtime(true);
        $min = $ms / 1000.0;
        $dt  = $now - $this->lastCallAt;
        if ($dt < $min) usleep((int)(($min - $dt) * 1_000_000));
        $this->lastCallAt = microtime(true);
    }

    private function metrcGet(string $path, array $query, string $license)
    {
        $key = $this->resolveApiKey();
        if (!$key) {
            return (object)[
                'ok'=>false,'status'=>0,'json'=>fn()=>[],'body'=>'Missing METRC API key.',
            ];
        }
        $this->throttle();
        $url = $this->baseUrl() . '/' . ltrim($path, '/');

        Log::info('METRC GET', ['url'=>$url, 'q'=>$query]);

        try {
            $resp = Http::withBasicAuth(self::VENDOR_USER, $key)
                ->withHeaders([
                    'Accept'               => 'application/json',
                    'Content-Type'         => 'application/json',
                    'x-mme-license-number' => $license, // Oregon requires this header
                    'User-Agent'           => 'CannaBestPOS/inline-sync',
                ])
                ->retry(1, 300)
                ->get($url, $query);

            if (!$resp->ok()) {
                Log::warning('METRC non-OK', [
                    'status' => $resp->status(),
                    'body'   => mb_substr((string)$resp->body(), 0, 4000),
                ]);
            }

            return $resp;
        } catch (\Throwable $e) {
            Log::warning('METRC GET exception', ['err'=>$e->getMessage(), 'url'=>$url, 'q'=>$query]);
            return (object)[
                'ok'=>false,'status'=>0,'json'=>fn()=>[],'body'=>'fallback_failed: '.$e->getMessage(),
            ];
        }
    }

    private function metrcGetReceiptDetail(int $id, string $license): ?array
    {
        $resp = $this->metrcGet("/sales/v2/receipts/{$id}", ['licenseNumber'=>$license], $license);
        if (method_exists($resp,'ok') && $resp->ok()) {
            $js = $resp->json();
            // Some regions return an array with single item; normalize to object
            if (is_array($js)) {
                if ($this->isListArray($js) && count($js) === 1) {
                    $js = $js[0];
                }
                if (is_array($js)) return $js;
            }
            if (is_object($js)) return (array)$js;
        }
        Log::warning('METRC detail fetch failed', ['id'=>$id, 'err'=>method_exists($resp,'body')?(string)$resp->body():'']);
        return null;
    }

    /* =========================================================
     |  JSON unwrapping + normalization
     |=========================================================*/

    private function isListArray(mixed $a): bool
    {
        if (!is_array($a)) return false;
        $i = 0;
        foreach ($a as $k => $_) if ($k !== $i++) return false;
        return true;
    }

    private function unwrapReceipts(mixed $json): array
    {
        if (!is_array($json)) return [];

        // Flatten [[...]] → [...]
        if ($this->isListArray($json) && count($json) === 1 && is_array($json[0]) && $this->isListArray($json[0])) {
            $json = $json[0];
        }

        // Envelope objects { Data: [...] } / { Receipts: [...] } etc.
        if (!$this->isListArray($json)) {
            foreach (['Data','Receipts','Results','items','records'] as $k) {
                if (isset($json[$k]) && is_array($json[$k])) { $json = $json[$k]; break; }
            }
        }

        // Keep only rows with a positive Id (skip Id=0 stubs)
        $out = [];
        if (is_array($json) && $this->isListArray($json)) {
            foreach ($json as $row) {
                $arr = (array)$row;
                $id  = $arr['Id'] ?? $arr['ReceiptId'] ?? $arr['SaleId'] ?? null;
                if (is_numeric($id) && (int)$id > 0) $out[] = $arr;
            }
        }
        return $out;
    }

    private function normalizeReceipt(array $r, string $storeTz, string $license): array
    {
        $metrcId        = $r['Id'] ?? $r['ReceiptId'] ?? $r['SaleId'] ?? null;
        $receiptNumber  = $r['ReceiptNumber'] ?? $r['ReceiptNo'] ?? null;
        $externalNumber = $r['ExternalReceiptNumber'] ?? $r['ExternalReceiptNo'] ?? null;
        $licenseNumber  = $r['FacilityLicenseNumber'] ?? $r['LicenseNumber'] ?? $license;

        // Money
        $totalPrice = null;
        if (array_key_exists('TotalPrice', $r))       $totalPrice = is_null($r['TotalPrice']) ? null : (float)$r['TotalPrice'];
        elseif (array_key_exists('TotalAmount', $r))  $totalPrice = is_null($r['TotalAmount']) ? null : (float)$r['TotalAmount'];
        elseif (!empty($r['Transactions']) && is_array($r['Transactions'])) {
            $sum = 0.0;
            foreach ($r['Transactions'] as $t) {
                $t = (array)$t;
                $sum += isset($t['TotalAmount'])
                    ? (float)$t['TotalAmount']
                    : ((float)($t['Price'] ?? 0) * (float)($t['Quantity'] ?? 0));
            }
            $totalPrice = round($sum, 2);
        }

        $isFinal = (!empty($r['IsFinal']) || !empty($r['Archived']) || !empty($r['ArchivedDate'])) ? 1 : 0;

        // Timestamps
        $salesIso = $r['SalesDateTime'] ?? $r['SalesDateTimeUtc'] ?? $r['DateSold'] ?? $r['SoldDateTime'] ?? null;
        $salesLocal = null;
        if ($salesIso) {
            try {
                $salesLocal = Carbon::parse($salesIso)->timezone($storeTz)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        }

        return [
            'metrc_id'                => is_numeric($metrcId) ? (int)$metrcId : null,
            'receipt_number'          => $receiptNumber ? trim((string)$receiptNumber) : null,
            'external_receipt_number' => $externalNumber ? trim((string)$externalNumber) : null,
            'license_number'          => $licenseNumber ?: $license, // never NULL
            'total_price'             => $totalPrice,
            'is_final'                => $isFinal,
            'sales_date_time'         => $salesLocal,                // store-local
            'payload'                 => $r,                         // raw for audit
        ];
    }

    private function upsertReceipts(array $rows, string $storeTz, string $license): int
    {
        if (!Schema::hasTable('metrc_receipts')) return 0;

        $cols = Schema::getColumnListing('metrc_receipts');
        $can  = fn(string $c) => in_array($c, $cols, true);

        $u   = Auth::user();
        $uid = $u->id ?? null;
        $org = $u->organization_id ?? null;

        $saved = 0;

        foreach ($rows as $raw) {
            $norm = $this->normalizeReceipt((array)$raw, $storeTz, $license);

            // Must have metrc_id > 0
            if (empty($norm['metrc_id']) || (int)$norm['metrc_id'] <= 0) {
                Log::warning('Skipping METRC row without metrc_id', ['raw'=>$raw]);
                continue;
            }

            $row = [];
            if ($can('metrc_id'))                $row['metrc_id'] = (int)$norm['metrc_id'];
            if ($can('receipt_number'))          $row['receipt_number'] = $norm['receipt_number'];
            if ($can('external_receipt_number')) $row['external_receipt_number'] = $norm['external_receipt_number'];
            if ($can('license_number'))          $row['license_number'] = $norm['license_number'] ?: $license;
            if ($can('total_price'))             $row['total_price'] = $norm['total_price'];
            if ($can('is_final'))                $row['is_final'] = (int)$norm['is_final'];
            if ($can('sales_date_time'))         $row['sales_date_time'] = $norm['sales_date_time'];
            if ($can('user_id') && $uid)         $row['user_id'] = (int)$uid;
            if ($can('organization_id') && $org) $row['organization_id'] = (int)$org;
            if ($can('payload'))                 $row['payload'] = json_encode($norm['payload'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if ($can('updated_at'))              $row['updated_at'] = now();
            if ($can('created_at'))              $row['created_at'] = now();

            try {
                $q = DB::table('metrc_receipts')->where('metrc_id', $row['metrc_id']);
                if ($can('organization_id') && $org) $q->where('organization_id', $org);

                $ex = $q->first(['id']);
                if ($ex) {
                    if ($can('created_at')) unset($row['created_at']);
                    DB::table('metrc_receipts')->where('id', $ex->id)->update($row);
                } else {
                    DB::table('metrc_receipts')->insert($row);
                }
                $saved++;
            } catch (\Throwable $e) {
                Log::warning('metrc_receipts upsert failed', [
                    'err'=>$e->getMessage(),
                    'row'=>array_intersect_key($row, array_flip(['metrc_id','license_number','receipt_number','sales_date_time','total_price'])),
                ]);
            }
        }

        return $saved;
    }

    /* =========================================================
     |  Sales helpers (local moments & pre-tax)
     |=========================================================*/

    private function salePreTaxLocal($sale): ?float
    {
        foreach (['pre_tax_total','pretax','subtotal'] as $c) {
            if (Schema::hasColumn('sales', $c) && isset($sale->{$c})) {
                return (float)$sale->{$c};
            }
        }
        return null;
    }

    private function saleLocalMoment($sale, string $storeTz): Carbon
    {
        $localFields = [
            'receipt_at','receipt_time','receipt_printed_at',
            'closed_at','completed_at','paid_at',
            'pos_time','pos_local_time',
        ];
        foreach ($localFields as $c) {
            if (Schema::hasColumn('sales', $c) && !empty($sale->{$c})) {
                return Carbon::parse((string)$sale->{$c}, $storeTz)->timezone($storeTz);
            }
        }
        $base = Schema::hasColumn('sales','created_at') && !empty($sale->created_at)
            ? (string)$sale->created_at
            : 'now';
        return Carbon::parse($base, $this->salesAssumeTz())->timezone($storeTz);
    }

    private function presentSaleSelect(): array
    {
        $cols = ['id'];
        foreach (['created_at','pre_tax_total','pretax','subtotal','metrc_receipt_id','organization_id','status'] as $c) {
            if (Schema::hasColumn('sales',$c)) $cols[] = $c;
        }
        return $cols;
    }

    /* =========================================================
     |  Coverage + Cursor helpers (lightweight, DB-driven)
     |=========================================================*/

    private function hasStateTable(): bool
    {
        return Schema::hasTable('metrc_sync_state');
    }

    private function loadState(string $license): array
    {
        if (!$this->hasStateTable()) return [
            'last_sales_cursor_utc'    => null,
            'last_modified_cursor_utc' => null,
            'last_full_sync_at'        => null,
        ];

        $row = DB::table('metrc_sync_state')->where('license', $license)->first();
        if (!$row) return [
            'last_sales_cursor_utc'    => null,
            'last_modified_cursor_utc' => null,
            'last_full_sync_at'        => null,
        ];
        return [
            'last_sales_cursor_utc'    => $row->last_sales_cursor_utc ? Carbon::parse($row->last_sales_cursor_utc) : null,
            'last_modified_cursor_utc' => $row->last_modified_cursor_utc ? Carbon::parse($row->last_modified_cursor_utc) : null,
            'last_full_sync_at'        => $row->last_full_sync_at ? Carbon::parse($row->last_full_sync_at) : null,
        ];
    }

    private function saveState(string $license, array $patch): void
    {
        if (!$this->hasStateTable()) return;

        $now = now();
        $patch['updated_at'] = $now;

        $exists = DB::table('metrc_sync_state')->where('license', $license)->exists();
        if ($exists) {
            DB::table('metrc_sync_state')->where('license', $license)->update($patch);
        } else {
            DB::table('metrc_sync_state')->insert(array_merge([
                'license'    => $license,
                'created_at' => $now,
            ], $patch));
        }
    }

    private function countSalesInLocalWindow(Carbon $startL, Carbon $endL, ?int $orgId): int
    {
        $q = DB::table('sales');

        if ($orgId && Schema::hasColumn('sales','organization_id')) {
            $q->where('organization_id', $orgId);
        }
        if (Schema::hasColumn('sales','status')) {
            $q->where('status', 1);
        }

        // created_at (UTC)
        $q->whereBetween('created_at', [$startL->copy()->utc(), $endL->copy()->utc()]);

        // Also consider common local timestamp columns if present
        $localStart = $startL->format('Y-m-d H:i:s');
        $localEnd   = $endL->format('Y-m-d H:i:s');
        $q->orWhere(function($qq) use ($localStart, $localEnd) {
            foreach (['receipt_at','receipt_time','receipt_printed_at','closed_at','completed_at','paid_at','pos_time','pos_local_time'] as $c) {
                if (Schema::hasColumn('sales', $c)) {
                    $qq->orWhereBetween($c, [$localStart, $localEnd]);
                }
            }
        });

        try { return (int)$q->count(); } catch (\Throwable $e) { return 0; }
    }

    private function countReceiptsInLocalWindow(Carbon $startL, Carbon $endL, ?int $orgId): int
    {
        if (!Schema::hasTable('metrc_receipts')) return 0;

        $q = DB::table('metrc_receipts')
            ->whereBetween('sales_date_time', [
                $startL->format('Y-m-d H:i:s'),
                $endL->format('Y-m-d H:i:s'),
            ]);

        if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
            $q->where('organization_id', $orgId);
        }

        try { return (int)$q->count(); } catch (\Throwable $e) { return 0; }
    }

    private function needsWindowSync(string $license, Carbon $startL, Carbon $endL, ?int $orgId): array
    {
        $sales  = $this->countSalesInLocalWindow($startL, $endL, $orgId);
        $have   = $this->countReceiptsInLocalWindow($startL, $endL, $orgId);
        $state  = $this->loadState($license);

        $nowUtc = now()->utc();
        $salesCursor  = $state['last_sales_cursor_utc'];
        $modCursor    = $state['last_modified_cursor_utc'];

        $windowEndUtc = $endL->copy()->utc();
        $reasons = [];

        if ($sales > 0 && $have === 0) $reasons[] = 'sales_without_receipts';

        if (!$salesCursor || $salesCursor->lt($windowEndUtc->copy()->subHours(6))) {
            $reasons[] = 'sales_cursor_stale';
        }
        if (!$modCursor || $modCursor->lt($nowUtc->copy()->subHours(36))) {
            $reasons[] = 'modified_cursor_stale';
        }

        return [
            'should_sync' => !empty($reasons),
            'reasons'     => $reasons,
            'sales'       => $sales,
            'receipts'    => $have,
        ];
    }

    /* =========================================================
     |  Inline sync: init + chunk
     |=========================================================*/

    // POST /metrc/sync-inline/init
    public function syncInlineInit(Request $r)
    {
        $license = $this->resolveLicense($r);
        if (!$license) return response()->json(['ok'=>false,'message'=>'licenseNumber required'], 400);

        $tz = $this->storeTz();
        $startStr = (string)($r->input('start') ?? $r->input('start_date') ?? now($tz)->toDateString());
        $endStr   = (string)($r->input('end')   ?? $r->input('end_date')   ?? $startStr);

        $start = Carbon::parse($startStr.' 00:00:00', $tz);
        $end   = Carbon::parse($endStr.' 23:59:59', $tz);

        $mode     = (string)$r->input('mode', 'sales');
        $pageSize = max(self::MIN_PAGE_SIZE, min(self::MAX_PAGE_SIZE, (int)$r->input('page_size', 10)));

        return response()->json([
            'ok'=>true,
            'license'=>$license,
            'mode'=>$mode,
            'sales'=>[
                'start_utc'=>$start->copy()->utc()->toIso8601String(),
                'end_utc'  =>$end->copy()->utc()->toIso8601String(),
            ],
            'modified'=>null,
            'paging'=>['page'=>1,'page_size'=>$pageSize,'kind'=>'active'],
        ]);
    }

    // POST /metrc/sync-inline/chunk
    public function syncInlineChunk(Request $r)
    {
        $license = (string)($r->input('license') ?? $r->input('licenseNumber') ?? '');
        if ($license === '') $license = $this->resolveLicense($r) ?? '';
        if ($license === '') {
            return response()->json([
                'ok'=>false,'errors'=>['license required'],
                'kind'=>'active','page'=>1,'page_size'=>(int)$r->input('page_size',10),
                'done_kind'=>true,'next_page'=>1,'next_kind'=>'inactive',
                'throttled'=>false,'retry_after'=>0,
            ], 200);
        }

        $kind     = in_array($r->input('kind'), ['active','inactive'], true) ? $r->input('kind') : 'active';
        $page     = max(1, (int)$r->input('page', 1));
        $pageSize = max(self::MIN_PAGE_SIZE, min(self::MAX_PAGE_SIZE, (int)$r->input('page_size', 10)));
        $mode     = (string)$r->input('mode', 'sales');

        // Time window aliases
        $salesStart = $r->input('sales.start_utc') ?? $r->input('start_utc') ?? $r->input('salesStartUtc') ?? null;
        $salesEnd   = $r->input('sales.end_utc')   ?? $r->input('end_utc')   ?? $r->input('salesEndUtc')   ?? null;

        $modifiedStart = $r->input('modified.start_utc') ?? $r->input('modified_start_utc') ?? $r->input('lastModifiedStart') ?? null;
        $modifiedEnd   = $r->input('modified.end_utc')   ?? $r->input('modified_end_utc')   ?? $r->input('lastModifiedEnd')   ?? null;

        // default to today (store-local)
        if (!$salesStart && !$modifiedStart) {
            $tz = $this->storeTz();
            $startStr = (string)($r->input('start') ?? $r->input('start_date') ?? now($tz)->toDateString());
            $endStr   = (string)($r->input('end')   ?? $r->input('end_date')   ?? $startStr);
            $salesStart = Carbon::parse($startStr.' 00:00:00', $tz)->utc()->toIso8601String();
            $salesEnd   = Carbon::parse($endStr.' 23:59:59', $tz)->utc()->toIso8601String();
        }

        $tz = $this->storeTz();

        // Build query variants (OR-compatible; try several time formats)
        $variants = [];
        if ($mode === 'modified') {
            $lmStart = $modifiedStart ?: $salesStart;
            $lmEnd   = $modifiedEnd   ?: $salesEnd;
            if (!$lmStart || !$lmEnd) {
                return response()->json([
                    'ok'=>false,'errors'=>['Provide either modified.start_utc/end_utc or sales.start_utc/end_utc.'],
                    'kind'=>$kind,'page'=>$page,'page_size'=>$pageSize,
                    'count'=>0,'returned'=>0,'saved'=>0,'done_kind'=>true,
                    'next_page'=>1,'next_kind'=>($kind==='active'?'inactive':'done'),
                    'throttled'=>false,'retry_after'=>0,
                ], 200);
            }
            $variants[] = ['lastModifiedStart'=>$this->toZulu($lmStart), 'lastModifiedEnd'=>$this->toZulu($lmEnd)];
        } else {
            $sdStart = $salesStart ?: $modifiedStart;
            $sdEnd   = $salesEnd   ?: $modifiedEnd;
            if (!$sdStart || !$sdEnd) {
                return response()->json([
                    'ok'=>false,'errors'=>['Provide either sales.start_utc/end_utc or modified.start_utc/end_utc.'],
                    'kind'=>$kind,'page'=>$page,'page_size'=>$pageSize,
                    'count'=>0,'returned'=>0,'saved'=>0,'done_kind'=>true,
                    'next_page'=>1,'next_kind'=>($kind==='active'?'inactive':'done'),
                    'throttled'=>false,'retry_after'=>0,
                ], 200);
            }
            // 1) UTC Z
            $variants[] = ['salesDateStart'=>$this->toZulu($sdStart), 'salesDateEnd'=>$this->toZulu($sdEnd)];
            // 2) store-local, no Z
            $variants[] = ['salesDateStart'=>$this->toLocalNoZ($sdStart, $tz), 'salesDateEnd'=>$this->toLocalNoZ($sdEnd, $tz)];
            // 3) date-only (YYYY-MM-DD)
            $variants[] = ['salesDateStart'=>$this->toDateOnly($sdStart, $tz), 'salesDateEnd'=>$this->toDateOnly($sdEnd, $tz)];
            // 4) fallback to lastModified UTC
            $variants[] = ['lastModifiedStart'=>$this->toZulu($sdStart), 'lastModifiedEnd'=>$this->toZulu($sdEnd)];
        }

        $returned = 0;
        $saved    = 0;
        $attempts = [];
        $rawType  = null;
        $status   = null;
        $diag_page = null;
        $diag_total_pages = null;

        foreach ($variants as $idx => $timeParams) {
            $q = array_merge([
                'licenseNumber' => $license,
                'page'          => $page,
                'pageSize'      => $pageSize,
            ], $timeParams);

            $endpoint = "/sales/v2/receipts/{$kind}";
            $resp = $this->metrcGet($endpoint, $q, $license);

            // Throttle/429
            if (is_object($resp) && method_exists($resp,'status') && (int)$resp->status() === 429) {
                $retry = (int)($resp->header('Retry-After') ?? 1);
                return response()->json([
                    'ok'=>true,'errors'=>[],'kind'=>$kind,'page'=>$page,'page_size'=>$pageSize,
                    'count'=>0,'returned'=>0,'saved'=>0,'done_kind'=>false,
                    'next_page'=>$page,'next_kind'=>$kind,'throttled'=>true,'retry_after'=>max(1,$retry),
                ], 200);
            }

            if (!method_exists($resp,'ok') || !$resp->ok()) {
                $attempts[] = [
                    'endpoint'      => $endpoint,
                    'variant'       => $idx+1,
                    'license_param' => true,
                    'status'        => method_exists($resp,'status')?$resp->status():0,
                    'err'           => method_exists($resp,'body')?mb_substr((string)$resp->body(),0,300):'unknown_error',
                    'q'             => $q,
                ];
                continue;
            }

            $status = $resp->status();
            $json   = $resp->json();
            $rawType = gettype($json);

            // Diagnostics for wrapped response (Data/TotalPages etc.)
            if (is_array($json) && isset($json['TotalPages'])) {
                $diag_total_pages = (int)$json['TotalPages'];
            }
            if (is_array($json) && isset($json['Page'])) {
                $diag_page = (int)$json['Page'];
            } elseif (is_array($json) && isset($json['CurrentPage'])) {
                $diag_page = (int)$json['CurrentPage'];
            }

            $rows = $this->unwrapReceipts($json);

            // If rows exist but are skeletal (Id only), hydrate each by detail call
            $needHydrate = false;
            foreach ($rows as $r) {
                if (!isset($r['ReceiptNumber']) && !isset($r['SalesDateTime']) && !isset($r['TotalPrice']) && !isset($r['TotalAmount'])) {
                    $needHydrate = true; break;
                }
            }
            if ($needHydrate) {
                $full = [];
                foreach ($rows as $r) {
                    $id = (int)($r['Id'] ?? $r['ReceiptId'] ?? $r['SaleId'] ?? 0);
                    if ($id > 0) {
                        $det = $this->metrcGetReceiptDetail($id, $license);
                        if ($det) { $full[] = $det; }
                    }
                }
                if (!empty($full)) $rows = $full;
            }

            if (count($rows) > 0) {
                $returned = count($rows);
                $saved    = $this->upsertReceipts($rows, $tz, $license);
                break; // success
            } else {
                $attempts[] = [
                    'endpoint'      => $endpoint,
                    'variant'       => $idx+1,
                    'license_param' => true,
                    'status'        => $resp->status(),
                    'rows'          => 0,
                    'q'             => $q,
                    'body_preview'  => is_string($resp->body()) ? mb_substr((string)$resp->body(),0,180) : null,
                ];
                // try next variant
            }
        }

        // Stop conditions: 0 rows OR < pageSize OR max-page guard
        $kindDone = ($returned === 0) || ($returned < $pageSize) || ($page >= self::MAX_PAGES_PER_KIND);
        $nextKind = $kindDone ? ($kind === 'active' ? 'inactive' : 'done') : $kind;
        $nextPage = $kindDone ? 1 : ($page + 1);

        $out = [
            'ok'          => true,
            'errors'      => [],
            'kind'        => $kind,
            'page'        => $page,
            'page_size'   => $pageSize,
            'count'       => (int)$saved,
            'returned'    => (int)$returned,
            'saved'       => (int)$saved,
            'done_kind'   => $kindDone,
            'next_page'   => $nextPage,
            'next_kind'   => $nextKind,
            'throttled'   => false,
            'retry_after' => 0,
        ];

        if ($returned === 0) {
            $out['diag'] = [
                'status'             => $status,
                'raw_type'           => $rawType,
                'attempts'           => $attempts,
                'used_variant'       => null,
                'endpoint'           => null,
                'metrc_page'         => $diag_page ?? $page,
                'metrc_total_pages'  => $diag_total_pages ?? 0,
            ];
        }

        return response()->json($out, 200);
    }

    /* =========================================================
     |  One-shot sync (iterate active → inactive)
     |=========================================================*/

    // GET/POST /metrc/sync-and-refresh
    public function syncAndRefresh(Request $r)
    {
        $license = $this->resolveLicense($r);
        if (!$license) return response()->json(['ok'=>false,'message'=>'licenseNumber required'], 400);

        $tz = $this->storeTz();
        $startStr = (string)($r->input('start') ?? $r->input('start_date') ?? now($tz)->toDateString());
        $endStr   = (string)($r->input('end')   ?? $r->input('end_date')   ?? $startStr);
        $start = Carbon::parse($startStr.' 00:00:00', $tz)->utc()->toIso8601String();
        $end   = Carbon::parse($endStr.' 23:59:59', $tz)->utc()->toIso8601String();

        $mode     = (string)$r->input('mode', 'sales');
        $pageSize = max(self::MIN_PAGE_SIZE, min(self::MAX_PAGE_SIZE, (int)$r->input('page_size', 10)));

        $summary = [
            'ok'=>true,
            'license'=>$license,
            'mode'=>$mode,
            'pages'=>0,
            'synced_count'=>0,
            'errors'=>[],
            'kinds_done'=>[],
        ];

        foreach (['active','inactive'] as $kind) {
            $page = 1;
            for ($guard=0; $guard<self::MAX_PAGES_PER_KIND; $guard++) {
                $req = new Request([
                    'license'=>$license,
                    'mode'=>$mode,
                    'kind'=>$kind,
                    'page'=>$page,
                    'page_size'=>$pageSize,
                    'sales'=>['start_utc'=>$start,'end_utc'=>$end],
                ]);
                $resp = $this->syncInlineChunk($req);
                $data = $resp->getData(true);

                $summary['pages']++;
                $summary['synced_count'] += (int)($data['saved'] ?? 0);
                if (!empty($data['errors'])) $summary['errors'] = array_merge($summary['errors'], (array)$data['errors']);
                if (!empty($data['done_kind'])) { $summary['kinds_done'][] = $kind; break; }
                $page = (int)($data['next_page'] ?? ($page+1));
            }
        }

        return response()->json($summary);
    }

    /* =========================================================
     |  Backfill: robust day-by-day window (90d)
     |=========================================================*/

    /** POST /metrc/sync/backfill-90d */
    public function backfillLast90Days(Request $r)
    {
        $license = $this->resolveLicense($r);
        if (!$license) return response()->json(['ok'=>false,'message'=>'licenseNumber required'], 400);

        $tz = $this->storeTz();
        $endL   = Carbon::now($tz);
        $startL = $endL->copy()->subDays(90)->startOfDay();

        $stats = $this->backfillRange($license, $startL, $endL);

        // Conservatively move the sales cursor to now (so dailyIncremental is cheap next time)
        $this->saveState($license, ['last_sales_cursor_utc' => now()->utc(), 'last_modified_cursor_utc'=> now()->utc()]);

        return response()->json(array_merge(['ok'=>true], $stats));
    }

    /** Private worker used by both backfill and "sync before count". */
    private function backfillRange(string $license, Carbon $startL, Carbon $endL, int $pageSize = 25): array
    {
        $savedTotal = 0; $pages = 0; $days = 0;

        $day = $startL->copy()->startOfDay();
        $end = $endL->copy()->endOfDay();

        while ($day->lte($end)) {
            $startUtc = $day->copy()->startOfDay()->utc()->toIso8601String();
            $endUtc   = $day->copy()->endOfDay()->utc()->toIso8601String();

            foreach (['active','inactive'] as $kind) {
                $req = new Request([
                    'license'   => $license,
                    'mode'      => 'sales',
                    'kind'      => $kind,
                    'page'      => 1,
                    'page_size' => $pageSize,
                    'sales'     => ['start_utc'=>$startUtc, 'end_utc'=>$endUtc],
                ]);
                $guard = 0;
                do {
                    $resp = $this->syncInlineChunk($req);
                    $data = $resp->getData(true);

                    $savedTotal += (int)($data['saved'] ?? 0);
                    $pages++;

                    if (!empty($data['done_kind'])) { break; }

                    $req->merge(['page'=>(int)($data['next_page'] ?? ((int)$req->input('page')+1))]);
                    $guard++;
                } while ($guard < self::MAX_PAGES_PER_KIND);
            }

            $days++;
            $day->addDay();
        }

        // Safety modified sweep for the last 48h (METRC retro edits)
        foreach (['active','inactive'] as $kind) {
            $req = new Request([
                'license'   => $license,
                'mode'      => 'modified',
                'kind'      => $kind,
                'page'      => 1,
                'page_size' => $pageSize,
                'modified'  => [
                    'start_utc'=> now()->utc()->subHours(48)->toIso8601String(),
                    'end_utc'  => now()->utc()->toIso8601String()
                ],
            ]);
            $guard = 0;
            do {
                $resp = $this->syncInlineChunk($req);
                $data = $resp->getData(true);

                $savedTotal += (int)($data['saved'] ?? 0);
                $pages++;

                if (!empty($data['done_kind'])) break;

                $req->merge(['page'=>(int)($data['next_page'] ?? ((int)$req->input('page')+1))]);
                $guard++;
            } while ($guard < self::MAX_PAGES_PER_KIND);
        }

        return [
            'range_start_local' => $startL->toDateTimeString(),
            'range_end_local'   => $endL->toDateTimeString(),
            'days_processed'    => $days,
            'pages'             => $pages,
            'saved'             => $savedTotal,
            'message'           => 'backfill complete',
        ];
    }

    /* =========================================================
     |  Daily incremental (cheap upkeep)
     |=========================================================*/

    // POST /metrc/sync/daily-incremental
    public function dailyIncremental(Request $r)
    {
        $license = $this->resolveLicense($r);
        if (!$license) return response()->json(['ok'=>false,'message'=>'licenseNumber required'], 400);

        $tz = $this->storeTz();

        $state = $this->loadState($license);

        // Sales-date catchup: from last cursor (or yesterday) to today
        $startAnchor = $state['last_sales_cursor_utc']
            ? $state['last_sales_cursor_utc']->copy()->timezone($tz)->toDateString()
            : now($tz)->subDay()->toDateString();

        $endAnchor   = now($tz)->toDateString();

        $savedTotal = 0; $pages = 0;

        // Iterate day by day (small windows are faster & friendlier to METRC)
        $day = Carbon::parse($startAnchor, $tz);
        $end = Carbon::parse($endAnchor, $tz);
        while ($day->lte($end)) {
            $startUtc = $day->copy()->startOfDay()->utc()->toIso8601String();
            $endUtc   = $day->copy()->endOfDay()->utc()->toIso8601String();

            foreach (['active','inactive'] as $kind) {
                $req = new Request([
                    'license'   => $license,
                    'mode'      => 'sales',
                    'kind'      => $kind,
                    'page'      => 1,
                    'page_size' => 25,
                    'sales'     => ['start_utc'=>$startUtc, 'end_utc'=>$endUtc],
                ]);
                $guard = 0;
                do {
                    $resp = $this->syncInlineChunk($req);
                    $data = $resp->getData(true);

                    $savedTotal += (int)($data['saved'] ?? 0);
                    $pages++;

                    if (!empty($data['done_kind'])) { break; }

                    $req->merge(['page'=>(int)($data['next_page'] ?? ((int)$req->input('page')+1))]);
                    $guard++;
                } while ($guard < 200); // safety
            }

            // Move sales cursor to the end of this day
            $this->saveState($license, ['last_sales_cursor_utc' => $day->copy()->endOfDay()->utc()]);
            $day->addDay();
        }

        // Modified catch-up for last 36 hours (in case of edits/backfills)
        $modStart = now()->utc()->subHours(36)->toIso8601String();
        $modEnd   = now()->utc()->toIso8601String();
        foreach (['active','inactive'] as $kind) {
            $req = new Request([
                'license'   => $license,
                'mode'      => 'modified',
                'kind'      => $kind,
                'page'      => 1,
                'page_size' => 25,
                'modified'  => ['start_utc'=>$modStart, 'end_utc'=>$modEnd],
            ]);
            $guard = 0;
            do {
                $resp = $this->syncInlineChunk($req);
                $data = $resp->getData(true);

                $savedTotal += (int)($data['saved'] ?? 0);
                $pages++;

                if (!empty($data['done_kind'])) break;

                $req->merge(['page'=>(int)($data['next_page'] ?? ((int)$req->input('page')+1))]);
                $guard++;
            } while ($guard < 200);
        }
        $this->saveState($license, ['last_modified_cursor_utc' => now()->utc()]);

        return response()->json([
            'ok'=>true,
            'saved'=>$savedTotal,
            'pages'=>$pages,
            'message'=>'incremental sync done',
        ]);
    }

    /* =========================================================
     |  Ensure window (called when user opens sales page)
     |=========================================================*/

    // POST /metrc/sync/ensure-window
    public function ensureWindow(Request $r)
    {
        $license = $this->resolveLicense($r);
        if (!$license) return response()->json(['ok'=>false,'message'=>'licenseNumber required'], 400);

        $tz = $this->storeTz();
        $orgId = optional(Auth::user())->organization_id;

        $startStr = (string)($r->input('start') ?? $r->input('start_date') ?? now($tz)->toDateString());
        $endStr   = (string)($r->input('end')   ?? $r->input('end_date')   ?? $startStr);

        $startL = Carbon::parse($startStr.' 00:00:00', $tz);
        $endL   = Carbon::parse($endStr.' 23:59:59', $tz);

        $need = $this->needsWindowSync($license, $startL, $endL, $orgId);

        if (!$need['should_sync']) {
            return response()->json([
                'ok'=>true,
                'action'=>'noop',
                'sales'=>$need['sales'],
                'receipts'=>$need['receipts'],
            ]);
        }

        // Cheap, bounded fetch for just this window
        $startUtc = $startL->copy()->utc()->toIso8601String();
        $endUtc   = $endL->copy()->utc()->toIso8601String();

        $savedTotal = 0; $pages = 0;
        foreach (['active','inactive'] as $kind) {
            $req = new Request([
                'license'   => $license,
                'mode'      => 'sales',
                'kind'      => $kind,
                'page'      => 1,
                'page_size' => 25,
                'sales'     => ['start_utc'=>$startUtc, 'end_utc'=>$endUtc],
            ]);
            $guard = 0;
            do {
                $resp = $this->syncInlineChunk($req);
                $data = $resp->getData(true);
                $savedTotal += (int)($data['saved'] ?? 0);
                $pages++;
                if (!empty($data['done_kind'])) { break; }
                $req->merge(['page'=>(int)($data['next_page'] ?? ((int)$req->input('page')+1))]);
                $guard++;
            } while ($guard < 200);
        }

        // Update cursors conservatively
        $this->saveState($license, ['last_sales_cursor_utc' => $endL->copy()->utc()]);

        return response()->json([
            'ok'=>true,
            'action'=>'fetched_window',
            'reasons'=>$need['reasons'],
            'saved'=>$savedTotal,
            'pages'=>$pages,
        ]);
    }

    /* =========================================================
     |  Candidates + direct link by timestamp (+amount guard)
     |=========================================================*/

    // GET /metrc/reconcile/candidates-ts?sale_id=..&minutes=0|N
    public function candidatesTs(Request $r)
    {
        $saleId  = (int)$r->query('sale_id');
        $minutes = max(0, (int)$r->query('minutes', 0));
        $orgId   = (int)($r->query('organization_id') ?? $this->currentOrgId() ?? 0);

        if (!$saleId) return response()->json(['candidates'=>[]]);

        $storeTz = $this->storeTz();
        $sale = DB::table('sales')->where('id',$saleId)->first($this->presentSaleSelect());
        if (!$sale) return response()->json(['candidates'=>[]]);

        $saleLocal = $this->saleLocalMoment($sale, $storeTz);

        $q = DB::table('metrc_receipts')->orderBy('sales_date_time');
        if ($minutes > 0) {
            $q->whereBetween('sales_date_time', [
                $saleLocal->copy()->subMinutes($minutes)->format('Y-m-d H:i:s'),
                $saleLocal->copy()->addMinutes($minutes)->format('Y-m-d H:i:s'),
            ]);
        } else {
            $key = $saleLocal->format('Y-m-d H:i:s');
            $q->whereRaw("DATE_FORMAT(sales_date_time, '%Y-%m-%d %H:%i:%s') = ?", [$key]);
        }
        if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
            $q->where('organization_id', $orgId);
        }

        $rows = $q->get(['id','metrc_id','sales_date_time','total_price','receipt_number','external_receipt_number']);
        $out = [];
        foreach ($rows as $mr) {
            $tsLocal = Carbon::parse($mr->sales_date_time, $storeTz);
            $out[] = [
                'id'                 => (int)$mr->id,
                'metrc_id'           => (int)$mr->metrc_id,
                'receipt_number'     => (string)($mr->receipt_number ?? ''),
                'external'           => (string)($mr->external_receipt_number ?? ''),
                'total_price'        => $mr->total_price !== null ? (float)$mr->total_price : null,
                'sales_date_time'    => $tsLocal->format('Y-m-d H:i:s'),
                'seconds_diff'       => (int)$tsLocal->diffInSeconds($saleLocal, false),
            ];
        }

        return response()->json(['candidates'=>$out]);
    }

    // POST /metrc/reconcile/link-ts
    public function linkTs(Request $r)
    {
        $saleId = (int)$r->input('sale_id');
        $metrcId = (int)$r->input('metrc_id');
        $mrId = (int)$r->input('metrc_receipt_id');

        if (!$mrId && $metrcId) {
            $mrId = (int) DB::table('metrc_receipts')->where('metrc_id', $metrcId)->value('id');
        }
        if (!$saleId || !$mrId) {
            return response()->json(['ok'=>false,'message'=>'sale_id and metrc_receipt_id/metrc_id are required'], 422);
        }

        $tolSec   = max(0, (int)$r->input('tolerance_seconds', 0));
        $preAbs   = $r->has('pre_abs_tolerance') ? (float)$r->input('pre_abs_tolerance') : null;
        $prePct   = $r->has('pre_pct_tolerance') ? (float)$r->input('pre_pct_tolerance') : null;
        $steal    = (bool)$r->input('steal', true);

        $storeTz = $this->storeTz();

        return DB::transaction(function () use ($saleId, $mrId, $tolSec, $preAbs, $prePct, $steal, $storeTz) {

            $sale = DB::table('sales')->where('id',$saleId)->lockForUpdate()->first($this->presentSaleSelect());
            if (!$sale) return response()->json(['ok'=>false,'message'=>'Sale not found'], 404);

            $mr = DB::table('metrc_receipts')->where('id',$mrId)->lockForUpdate()->first();
            if (!$mr) return response()->json(['ok'=>false,'message'=>'METRC receipt not found'], 404);

            $saleLocal = $this->saleLocalMoment($sale, $storeTz);
            $mrLocal   = Carbon::parse($mr->sales_date_time, $storeTz)->timezone($storeTz);

            $secDiff = abs($saleLocal->diffInSeconds($mrLocal, false));

            if ($tolSec === 0) {
                if ($saleLocal->format('Y-m-d H:i:s') !== $mrLocal->format('Y-m-d H:i:s')) {
                    return response()->json([
                        'ok'=>false,'message'=>'timestamp_mismatch',
                        'sale_ts'=>$saleLocal->format('Y-m-d H:i:s'),
                        'metrc_ts'=>$mrLocal->format('Y-m-d H:i:s'),
                    ], 409);
                }
            } elseif ($secDiff > $tolSec) {
                return response()->json([
                    'ok'=>false,'message'=>'timestamp_out_of_tolerance',
                    'seconds_diff'=>$secDiff,'tolerance_seconds'=>$tolSec,
                ], 409);
            }

            // Amount guard
            if ($preAbs !== null || $prePct !== null) {
                $salePre = $this->salePreTaxLocal($sale);
                $mrPre   = $mr->total_price !== null ? (float)$mr->total_price : null;
                if ($salePre !== null && $mrPre !== null) {
                    $delta = abs($salePre - $mrPre);
                    $pct   = ($salePre != 0.0) ? ($delta / abs($salePre)) : ($delta == 0.0 ? 0.0 : INF);
                    if ($preAbs !== null && $delta > $preAbs) {
                        return response()->json([
                            'ok'=>false,'message'=>'amount_out_of_tolerance',
                            'sale_pre'=>$salePre,'metrc_pre'=>$mrPre,'delta'=>$delta,'pre_abs_tolerance'=>$preAbs,
                        ], 409);
                    }
                    if ($prePct !== null && $pct > $prePct) {
                        return response()->json([
                            'ok'=>false,'message'=>'amount_pct_out_of_tolerance',
                            'sale_pre'=>$salePre,'metrc_pre'=>$mrPre,'pct'=>$pct,'pre_pct_tolerance'=>$prePct,
                        ], 409);
                    }
                }
            }

            // Steal if linked elsewhere
            if ($steal && Schema::hasColumn('sales','metrc_receipt_id')) {
                $otherId = DB::table('sales')->where('metrc_receipt_id',$mr->id)->value('id');
                if ($otherId && (int)$otherId !== $saleId) {
                    DB::table('sales')->where('id',$otherId)->update(['metrc_receipt_id'=>null,'updated_at'=>now()]);
                }
            }

            if (!Schema::hasColumn('sales','metrc_receipt_id')) {
                return response()->json(['ok'=>false,'message'=>'sales.metrc_receipt_id column missing'], 500);
            }

            DB::table('sales')->where('id',$saleId)->update(['metrc_receipt_id'=>$mr->id,'updated_at'=>now()]);

            return response()->json([
                'ok'=>true,
                'sale_id'=>$saleId,
                'metrc_receipt_id'=>$mr->id,
                'metrc_id'=>(int)$mr->metrc_id,
                'metrc_receipt_number'=>(string)($mr->receipt_number ?? ''),
                'metrc_pre'=>$mr->total_price !== null ? (float)$mr->total_price : null,
                'metrc_time_local'=>$mrLocal->format('m/d/Y H:i:s'),
                'seconds_diff'=>$secDiff,
            ]);
        }, 5);
    }

    /* =========================================================
     |  Batch relink by date window (exact or ± tolerance)
     |=========================================================*/

    // POST /metrc/relink/timestamp-window-inline
    public function relinkTimestampWindowInline(Request $r)
    {
        $storeTz = $this->storeTz();
        $orgId   = $this->resolveOrgId($r);

        $startStr = (string)($r->input('start') ?? $r->input('start_date') ?? now($storeTz)->toDateString());
        $endStr   = (string)($r->input('end')   ?? $r->input('end_date')   ?? $startStr);

        $startL = Carbon::parse($startStr.' 00:00:00', $storeTz);
        $endL   = Carbon::parse($endStr.' 23:59:59', $storeTz);

        $unlinkFirst = (bool)$r->input('unlink_first', false);
        $steal       = (bool)$r->input('steal', true);
        $tolSec      = max(0, (int)$r->input('tolerance_seconds', 0));
        $preAbs      = $r->has('pre_abs_tolerance') ? (float)$r->input('pre_abs_tolerance') : null;
        $prePct      = $r->has('pre_pct_tolerance') ? (float)$r->input('pre_pct_tolerance') : null;

        // Unlink existing within receipt window if requested
        $freed = 0;
        if ($unlinkFirst && Schema::hasTable('metrc_receipts') && Schema::hasColumn('sales','metrc_receipt_id')) {
            $q = DB::table('sales as s')
                ->join('metrc_receipts as mr', 'mr.id','=', 's.metrc_receipt_id')
                ->whereBetween('mr.sales_date_time', [$startL->copy()->startOfDay(), $endL->copy()->endOfDay()]);
            if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
                $q->where('mr.organization_id',$orgId);
            }
            $freed = $q->update(['s.metrc_receipt_id'=>null,'s.updated_at'=>now()]);
        }

        // Load receipts in local window (include external #)
        $rcptQ = DB::table('metrc_receipts')->whereBetween('sales_date_time', [
            $startL->copy()->startOfDay()->toDateTimeString(),
            $endL->copy()->endOfDay()->toDateTimeString(),
        ]);
        if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
            $rcptQ->where('organization_id', $orgId);
        }
        $rcpts = $rcptQ->get(['id','metrc_id','sales_date_time','total_price','receipt_number','external_receipt_number']);

        // Normalize receipt local timestamps
        $rcptPool = [];
        foreach ($rcpts as $r) {
            $t = Carbon::parse($r->sales_date_time, $storeTz)->timezone($storeTz);
            $rcptPool[] = [
                'id' => (int)$r->id,
                'metrc_id' => (int)$r->metrc_id,
                'time' => $t,
                'pre'  => $r->total_price !== null ? (float)$r->total_price : null,
                'receipt_no' => (string)($r->receipt_number ?? ''),
                'external'   => (string)($r->external_receipt_number ?? ''),
            ];
        }

        // External-number pre-pass (INV000451 / INV000451-r1 / #451)
        $taken = [];
        $pairs = [];
        $externalLinked = 0;

        foreach ($rcptPool as $cand) {
            if ($cand['external'] === '') continue;
            $saleIdFromExt = $this->parseSaleIdFromExternalString($cand['external']);
            if (!$saleIdFromExt) continue;

            $sale = $this->findSaleById($saleIdFromExt, $orgId);
            if (!$sale) continue;

            $res = $this->linkSaleToMetrcReceipt((int)$sale->id, (int)$cand['id'], $steal, false);
            if ($res['ok']) {
                $externalLinked++;
                $taken[(int)$cand['id']] = true;
                $pairs[] = [
                    'strategy'         => 'external_number',
                    'sale_id'          => (int)$sale->id,
                    'metrc_receipt_id' => (int)$cand['id'],
                    'metrc_id'         => (int)$cand['metrc_id'],
                    'receipt_number'   => (string)$cand['receipt_no'],
                    'metrc_pre'        => $cand['pre'],
                    'metrc_time_local' => $cand['time']->format('m/d/Y H:i:s'),
                    'seconds_diff'     => 0,
                    'steal'            => (bool)$res['stole'],
                    'external'         => (string)$cand['external'],
                ];
            }
        }

        // Load candidate sales (created_at UTC window plus any local timestamp fields)
        $startUtc = $startL->copy()->utc()->startOfDay();
        $endUtc   = $endL->copy()->utc()->endOfDay();

        $salesQ = DB::table('sales')
            ->when(Schema::hasColumn('sales','status'), fn($q) => $q->where('status',1))
            ->when($orgId && Schema::hasColumn('sales','organization_id'), fn($q) => $q->where('organization_id',$orgId))
            ->where(function($q) use ($startUtc, $endUtc, $startL, $endL) {
                $q->whereBetween('created_at', [$startUtc, $endUtc]);
                $localStart = $startL->copy()->startOfDay()->format('Y-m-d H:i:s');
                $localEnd   = $endL->copy()->endOfDay()->format('Y-m-d H:i:s');
                foreach (['receipt_at','receipt_time','receipt_printed_at','closed_at','completed_at','paid_at','pos_time','pos_local_time'] as $c) {
                    if (Schema::hasColumn('sales',$c)) {
                        $q->orWhereBetween($c, [$localStart, $localEnd]);
                    }
                }
            });

        $sales = $salesQ->get($this->presentSaleSelect())
                        ->sortBy(fn($s) => $this->saleLocalMoment($s, $storeTz)->timestamp)
                        ->values();

        // Build best assignment sale->receipt for remaining (not taken)
        $assign = [];
        $linked = 0; $skipped = 0;

        foreach ($sales as $s) {
            // skip if already linked from external pre-pass or already linked in DB
            if (!empty($s->metrc_receipt_id)) continue;

            $saleLocal = $this->saleLocalMoment($s, $storeTz);
            $best = null; $bestAbs = PHP_INT_MAX;

            foreach ($rcptPool as $cand) {
                if (!empty($taken[(int)$cand['id']])) continue;

                $diff = $cand['time']->diffInSeconds($saleLocal, false);
                $abs  = abs($diff);

                if ($tolSec === 0 && $abs !== 0) continue;
                if ($tolSec > 0 && $abs > $tolSec) continue;

                if ($preAbs !== null || $prePct !== null) {
                    $salePre = $this->salePreTaxLocal($s);
                    $mPre = $cand['pre'];
                    if ($salePre !== null && $mPre !== null) {
                        $delta = abs($salePre - $mPre);
                        $pct   = ($salePre != 0.0) ? ($delta / abs($salePre)) : ($delta == 0.0 ? 0.0 : INF);
                        if ($preAbs !== null && $delta > $preAbs) continue;
                        if ($prePct !== null && $pct > $prePct) continue;
                    }
                }

                if ($abs < $bestAbs) { $bestAbs = $abs; $best = $cand; }
            }

            if ($best) {
                $assign[(int)$s->id] = (int)$best['id'];
                $taken[(int)$best['id']] = true;

                $pairs[] = [
                    'strategy'         => 'timestamp',
                    'sale_id'          => (int)$s->id,
                    'metrc_receipt_id' => (int)$best['id'],
                    'metrc_id'         => (int)$best['metrc_id'],
                    'receipt_number'   => (string)$best['receipt_no'],
                    'metrc_pre'        => $best['pre'],
                    'metrc_time_local' => $best['time']->format('m/d/Y H:i:s'),
                    'seconds_diff'     => ($bestAbs === PHP_INT_MAX ? null : (int)$bestAbs),
                ];
            }
        }

        // Apply timestamp assignments
        DB::transaction(function () use ($assign, $steal, &$linked, &$skipped) {
            if ($steal && Schema::hasColumn('sales','metrc_receipt_id')) {
                $stealIds = array_values(array_unique(array_values($assign)));
                if ($stealIds) {
                    DB::table('sales')->whereIn('metrc_receipt_id', $stealIds)
                        ->update(['metrc_receipt_id'=>null,'updated_at'=>now()]);
                }
            }
            foreach ($assign as $sid=>$rid) {
                if (!Schema::hasColumn('sales','metrc_receipt_id')) { $skipped++; continue; }
                $ok = DB::table('sales')->where('id',$sid)->update(['metrc_receipt_id'=>$rid,'updated_at'=>now()]);
                $ok ? $linked++ : $skipped++;
            }
        });

        $unresolved = max(0, (int)$sales->count() - (int)$linked);

        return response()->json([
            'ok'                 => true,
            'freed'              => (int)$freed,
            'linked'             => (int)$linked + (int)$externalLinked,
            'linked_timestamp'   => (int)$linked,
            'linked_external'    => (int)$externalLinked,
            'skipped'            => (int)$skipped,
            'unresolved'         => (int)$unresolved,
            'pairs'              => $pairs,
            'start_local'        => $startL->toDateTimeString(),
            'end_local'          => $endL->toDateTimeString(),
            'tolerance_seconds'  => $tolSec,
        ]);
    }

    /* =========================================================
     |  Stats & Reporting
     |=========================================================*/

    // GET /metrc/stats/last-90d
    public function last90DayCount(Request $r)
    {
        $tz      = $this->storeTz();
        $endL    = Carbon::now($tz);
        $startL  = $endL->copy()->subDays(90);

        $orgId     = $this->resolveOrgId($r);
        $license   = $this->resolveLicense($r); // optional scope
        $finalOnly = filter_var($r->input('final_only', false), FILTER_VALIDATE_BOOL);

        // Optional: backfill before counting to guarantee complete coverage
        if ((bool)$r->input('sync_before', false) && $license) {
            $this->backfillRange($license, $startL->copy()->startOfDay(), $endL);
        }

        if (!Schema::hasTable('metrc_receipts')) {
            return response()->json(['ok'=>false,'message'=>'metrc_receipts table missing'], 500);
        }

        $q = DB::table('metrc_receipts');

        if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
            $q->where('organization_id', $orgId);
        }
        if ($license && Schema::hasColumn('metrc_receipts','license_number')) {
            $q->where('license_number', $license);
        }
        if ($finalOnly && Schema::hasColumn('metrc_receipts','is_final')) {
            $q->where('is_final', 1);
        }

        $q->whereBetween('sales_date_time', [
            $startL->format('Y-m-d H:i:s'),
            $endL->format('Y-m-d H:i:s'),
        ]);

        // Count distinct METRC receipts (defensive vs dup rows)
        $count = $q->distinct()->count('metrc_id');

        return response()->json([
            'ok'              => true,
            'count'           => (int)$count,
            'final_only'      => $finalOnly,
            'start_local'     => $startL->toDateTimeString(),
            'end_local'       => $endL->toDateTimeString(),
            'organization_id' => $orgId,
            'license'         => $license,
        ]);
    }

    /* =========================================================
     |  Utilities
     |=========================================================*/

    private function toZulu(string $iso): string
    {
        try { return Carbon::parse($iso)->utc()->format('Y-m-d\TH:i:s\Z'); }
        catch (\Throwable $e) { return $iso; }
    }
    private function toLocalNoZ(string $iso, string $tz): string
    {
        try { return Carbon::parse($iso)->timezone($tz)->format('Y-m-d\TH:i:s'); }
        catch (\Throwable $e) { return $iso; }
    }
    private function toDateOnly(string $iso, string $tz): string
    {
        try { return Carbon::parse($iso)->timezone($tz)->format('Y-m-d'); }
        catch (\Throwable $e) { return substr($iso, 0, 10); }
    }

    /** Parse sale id from strings like "INV000451", "INV-000451", "INV000451-R1", or "#451". */
    private function parseSaleIdFromExternalString(string $ext): ?int
    {
        $s = strtoupper(trim($ext));
        if ($s === '') return null;

        // #451 or "# 451"
        if (preg_match('/#\s*([0-9]{1,10})\b/', $s, $m)) {
            $id = (int)$m[1];
            return $id > 0 ? $id : null;
        }

        // INV000451, INV-000451, INV 000451, INV000451-R1
        if (preg_match('/\bINV[-_\s]*0*([0-9]{1,10})(?:[-_\s]*R[0-9]+)?\b/i', $s, $m)) {
            $id = (int)$m[1];
            return $id > 0 ? $id : null;
        }

        return null;
    }

    /** Find sale by numeric id (optionally scoped by org). */
    private function findSaleById(int $id, ?int $orgId)
    {
        $q = DB::table('sales')->where('id', $id);
        if ($orgId && Schema::hasColumn('sales','organization_id')) {
            $q->where('organization_id', $orgId);
        }
        return $q->first($this->presentSaleSelect());
    }

    /**
     * Link a sale → metrc_receipt row (steal-aware).
     * @return array{ok:bool,stole:bool}
     */
    private function linkSaleToMetrcReceipt(int $saleId, int $mrId, bool $steal = true, bool $dry = false): array
    {
        if (!Schema::hasColumn('sales','metrc_receipt_id')) {
            return ['ok'=>false,'stole'=>false];
        }
        $stole = false;

        return DB::transaction(function () use ($saleId, $mrId, $steal, $dry, &$stole) {
            $has = DB::table('metrc_receipts')->where('id', $mrId)->exists();
            if (!$has) return ['ok'=>false,'stole'=>false];

            if ($steal) {
                $otherId = DB::table('sales')->where('metrc_receipt_id', $mrId)->value('id');
                if ($otherId && (int)$otherId !== $saleId) {
                    if (!$dry) DB::table('sales')->where('id',$otherId)->update(['metrc_receipt_id'=>null,'updated_at'=>now()]);
                    $stole = true;
                }
            }

            if (!$dry) {
                DB::table('sales')->where('id',$saleId)->update(['metrc_receipt_id'=>$mrId,'updated_at'=>now()]);
            }
            return ['ok'=>true,'stole'=>$stole];
        }, 3);
    }

    /* =========================================================
     |  Legacy stubs used by UI
     |=========================================================*/

    public function ping() { return response()->json(['ok'=>true]); }
    public function receiptsWindow(Request $r) { return response()->json(['ok'=>true]); }
    public function relinkReceiptsFirst(Request $r) { return response()->json(['ok'=>true]); }
    public function candidatesByReceiptApi(Request $r) { return response()->json(['candidates'=>[]]); }
}
