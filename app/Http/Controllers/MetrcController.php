<?php

namespace App\Http\Controllers;

use App\Models\MetrcPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetrcController extends Controller
{
    // Hard-code your vendor username here (Basic Auth "username")
    private string $hardcodedVendorUsername = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';

    public function status()
    {
        $orgId = Auth::user()?->organization_id;

        if (!$orgId) {
            return response()->json([
                'running'      => false,
                'progress'     => null,
                'last_sync_at' => null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
        }

        $runningKey = "metrc:sync:running:{$orgId}";
        $lastKey    = "metrc:sync:last:{$orgId}";
        $progKey    = "metrc:sync:progress:{$orgId}";

        $running  = (bool) Cache::get($runningKey, false);
        $lastISO  = Cache::get($lastKey);
        $progress = Cache::get($progKey, null);

        if ($running || $lastISO) {
            return response()->json([
                'running'      => $running,
                'progress'     => $progress,
                'last_sync_at' => $lastISO,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
        }

        $path = $this->latestMetrcLogPath($orgId);
        if ($path && File::exists($path)) {
            $mtime   = File::lastModified($path);
            $writing = (time() - $mtime) <= 5;
            $lastLineTs = $this->lastLineTimestamp($path) ?: Carbon::createFromTimestamp($mtime);

            return response()->json([
                'running'      => $writing,
                'progress'     => null,
                'last_sync_at' => $lastLineTs ? $lastLineTs->toIso8601String() : null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
        }

        $legacy = $this->legacyMetrcLogPath();
        if ($legacy && File::exists($legacy)) {
            $mtime   = File::lastModified($legacy);
            $writing = (time() - $mtime) <= 5;
            $lastLineTs = $this->lastLineTimestamp($legacy) ?: Carbon::createFromTimestamp($mtime);

            return response()->json([
                'running'      => $writing,
                'progress'     => null,
                'last_sync_at' => $lastLineTs ? $lastLineTs->toIso8601String() : null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
        }

        return response()->json([
            'running'      => false,
            'progress'     => null,
            'last_sync_at' => null,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
    }

    public function getActivePackages(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        $org = $user->organization;

        $vendorUsername = $this->hardcodedVendorUsername; // always the same
        $vendorKey      = $user->apiKey;                 // password
        $licenseNumber  = optional($org)->license_number;
        $baseUrl        = rtrim(optional($org)->metrc_base_url ?: 'https://api-or.metrc.com', '/');

        if (!$vendorKey || !$licenseNumber) return [];

        try {
            $resp = Http::withBasicAuth($vendorUsername, $vendorKey)
                ->acceptJson()
                ->get("{$baseUrl}/packages/v2/active", [
                    'licenseNumber' => $licenseNumber,
                ]);

            if ($resp->failed()) {
                Log::error('MetrcController: Active packages API error', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return [];
            }

            $json = $resp->json();
            return is_array($json) ? ($json['Data'] ?? $json) : [];
        } catch (\Throwable $e) {
            Log::error('MetrcController Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    public function getDeliveryPackages($id, Request $request)
    {
        $user = Auth::user();
        $org  = $user?->organization;

        $vendorUsername = $this->hardcodedVendorUsername;
        $vendorKey      = $user?->apiKey;
        $licenseNumber  = $request->query('licenseNumber');
        $baseUrl        = rtrim(optional($org)->metrc_base_url ?: 'https://api-or.metrc.com', '/');

        if (!$vendorKey || !$licenseNumber) {
            return response()->json(['error' => 'Missing API credentials or license number'], 400);
        }

        $resp = Http::withBasicAuth($vendorUsername, $vendorKey)
            ->acceptJson()
            ->get("{$baseUrl}/transfers/v2/deliveries/{$id}/packages", [
                'licenseNumber' => $licenseNumber,
            ]);

        if ($resp->failed()) {
            return response()->json([
                'error'  => 'Metrc API error',
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ], $resp->status());
        }

        return response()->json($resp->json());
    }

    public function getByLabel($label)
    {
        $orgId = Auth::user()?->organization_id;
        $query = MetrcPackage::query()->where('data->PackageLabel', $label);

        if (Schema::hasColumn((new MetrcPackage)->getTable(), 'organization_id') && $orgId) {
            $query->where('organization_id', $orgId);
        }

        $record = $query->first();
        return $record ? response()->json($record->data) : response()->json(null, 404);
    }

    public function syncNow(Request $request)
    {
        $orgId = $request->user()?->organization_id;
        if (!$orgId) return response()->json(['message' => 'Not authorized'], 403);

        $orgArg = ' ' . (int)$orgId;
        $php     = config('app.php_cli', env('PHP_CLI', 'php'));
        $base    = base_path();
        $artisan = $base . DIRECTORY_SEPARATOR . 'artisan';

        $packagesLog = storage_path("logs/metrc-packages-org-{$orgId}.log");
        $syncLog     = storage_path("logs/metrc-sync-org-{$orgId}.log");

        exec("cd {$base} && nohup {$php} {$artisan} metrc:sync-packages{$orgArg} >> {$packagesLog} 2>&1 & echo $!");
        exec("cd {$base} && nohup {$php} {$artisan} metrc:sync-inventory{$orgArg} >> {$syncLog} 2>&1 & echo $!");

        return response()->json(['message' => "METRC sync started for org {$orgId}."]);
    }

    private function latestMetrcLogPath(?int $orgId): ?string
    {
        if ($orgId) {
            $orgSync = storage_path("logs/metrc-sync-org-{$orgId}.log");
            if (File::exists($orgSync)) return $orgSync;
            $orgPkg = storage_path("logs/metrc-packages-org-{$orgId}.log");
            if (File::exists($orgPkg)) return $orgPkg;
        }
        return $this->legacyMetrcLogPath();
    }

    private function legacyMetrcLogPath(): ?string
    {
        $fixed = storage_path('logs/metrc-sync.log');
        if (File::exists($fixed)) return $fixed;

        $candidates = array_merge(
            glob(storage_path('logs/metrc-*.log')) ?: [],
            glob(storage_path('logs/metrc_sync-*.log')) ?: [],
            glob(storage_path('logs/metrc-sync-*.log')) ?: []
        );
        if (empty($candidates)) return null;
        usort($candidates, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return $candidates[0];
    }

    private function lastLineTimestamp(string $path): ?Carbon
    {
        if (!File::exists($path)) return null;
        $fh = fopen($path, 'rb');
        if (!$fh) return null;

        fseek($fh, 0, SEEK_END);
        $filesize = ftell($fh);
        if ($filesize === 0) { fclose($fh); return null; }

        $buffer = '';
        $pos = $filesize;
        while ($pos > 0) {
            $chunkSize = 4096;
            $readPos   = max(0, $pos - $chunkSize);
            $len       = $pos - $readPos;
            fseek($fh, $readPos, SEEK_SET);
            $buffer = fread($fh, (int)$len) . $buffer;
            $pos    = $readPos;
            if (strpos($buffer, "\n") !== false) break;
        }
        fclose($fh);

        $lines = array_values(array_filter(explode("\n", trim($buffer))));
        $last  = end($lines);
        if (!$last) return null;

        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $last, $m)) {
            return Carbon::parse($m[1], config('app.timezone'));
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+\-]\d{2}:\d{2}))/', $last, $m)) {
            return Carbon::parse($m[1]);
        }
        return null;
    }
}
