<?php

namespace App\Jobs;

use Database\Seeders\ImportInventoriesSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class RunInventoriesSeederJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries   = 1;

    protected int $orgId;
    protected string $csvPath;
    protected string $cacheKey;

    public function __construct(int $orgId, string $csvPath, string $cacheKey)
    {
        $this->orgId    = $orgId;
        $this->csvPath  = $csvPath;
        $this->cacheKey = $cacheKey;
    }

    public function handle(): void
    {
        $this->put([
            'status'    => 'running',
            'phase'     => 'seeding',
            'processed' => 0,
        ]);

        Config::set('imports.organization_id', $this->orgId);
        Config::set('imports.inventory_csv_path', $this->csvPath);

        $progress = function (string $evt, array $payload = []) {
            $state = Cache::get($this->cacheKey, []);
            switch ($evt) {
                case 'progress':
                    $state['processed'] = (int) ($payload['processed'] ?? $state['processed'] ?? 0);
                    $state['total']     = (int) ($payload['total']     ?? $state['total'] ?? 0);
                    break;
                case 'sample':
                    $samples = $state['map_samples'] ?? [];
                    if (count($samples) < 60) {
                        $samples[] = $payload;
                        $state['map_samples'] = $samples;
                    }
                    break;
                case 'created': $state['created'] = (int) ($state['created'] ?? 0) + 1; break;
                case 'updated': $state['updated'] = (int) ($state['updated'] ?? 0) + 1; break;
                case 'error':   $state['errors']  = (int) ($state['errors']  ?? 0) + 1; break;
            }
            $this->put($state);
        };

        try {
            (new ImportInventoriesSeeder($progress))->run();
            $this->put(['status' => 'done', 'phase' => 'complete']);
        } catch (\Throwable $e) {
            Log::error('RunInventoriesSeederJob failed', [
                'org' => $this->orgId,
                'csv' => $this->csvPath,
                'err' => $e->getMessage(),
            ]);

            $this->put([
                'status'    => 'failed',
                'phase'     => 'error',
                'message'   => $e->getMessage(),
                'error_at'  => now()->toDateTimeString(),
            ]);
            // do not rethrow; we want the queue worker to remain healthy
        }
    }

    protected function put(array $patch): void
    {
        $state = Cache::get($this->cacheKey, []);
        Cache::put($this->cacheKey, array_merge($state, $patch), now()->addHours(6));
    }
}
