<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Database\Seeders\ImportInventoriesSeeder;

class InventoriesImportController extends Controller
{
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:51200', // 50MB
            ]);

            $user  = $request->user();
            $orgId = (int) optional($user->organization)->id;
            if (!$orgId) {
                return response()->json(['ok' => false, 'message' => 'No organization for user'], 422);
            }

            // ---- Save upload to an absolute path (disk-agnostic) ----
            $fileName = 'org'.$orgId.'-'.date('Ymd_His').'.csv';
            $absPath  = null;
            $where    = null;
            $errors   = [];

            $targets = [
                ['dir' => public_path('uploads/imports'), 'label' => 'public/uploads/imports'],
                ['dir' => public_path('imports'),         'label' => 'public/imports'],
                ['dir' => storage_path('app/imports'),          'label' => 'storage/app/imports'],
                ['dir' => storage_path('app/public/imports'),   'label' => 'storage/app/public/imports'],
            ];

            foreach ($targets as $t) {
                try {
                    $dir = $t['dir'];
                    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
                        throw new \RuntimeException("mkdir failed");
                    }
                    if (!is_writable($dir)) {
                        throw new \RuntimeException("not writable");
                    }
                    $request->file('file')->move($dir, $fileName);
                    $absPath = $dir.DIRECTORY_SEPARATOR.$fileName;
                    $where   = $t['label'];
                    break;
                } catch (\Throwable $e) {
                    $errors[] = "{$t['label']}: ".$e->getMessage();
                }
            }

            if (!$absPath || !is_readable($absPath)) {
                Log::error('CSV import: no writable target', ['attempt_errors' => $errors]);
                return response()->json([
                    'ok'      => false,
                    'message' => 'No writable import folder (tried public/uploads/imports, public/imports, storage/app/imports, storage/app/public/imports).',
                    'code'    => 'E_STORAGE',
                    'detail'  => $errors,
                ], 500);
            }

            // ---- Status key (the UI can still poll /status/{key}) ----
            $key = 'invimp:'.Str::uuid()->toString();
            Cache::put($key, [
                'status'      => 'running',
                'phase'       => 'importing (sync)',
                'total'       => 0,
                'processed'   => 0,
                'created'     => 0,
                'updated'     => 0,
                'errors'      => 0,
                'map_samples' => [],
                'path'        => $absPath,
                'org'         => $orgId,
                'saved_in'    => $where,
                '_log'        => [],
            ], now()->addHours(6));

            // ---- Configure seeder + progress callback, then RUN SYNC ----
            Config::set('imports.organization_id', $orgId);
            Config::set('imports.inventory_csv_path', $absPath);

            $progress = function (string $evt, array $payload = []) use ($key) {
                $state = Cache::get($key, []);
                $state['phase'] = $evt;
                foreach ($payload as $k => $v) {
                    $state[$k] = $v;
                }
                Cache::put($key, $state, now()->addHours(6));
            };

            try {
                (new ImportInventoriesSeeder($progress))->run();
                $state = Cache::get($key, []);
                $state['status'] = 'done';
                $state['phase']  = 'done';
                Cache::put($key, $state, now()->addHours(6));
            } catch (\Throwable $e) {
                Log::error('ImportInventoriesSeeder failed', ['err' => $e->getMessage()]);
                $state = Cache::get($key, []);
                $state['status']  = 'failed';
                $state['phase']   = 'failed';
                $state['message'] = $e->getMessage();
                Cache::put($key, $state, now()->addHours(6));
                return response()->json([
                    'ok'      => false,
                    'message' => 'Import failed: '.$e->getMessage(),
                ], 500);
            }

            return response()->json([
                'ok'       => true,
                'key'      => $key,
                'saved_in' => $where,
                'status'   => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::error('CSV import enqueue failed', ['err' => $e->getMessage()]);
            return response()->json([
                'ok'      => false,
                'message' => 'Server error: '.$e->getMessage(),
                'code'    => 'E_IMPORT',
            ], 500);
        }
    }

    public function status(string $k)
    {
        $state = Cache::get($k);
        if (!$state) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }
        return response()->json([
            'ok'          => true,
            'status'      => (string) ($state['status'] ?? 'unknown'),
            'phase'       => (string) ($state['phase']  ?? ''),
            'message'     => $state['message'] ?? null,
            'error'       => $state['error']   ?? null,
            'total'       => (int) ($state['total'] ?? 0),
            'processed'   => (int) ($state['processed'] ?? 0),
            'created'     => (int) ($state['created'] ?? 0),
            'updated'     => (int) ($state['updated'] ?? 0),
            'errors'      => (int) ($state['errors'] ?? 0),
            'map_samples' => array_slice((array) ($state['map_samples'] ?? []), 0, 60),
            'saved_in'    => $state['saved_in'] ?? null,
            'path'        => $state['path']     ?? null,
            '_log'        => $state['_log']     ?? [],
        ]);
    }
}
