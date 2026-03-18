<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncBidirectional extends Command
{
    protected $signature = 'sync:bidirectional';
    protected $description = 'Push local SQLite changes to MySQL and pull remote changes into SQLite';

    public function handle()
    {
        $this->info('Starting two-way sync…');

        // ––– PUSH local outbox to MySQL –––
        $outboxItems = DB::connection('sqlite')
            ->table('sync_outbox')
            ->whereNull('synced_at')
            ->orderBy('created_at')
            ->get();

        $pushed = 0;
        foreach ($outboxItems as $item) {
            $modelClass = $item->model_type;
            $data       = json_decode($item->payload, true);
            $action     = $item->action;
            $idField    = (new $modelClass)->getKeyName();
            $table      = (new $modelClass)->getTable();

            $mysql = DB::connection('mysql');

            if ($action === 'delete') {
                $mysql->table($table)->where($idField, $item->model_id)->delete();
            } else {
                $mysql->table($table)
                      ->updateOrInsert([$idField => $item->model_id], $data);
            }

            DB::connection('sqlite')
              ->table('sync_outbox')
              ->where('id', $item->id)
              ->update(['synced_at' => now()]);

            $pushed++;
        }
        $this->info("Pushed {$pushed} local change(s) to MySQL.");

        // ––– PULL remote changes from MySQL –––
        $lastPull = DB::connection('sqlite')
            ->table('sync_meta')
            ->where('key', 'last_pull')
            ->value('value') ?? '1970-01-01 00:00:00';

        $models = [
            \App\Models\Product::class,
            // add other models here…
        ];

        $pulled = 0;
        foreach ($models as $class) {
            $instance = new $class;
            $table    = $instance->getTable();
            $idField  = $instance->getKeyName();

            $rows = DB::connection('mysql')
                ->table($table)
                ->where('updated_at', '>', $lastPull)
                ->get();

            foreach ($rows as $row) {
                DB::connection('sqlite')->table($table)
                  ->updateOrInsert(
                      [$idField => $row->{$idField}],
                      (array)$row
                  );
                $pulled++;
            }
        }
        $this->info("Pulled {$pulled} remote change(s) into SQLite.");

        // update last_pull
        $now = now()->toDateTimeString();
        DB::connection('sqlite')
          ->table('sync_meta')
          ->updateOrInsert(['key' => 'last_pull'], ['value' => $now]);

        $this->info("Sync complete at {$now}.");

        return 0;
    }
}
