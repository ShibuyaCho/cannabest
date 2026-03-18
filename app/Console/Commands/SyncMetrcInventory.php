<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Models\User;
use App\Inventory;
use App\Models\MetrcPackage;
use App\Models\MetrcTestResult;
use Carbon\Carbon;

class SyncMetrcInventory extends Command
{
    protected $signature   = 'metrc:sync-inventory {org? : (optional) organization_id to sync (defaults to 1)}';
    protected $description = 'Refresh METRC cache for labels currently in your inventory (incremental, no truncates)';

    // Hard-code your vendor API username (NOT the org/user API key).
    private string $vendorUsername = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    protected int $sleepMicros = 120_000; // ~120ms between requests
    protected int $labTtlDays  = 7;       // refresh labs if DateTested is older than this

    public function handle()
    {
        $orgId = (int)($this->argument('org') ?? 1);
        $org   = Organization::find($orgId);
        if (! $org) { $this->error("Organization #{$orgId} not found."); return 1; }

        // Get the admin user for the org the same way your original code does
        $admin = User::where('organization_id', $org->id)->where('role_id', 2)->first();
        if (! $admin || ! $admin->apiKey) {
            $this->error("No admin API key for org #{$org->id}.");
            return 1;
        }

        // Org-specific variables (kept exactly like your style)
        $vendorKey     = $admin->apiKey;               // Basic auth "password"
        $licenseNumber = $org->license_number;         // required by METRC
        $baseUrl       = rtrim($this->baseUrlForOrg($org), '/');

        if (! $licenseNumber) { $this->error("Missing license number on org #{$org->id}."); return 1; }

        // Labels to refresh: from THIS org only
        $labels = Inventory::where('organization_id', $org->id)
            ->whereNotNull('Label')
            ->where('Label', '!=', '')
            ->pluck('Label')
            ->filter()
            ->map(fn($l) => Str::upper(trim((string)$l)))
            ->unique()
            ->values()
            ->all();

        if (empty($labels)) {
            $this->info("No inventory labels to sync for org #{$org->id}.");
            return 0;
        }

        $this->info("→ Syncing inventory-linked METRC data for org #{$org->id} (".count($labels)." labels)…");

        $synced = 0;
        $cutoff = now()->subDays($this->labTtlDays);

        // Column existence checks (to avoid where clauses on non-existent columns)
        $hasDateTestedCol = Schema::hasColumn('metrc_test_results', 'DateTested');
        $hasCreatedCol    = Schema::hasColumn('metrc_test_results', 'created_at');
        $hasUpdatedCol    = Schema::hasColumn('metrc_test_results', 'updated_at');

        foreach ($labels as $label) {
            $this->info("   • {$label}");

            // Prefer package cache
            $cached = MetrcPackage::where('Label', $label)->first();
            $needsRefresh = true;

            if ($cached && !empty($cached->LastModified)) {
                try {
                    $needsRefresh = Carbon::parse($cached->LastModified)->lt(now()->subDays(3));
                } catch (\Throwable) {
                    $needsRefresh = true;
                }
            } elseif (! $cached) {
                $needsRefresh = true;
            }

            if ($needsRefresh) {
                // GET /packages/v2/{label}?licenseNumber=...
                $resp = Http::withBasicAuth($this->vendorUsername, $vendorKey)
                    ->acceptJson()
                    ->retry(2, 250)
                    ->get("{$baseUrl}/packages/v2/{$label}", [
                        'licenseNumber' => $licenseNumber,
                    ]);

                if (! $resp->ok()) {
                    $this->emitHttpError('package by label', $resp->status(), $resp->body(), $baseUrl, $licenseNumber);
                    continue;
                }

                $p  = $resp->json();
                if (!is_array($p) || !isset($p['Id'])) {
                    $this->warn('     - unexpected package payload; skipping cache update');
                } else {
                    $lm = isset($p['LastModified'])
                        ? Carbon::parse($p['LastModified'])->toDateTimeString()
                        : now()->toDateTimeString();

                    MetrcPackage::updateOrCreate(
                        ['Id' => $p['Id']],
                        [
                            'Label'        => trim((string)($p['Label'] ?? $label)),
                            'payload'      => json_encode($p),
                            'LastModified' => $lm,
                        ]
                    );

                    $this->info("     + package cache updated");
                    $cached = MetrcPackage::where('Id', $p['Id'])->first();
                }
            } else {
                $this->info("     • using cached package");
            }

            if (!$cached || empty($cached->Id)) {
                $this->info("     • no package Id; skipping labs");
                $synced++; usleep($this->sleepMicros); continue;
            }

            // Labs: missing or stale — choose condition based on what columns exist
            if ($hasDateTestedCol) {
                $hasRecentLabs = MetrcTestResult::where('PackageId', $cached->Id)
                    ->whereNotNull('DateTested')
                    ->where('DateTested', '>=', $cutoff)
                    ->exists();
            } elseif ($hasCreatedCol) {
                $hasRecentLabs = MetrcTestResult::where('PackageId', $cached->Id)
                    ->where('created_at', '>=', $cutoff)
                    ->exists();
            } else {
                // No usable date columns: consider "any rows" as present
                $hasRecentLabs = MetrcTestResult::where('PackageId', $cached->Id)->exists();
            }

            if (! $hasRecentLabs) {
                // GET /labtests/v2/results?licenseNumber=...&packageId=...
                $labResp = Http::withBasicAuth($this->vendorUsername, $vendorKey)
                    ->acceptJson()
                    ->retry(2, 250)
                    ->get("{$baseUrl}/labtests/v2/results", [
                        'licenseNumber' => $licenseNumber,
                        'packageId'     => $cached->Id,
                    ]);

                if (! $labResp->ok()) {
                    $this->emitHttpError('lab results', $labResp->status(), $labResp->body(), $baseUrl, $licenseNumber);
                } else {
                    $rows = $labResp->json()['Data'] ?? [];

                    MetrcTestResult::where('PackageId', $cached->Id)->delete();

                    $insert = [];
                    foreach ($rows as $r) {
                        if (!isset($r['TestResultLevel']) || !is_numeric($r['TestResultLevel'])) continue;
                        $row = [
                            'PackageId'       => $r['PackageId'] ?? $cached->Id,
                            'TestTypeName'    => $r['TestTypeName'] ?? null,
                            'TestResultLevel' => $r['TestResultLevel'],
                        ];
                        if (isset($r['DateTested']) && $hasDateTestedCol) {
                            $row['DateTested'] = Carbon::parse($r['DateTested']);
                        }
                        // Only set timestamps if columns actually exist
                        if ($hasCreatedCol) $row['created_at'] = now();
                        if ($hasUpdatedCol) $row['updated_at'] = now();

                        $insert[] = $row;
                    }
                    if (!empty($insert)) MetrcTestResult::insert($insert);
                    $this->info("     + labs refreshed (" . count($insert) . " rows)");
                }
            } else {
                $this->info("     • labs current");
            }

            $synced++;
            usleep($this->sleepMicros);
        }

        $this->info("✅ Synced/verified {$synced} label(s) for org #{$org->id}.");
        return 0;
    }

    private function baseUrlForOrg($org): string
    {
        // Prefer org field if you have it; else infer from state; else default to OR
        if (!empty($org->metrc_base_url)) return $org->metrc_base_url;

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

    private function emitHttpError(string $what, int $status, $body, string $baseUrl, string $license): void
    {
        $safeBody = is_string($body) ? mb_substr($body, 0, 400) : json_encode($body);
        $this->error("     - {$what} request FAILED ({$status})");
        $this->line("       baseUrl={$baseUrl}");
        $this->line("       license={$license}");
        if ($status === 401) {
            $this->warn('       401 usually means: wrong vendor username, wrong org user API key, or wrong state/base URL.');
        }
        $this->line("       body: {$safeBody}");
    }
}
