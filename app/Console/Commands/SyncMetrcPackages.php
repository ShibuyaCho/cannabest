<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use App\Models\User;
use App\Inventory;
use App\Models\MetrcPackage;
use App\Models\MetrcTestResult;
use Carbon\Carbon;

class SyncMetrcPackages extends Command
{
    protected $signature   = 'metrc:sync-packages {org? : (optional) organization_id to sync (defaults to all)}';
    protected $description = 'Incrementally sync all active METRC packages into inventory & cache, and ensure lab data when missing/stale';

    // Tunables
    protected int $pageSize   = 20;        // METRC hard limit (1..20)
    protected int $sleepMicros = 120_000;  // ~120ms between requests
    protected int $labTtlDays  = 7;        // labs TTL by DateTested only

    public function handle()
    {
        $target = $this->argument('org');
        $orgIds = $target ? [(int)$target] : Organization::pluck('id')->all();

        $username = config('services.metrc.vendor_username', env('METRC_VENDOR_API_USER', '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV'));
        $baseUrl  = rtrim(config('services.metrc.base_url', env('METRC_BASE_URL', 'https://api-or.metrc.com')), '/');

        $hasDateTestedCol = Schema::hasColumn('metrc_test_results', 'DateTested');
        $labCutoff        = now()->subDays($this->labTtlDays);

        foreach ($orgIds as $orgId) {
            $org = Organization::find($orgId);
            if (! $org) { $this->error("Organization #{$orgId} not found."); continue; }

            $admin = User::where('organization_id', $orgId)->where('role_id', 2)->first();
            if (! $admin?->apiKey) { $this->error("No admin API key for org #{$orgId}."); continue; }

            $vendorKey     = $admin->apiKey;
            $licenseNumber = $org->license_number;
            if (! $licenseNumber) { $this->error("Missing license number for org #{$orgId}."); continue; }

            $this->info("→ Syncing org #{$orgId}, license {$licenseNumber}…");

            // 1) Pull all active packages (paginated, dedupe by Label)
            $all  = [];
            $page = 1;

            do {
                $resp = Http::withHeaders([
                        'Authorization' => 'Basic ' . base64_encode("{$username}:{$vendorKey}"),
                        'Accept'        => 'application/json',
                    ])
                    ->retry(3, 250)
                    ->get("{$baseUrl}/packages/v2/active", [
                        'licenseNumber' => $licenseNumber,
                        'pageNumber'    => $page,
                        'pageSize'      => $this->pageSize, // must be <= 20
                    ]);

                if (! $resp->ok()) {
                    $this->error("  API error on page {$page} (status {$resp->status()}): " . substr((string)$resp->body(), 0, 200));
                    break;
                }

                $data  = $resp->json();
                $batch = $data['Data'] ?? [];
                $this->info("   • page {$page}: " . count($batch));

                foreach ($batch as $pkg) {
                    $label = trim(preg_replace('/[[:^print:]]/', '', (string)($pkg['Label'] ?? '')), " \"'");
                    if ($label === '') continue;
                    $pkg['Label'] = $label;
                    $all[$label]  = $pkg; // dedupe by Label
                }

                $page++;
                usleep($this->sleepMicros);
            } while (!empty($batch) && count($batch) === $this->pageSize);

            $packages = collect(array_values($all));
            $this->info("   • total unique packages: {$packages->count()}");

            // 2) Upsert package cache (only when LM newer/missing)
            $incomingIds = $packages->pluck('Id')->filter()->values();
            $existingLM  = MetrcPackage::query()
                ->whereIn('Id', $incomingIds)
                ->pluck('LastModified', 'Id');

            $toUpsert = [];
            foreach ($packages as $pkg) {
                if (!isset($pkg['Id'])) continue;

                $incomingLMStr = isset($pkg['LastModified'])
                    ? Carbon::parse($pkg['LastModified'])->toDateTimeString()
                    : now()->toDateTimeString();

                $currentLMStr = $existingLM[$pkg['Id']] ?? null;

                if (!$currentLMStr || Carbon::parse($incomingLMStr)->gt(Carbon::parse($currentLMStr))) {
                    $toUpsert[] = [
                        'Id'           => $pkg['Id'],
                        'Label'        => $pkg['Label'],
                        'payload'      => json_encode($pkg),
                        'LastModified' => $incomingLMStr,
                    ];
                }
            }

            if ($toUpsert) {
                MetrcPackage::upsert($toUpsert, ['Id'], ['Label','payload','LastModified']);
                $this->info('   + upserted '.count($toUpsert).' packages into cache');
            } else {
                $this->info('   • package cache already current');
            }

            // 3) Create/Update inventory (skip trade samples; keep existing sku)
            $existingInvByLabel = Inventory::where('organization_id', $orgId)
                ->whereNotNull('Label')
                ->pluck('id', 'Label');

            foreach ($packages as $pkg) {
                $label = $pkg['Label'];
                $qty   = (float)($pkg['Quantity'] ?? 0);

                if (!empty($pkg['IsTradeSample'])) {
                    $this->info("   • skipping trade sample \"{$label}\"");
                    continue;
                }

                if (isset($existingInvByLabel[$label])) {
                    Inventory::where('id', $existingInvByLabel[$label])->update(['storeQty' => $qty]);
                } else {
                    $inv = new Inventory();
                    $inv->organization_id = $orgId;
                    $inv->Label           = $label;
                    $inv->storeQty        = $qty;
                    $inv->sku             = $label; // create-time only
                    $inv->inventory_type  = 'hold_inventories';
                    $inv->name            = data_get($pkg, 'Item.Name');
                    $inv->save();
                    $this->info("   + Inventory \"{$label}\" created");
                }
            }

            // 4) Labs: ensure present / refresh if stale (by DateTested only)
            $cachedPkgs = MetrcPackage::whereIn('Label', $packages->pluck('Label')->values())
                ->get(['Id','Label']);

            foreach ($cachedPkgs as $cached) {
                $packageId = $cached->Id;
                $label     = $cached->Label;

                $hasRecent = $hasDateTestedCol
                    ? MetrcTestResult::where('PackageId', $packageId)
                        ->whereNotNull('DateTested')
                        ->where('DateTested', '>=', $labCutoff)
                        ->exists()
                    : MetrcTestResult::where('PackageId', $packageId)->exists();

                if ($hasRecent) continue;

                $labResp = Http::withHeaders([
                        'Authorization' => 'Basic ' . base64_encode("{$username}:{$vendorKey}"),
                        'Accept'        => 'application/json',
                    ])
                    ->retry(3, 250)
                    ->get("{$baseUrl}/labtests/v2/results", [
                        'licenseNumber' => $licenseNumber,
                        'packageId'     => $packageId,
                    ]);

                if (! $labResp->ok()) {
                    $this->error("   ! failed to fetch lab data for {$label} (status {$labResp->status()})");
                    continue;
                }

                $rows = $labResp->json()['Data'] ?? [];

                MetrcTestResult::where('PackageId', $packageId)->delete();

                $insert = [];
                foreach ($rows as $r) {
                    if (!isset($r['TestResultLevel']) || !is_numeric($r['TestResultLevel'])) continue;

                    $row = [
                        'PackageId'                => $r['PackageId'] ?? $packageId,
                        'TestTypeName'             => $r['TestTypeName'] ?? null,
                        'TestResultLevel'          => $r['TestResultLevel'],
                        'LabFacilityName'          => $r['LabFacilityName'] ?? null,
                        'LabFacilityLicenseNumber' => $r['LabFacilityLicenseNumber'] ?? null,
                    ];
                    if ($hasDateTestedCol && isset($r['DateTested'])) {
                        $row['DateTested'] = Carbon::parse($r['DateTested']);
                    }
                    $insert[] = $row;
                }

                if ($insert) MetrcTestResult::insert($insert);
                $this->info("   + labs refreshed for {$label} (".count($insert)." rows)");
                usleep($this->sleepMicros);
            }

            $this->info("✔ Sync complete for org #{$orgId}.");
        }

        return 0;
    }
}
