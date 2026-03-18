<?php

namespace App\Jobs;

use App\Http\Controllers\SalesMetrcReconcileController;
use App\Models\MetrcReceipt;
use App\Sale;
use App\Support\MetrcLocal;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetrcSyncAndReconcile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<string,mixed> */
    public array $payload;

    /**
     * @param array<string,mixed> $payload
     *
     * Expected keys:
     * - job_id (string)
     * - organization_id (?int)
     * - license (string)
     * - start (ISO8601, store tz)
     * - end   (ISO8601, store tz)
     * - minutes (int) tolerance (minutes)
     * - relink (bool)
     * - fresh (bool)     // ignored here (MetrcLocal always upserts)
     * - unlink_first (bool)
     * - seed_last_90 (bool)
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->onQueue(config('queue.default', 'default'));
    }

    public function handle(): void
    {
        /** @var SalesMetrcReconcileController $svc */
        $svc   = app(SalesMetrcReconcileController::class);
        $jobId = (string)($this->payload['job_id'] ?? (string) \Str::uuid());
        $orgId = $this->payload['organization_id'] ?? null;
        $lic   = (string)($this->payload['license'] ?? '');
        $tz    = $svc->storeTz();

        $start = Carbon::parse((string)$this->payload['start'], $tz);
        $end   = Carbon::parse((string)$this->payload['end'],   $tz);

        $unlinkFirst = (bool)($this->payload['unlink_first'] ?? false);
        $seedLast90  = (bool)($this->payload['seed_last_90'] ?? false);
        $relink      = (bool)($this->payload['relink'] ?? true);
        $tolSeconds  = max(0, min(60 * 60 * 8, (int)(($this->payload['minutes'] ?? 30) * 60)));

        // mark active per-org (so controller can block concurrent)
        if ($orgId) {
            Cache::put("metrc_sync:active:org:{$orgId}", ['job_id'=>$jobId], now()->addMinutes(30));
        }

        // initial progress
        $svc->progressSet($jobId, 'running', 'Starting…', 3, [
            'window'  => ['start'=>$start->toIso8601String(), 'end'=>$end->toIso8601String()],
            'license' => $lic,
        ], $orgId);

        try {
            // Optional fast backfill by LastModified (last ~90 days)
            if ($seedLast90) {
                $svc->progressSet($jobId, 'running', 'Seeding last ~90 days (modified window)…', 5, [], $orgId);

                // Use MetrcLocal directly; it will pick org admin apiKey if available.
                $nowUtc = now('UTC');
                $lmStart = $nowUtc->copy()->subDays(90);
                MetrcLocal::receiptsSinceLastModified($lic, $lmStart, $nowUtc, $orgId, null);
            }

            // Refresh receipts in the chosen window (by sales date)
            $svc->progressSet($jobId, 'running', 'Pulling METRC receipts (window)…', 10, [], $orgId);
            $pull = MetrcLocal::receiptsBySalesDate(
                licenseNumber:  $lic,
                startSalesTz:   $start->copy()->startOfDay(),
                endSalesTz:     $end->copy()->endOfDay(),
                facilityTz:     $tz,
                organizationId: $orgId,
                vendorKey:      null // MetrcLocal will resolve: user->apiKey OR org admin->apiKey OR env
            );

            // Optional unlink first (surgical reset for this window)
            if ($unlinkFirst && Schema::hasColumn('sales','metrc_receipt_id')) {
                $svc->progressSet($jobId, 'running', 'Unlinking any previously linked sales (window)…', 62, [], $orgId);

                $sUtc = $start->copy()->utc()->startOfDay()->toDateTimeString();
                $eUtc = $end->copy()->utc()->endOfDay()->toDateTimeString();

                DB::table('sales')
                    ->when($orgId && Schema::hasColumn('sales','organization_id'), fn($q)=>$q->where('organization_id', $orgId))
                    ->when(Schema::hasColumn('sales','status'), fn($q)=>$q->where('status', 1))
                    ->whereBetween('sales.created_at', [$sUtc, $eUtc])
                    ->update(['metrc_receipt_id' => null]);
            }

            // Link by nearest timestamp
            if ($relink) {
                $svc->progressSet($jobId, 'running', 'Linking sales to METRC by timestamp…', 70, [
                    'tolerance_seconds' => $tolSeconds
                ], $orgId);

                [$linked, $skipped, $unresolved] =
                    $svc->relinkTimestampWindowCore($lic, $start->copy(), $end->copy(), $tolSeconds, $orgId, $jobId);

                $svc->tracePush($jobId, 'link.summary', compact('linked','skipped','unresolved'), $orgId);
            }

            // Build compact UI patch
            $svc->progressSet($jobId, 'running', 'Preparing UI patch…', 97, [], $orgId);
            $salesPatch = $this->buildSalesPatch($svc, $start->copy(), $end->copy(), $orgId);

            // Done
            $payload = [
                'status'   => 'done',
                'message'  => 'Sync complete.',
                'pct'      => 100,
                'sales'    => $salesPatch,
                'window'   => ['start'=>$start->toIso8601String(), 'end'=>$end->toIso8601String()],
                'license'  => $lic,
                'pull'     => $pull,
            ];

            Cache::put(SalesMetrcReconcileController::progressKey($jobId, $orgId), $payload, now()->addMinutes(30));
            Cache::put(SalesMetrcReconcileController::progressKeyAlias($jobId),   $payload, now()->addMinutes(30));
            if ($orgId) Cache::forget("metrc_sync:active:org:{$orgId}");

            $svc->tracePush($jobId, 'job.done', [
                'patch_sales' => count($salesPatch),
                'pull'        => $pull,
            ], $orgId);

        } catch (\Throwable $e) {
            Log::error('MetrcSyncAndReconcile failed', ['err'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            $svc->progressSet($jobId, 'error', $e->getMessage(), 0, [], $orgId);
            if ($orgId) Cache::forget("metrc_sync:active:org:{$orgId}");
        }
    }

    /**
     * Compact patch so the front-end can immediately show linked status,
     * METRC receipt #, and METRC pre-tax for sales in the window.
     *
     * @return array<int, array<string,mixed>>
     */
    protected function buildSalesPatch(SalesMetrcReconcileController $svc, Carbon $from, Carbon $to, ?int $orgId): array
    {
        $fromStr = $from->copy()->setTimezone($svc->salesAssumeTz())->toDateTimeString();
        $toStr   = $to->copy()->setTimezone($svc->salesAssumeTz())->toDateTimeString();

        $sel = [
            'sales.id as sale_id',
            'sales.metrc_receipt_id as metrc_receipt_local_id',
        ];

        if (Schema::hasTable('metrc_receipts')) {
            $sel[] = 'metrc_receipts.metrc_id';
            $sel[] = 'metrc_receipts.receipt_number';
            $sel[] = 'metrc_receipts.total_price';
        }

        $q = DB::table('sales')
            ->leftJoin('metrc_receipts', 'sales.metrc_receipt_id', '=', 'metrc_receipts.id')
            ->whereBetween('sales.created_at', [$fromStr, $toStr]);

        if ($orgId && Schema::hasColumn('sales','organization_id')) {
            $q->where('sales.organization_id', $orgId);
        }
        if (Schema::hasColumn('sales','status')) {
            $q->where('sales.status', 1);
        }

        $rows = $q->get($sel);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'sale_id'                 => (int)$r->sale_id,
                'metrc_receipt_local_id'  => $r->metrc_receipt_local_id ? (int)$r->metrc_receipt_local_id : null,
                'metrc_id'                => isset($r->metrc_id) ? (int)$r->metrc_id : null,
                'metrc_receipt_number'    => (string)($r->receipt_number ?? ''),
                'metrc_pre'               => isset($r->total_price) ? (float)$r->total_price : null,
                'metrc_receipt_id'        => $r->metrc_receipt_local_id ? (int)$r->metrc_receipt_local_id : null, // alias for front-end
            ];
        }
        return $out;
    }
}
