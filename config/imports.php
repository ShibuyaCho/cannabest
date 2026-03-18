<?php
// config/imports.php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory CSV import defaults
    |--------------------------------------------------------------------------
    | The job will override `inventory_csv_path` and `organization_id` at runtime
    | via Config::set(). These values are just fallbacks so the seeder can run
    | locally (e.g., `php artisan db:seed --class=ImportInventoriesSeeder`)
    | without the queue/job.
    */

    // Absolute path to a CSV (fallback only; the queued job passes an absolute path)
    'inventory_csv_path' => env('INVENTORY_IMPORT_PATH', storage_path('app/imports/import.csv')),

    // Organization to scope inventories to (the job normally sets this)
    'organization_id'    => env('INVENTORY_IMPORT_ORG_ID', 0),

    /*
    |--------------------------------------------------------------------------
    | Queue / runtime knobs
    |--------------------------------------------------------------------------
    */

    // Which queue to dispatch the import job onto. Null means default queue.
    'queue'              => env('INVENTORY_IMPORT_QUEUE', 'default'),

    // Seconds a single job run may execute before timing out (worker-level)
    'timeout'            => (int) env('INVENTORY_IMPORT_TIMEOUT', 900), // 15 minutes

    // How many mapping samples to keep in the final log output
    'log_map_samples'    => (int) env('INVENTORY_IMPORT_LOG_SAMPLE', 60),

    // Disk used for temporary CSV storage (controller uses this when saving upload)
    'disk'               => env('INVENTORY_IMPORT_DISK', 'local'),

    // Optional: batch size hint if you later chunk heavy DB writes
    'chunk'              => (int) env('INVENTORY_IMPORT_CHUNK', 1000),
];
