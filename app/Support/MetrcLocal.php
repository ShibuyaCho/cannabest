<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetrcLocal
{
    /** Minimum spacing between outbound METRC calls (ms) */
    protected const DEFAULT_MIN_INTERVAL_MS = 350;

    /** Your *hardcoded* vendor username (same as your packages command), with optional env override */
    protected const HARDCODED_VENDOR_USERNAME = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    /** Keep a timestamp of the last call to respect a global throttle */
    protected static float $lastCallAt = 0.0;

    /**
     * Fetch ONE page from Oregon GET endpoints:
     *   /sales/v2/receipts/{active|inactive}
     *
     * Returns:
     *  [
     *    'count'      => int,
     *    'done'       => bool,
     *    'errors'     => string[],
     *    // when throttled:
     *    'throttled'  => true,
     *    'retry_after'=> int,
     *  ]
     */
    public static function receiptsPage(
        string  $kind,                   // 'active' | 'inactive'
        string  $licenseNumber,
        ?Carbon $salesStartUtc,
        ?Carbon $salesEndUtc,
        ?Carbon $lmStartUtc,
        ?Carbon $lmEndUtc,
        int     $page,
        int     $pageSize,
        ?int    $organizationId = null,
        ?string $explicitVendorKey = null
    ): array {
        $errors = [];

        // ---- Credentials (username = vendor key; password = user's apiKey) ----
        try {
            $username  = self::vendorUsername();                          // hardcoded (or env) — NEVER empty
            $vendorKey = self::vendorKey($explicitVendorKey, $organizationId); // ALWAYS pulled from user/org; throws if missing
        } catch (\Throwable $e) {
            return ['count'=>0,'done'=>true,'errors'=>[$e->getMessage()]];
        }

        $base     = self::baseUrl();
        $endpoint = rtrim($base, '/') . '/sales/v2/receipts/' . $kind;

        $qs = [
            'licenseNumber' => $licenseNumber,
            'pageSize'      => min(20, max(1, $pageSize)),
            'pageNumber'    => max(1, $page),
        ];

        // Exactly one filter set per API rules
        if ($salesStartUtc && $salesEndUtc && !$lmStartUtc && !$lmEndUtc) {
            $qs['salesDateStart'] = self::fmtZ($salesStartUtc);
            $qs['salesDateEnd']   = self::fmtZ($salesEndUtc);
        } elseif ($lmStartUtc && $lmEndUtc && !$salesStartUtc && !$salesEndUtc) {
            $qs['lastModifiedStart'] = self::fmtZ($lmStartUtc);
            $qs['lastModifiedEnd']   = self::fmtZ($lmEndUtc);
        } else {
            return [
                'count'  => 0,
                'done'   => true,
                'errors' => ['Provide either salesDateStart/End OR lastModifiedStart/End (not both).'],
            ];
        }

        // --- Call API (one-shot; surface 429 cleanly) ---
        self::throttleGlobal();

        $resp   = Http::withHeaders([
                        'Authorization' => 'Basic ' . base64_encode("{$username}:{$vendorKey}"),
                        'Accept'        => 'application/json',
                    ])
                    ->timeout(45)
                    ->get($endpoint, $qs);

        $status = $resp->status();

        if ($status === 429) {
            $retry = self::parseRetryAfterSeconds($resp->header('Retry-After'));
            return [
                'count'       => 0,
                'done'        => false,
                'errors'      => ['throttled 429'],
                'throttled'   => true,
                'retry_after' => $retry > 0 ? $retry : 12,
            ];
        }

        if (!$resp->ok()) {
            $snippet = substr((string)$resp->body(), 0, 220);
            $errors[] = "HTTP {$status}: {$snippet}";
            return ['count'=>0,'done'=>true,'errors'=>$errors];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            return ['count'=>0,'done'=>true,'errors'=>['Non-JSON response']];
        }

        $data  = Arr::get($json, 'Data', []);
        $count = is_array($data) ? count($data) : 0;

        if ($count > 0) {
            self::upsertReceipts($data, $organizationId);
        }

        return [
            'count'  => $count,
            'done'   => ($count < $qs['pageSize']),
            'errors' => $errors,
        ];
    }

    /* ------------------ Persistence ------------------ */

    protected static function upsertReceipts(array $rows, ?int $orgId = null): void
    {
        if (!Schema::hasTable('metrc_receipts')) return;

        $batch = [];
        foreach ($rows as $r) {
            $norm = self::normalizeReceipt($r);
            if (!$norm) continue;

            $row = [
                'metrc_id'                => $norm['metrc_id'],
                'receipt_number'          => $norm['receipt_number'],
                'external_receipt_number' => $norm['external_receipt_number'],
                'sales_date_time'         => $norm['sales_date_time'],
                'total_price'             => $norm['total_price'],
                'is_final'                => $norm['is_final'],
                'last_modified'           => $norm['last_modified'],
                'payload'                 => json_encode($r),
            ];
            if ($orgId && Schema::hasColumn('metrc_receipts', 'organization_id')) {
                $row['organization_id'] = $orgId;
            }
            $batch[] = $row;
        }

        if (!$batch) return;

        foreach (array_chunk($batch, 300) as $chunk) {
            foreach ($chunk as $row) {
                $keys = ['metrc_id'];
                if ($orgId && Schema::hasColumn('metrc_receipts', 'organization_id')) {
                    $keys[] = 'organization_id';
                }
                $updates = $row; foreach ($keys as $k) unset($updates[$k]);

                DB::table('metrc_receipts')->updateOrInsert(
                    Arr::only($row, $keys),
                    $updates
                );
            }
        }
    }

    protected static function normalizeReceipt(array $r): ?array
    {
        $id = Arr::get($r, 'Id');
        if (!is_numeric($id)) return null;

        $receipt = trim((string)($r['ReceiptNumber'] ?? ''));
        $ext     = trim((string)($r['ExternalReceiptNumber'] ?? ''));

        $salesUtc = null;
        if (!empty($r['SalesDateTime'])) {
            try { $salesUtc = Carbon::parse((string)$r['SalesDateTime'], 'UTC')->utc()->toDateTimeString(); }
            catch (\Throwable $e) { $salesUtc = null; }
        }

        $lmUtc = null;
        if (!empty($r['LastModified'])) {
            try { $lmUtc = Carbon::parse((string)$r['LastModified'], 'UTC')->utc()->toDateTimeString(); }
            catch (\Throwable $e) { $lmUtc = null; }
        }

        return [
            'metrc_id'                => (int)$id,
            'receipt_number'          => $receipt !== '' ? $receipt : null,
            'external_receipt_number' => $ext !== '' ? $ext : null,
            'sales_date_time'         => $salesUtc,
            'total_price'             => is_numeric($r['TotalPrice'] ?? null) ? (float)$r['TotalPrice'] : null,
            'is_final'                => (int) (!!($r['IsFinal'] ?? false)),
            'last_modified'           => $lmUtc,
        ];
    }

    /* ------------------ HTTP helpers ------------------ */

    protected static function throttleGlobal(): void
    {
        $minMs   = (int) (env('METRC_MIN_INTERVAL_MS', self::DEFAULT_MIN_INTERVAL_MS));
        $now     = microtime(true);
        $deltaMs = ($now - self::$lastCallAt) * 1000.0;
        if (self::$lastCallAt > 0 && $deltaMs < $minMs) {
            $sleep = (int) (($minMs - $deltaMs) * 1000);
            if ($sleep > 0) usleep($sleep);
        }
        self::$lastCallAt = microtime(true);
    }

    protected static function parseRetryAfterSeconds(?string $v): int
    {
        if (!$v) return 0;
        $s = trim($v);
        if ($s === '') return 0;
        if (ctype_digit($s)) return max(0, (int)$s);
        try {
            $t = Carbon::parse($s);
            $diff = now()->diffInSeconds($t, false);
            return $diff > 0 ? $diff : 0;
        } catch (\Throwable $e) { return 0; }
    }

    /* ------------------ Credentials / config ------------------ */

    protected static function baseUrl(): string
    {
        return rtrim((string)(env('METRC_BASE_URL', 'https://api-or.metrc.com')), '/');
    }

    /**
     * Vendor API username:
     *  - Prefer env('METRC_VENDOR_API_USER') if set
     *  - Else use the *hardcoded* key used across your project
     */
    protected static function vendorUsername(): string
    {
        $u = trim((string) env('METRC_VENDOR_API_USER', ''));
        if ($u !== '') return $u;
        return self::HARDCODED_VENDOR_USERNAME;
    }

    /**
     * Password for Basic auth:
     *  - explicit (from controller) if provided
     *  - else requesting user's apiKey
     *  - else org admin's apiKey
     *  - else THROW (never silently fall back to blank/config)
     */
    protected static function vendorKey(?string $explicit, ?int $orgId): string
    {
        if ($explicit && trim($explicit) !== '') {
            return (string) $explicit;
        }

        try {
            $u = Auth::user();
            if ($u && !empty($u->apiKey)) {
                return (string) $u->apiKey;
            }
        } catch (\Throwable $e) {}

        if ($orgId) {
            try {
                $admin = \App\Models\User::where('organization_id', $orgId)->where('role_id', 2)->first();
                if ($admin && !empty($admin->apiKey)) {
                    return (string) $admin->apiKey;
                }
            } catch (\Throwable $e) {}
        }

        // Hard failure: do NOT return empty string or config fallback.
        throw new \RuntimeException('Missing METRC credentials: user apiKey (password) not found for this request and no explicit key was provided.');
    }

    protected static function fmtZ(Carbon $c): string
    {
        return $c->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
