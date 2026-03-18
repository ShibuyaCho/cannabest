<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InventoryCsvImportController extends Controller
{
    public function importCsv(Request $request)
    {
        try {
            // Auth/org guard
            $user  = $request->user();
            $orgId = $user?->organization_id;
            if (!$orgId) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No organization on the current user.'
                ], 403);
            }

            // Validate upload
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    // common CSV mimetypes + generous max (50MB)
                    'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,text/anytext',
                    'max:51200'
                ],
            ]);

            $file = $request->file('file');
            if (!$file->isValid()) {
                return response()->json(['ok'=>false,'message'=>'Invalid upload.'], 422);
            }

            // Ensure public disk exists (storage/app/public)
            if (!Storage::disk('public')->exists('imports')) {
                Storage::disk('public')->makeDirectory('imports');
            }

            // Store at storage/app/public/imports/{orgId}.csv
            $basename   = $orgId . '.csv';
            $storedPath = $file->storeAs('imports', $basename, 'public'); // "imports/3.csv"
            $absStorage = Storage::disk('public')->path($storedPath);

            // Also copy to public_path("{org}.csv") for legacy seeders that read from /public
            $publicCopy = public_path($basename);
            try {
                @copy($absStorage, $publicCopy);
            } catch (Throwable $e) {
                // non-fatal: continue
                Log::warning("CSV public copy failed: ".$e->getMessage());
            }

            // Quick line count (minus header) just for a friendly message
            $lineCount = 0;
            if (is_readable($absStorage)) {
                $fh = fopen($absStorage, 'r');
                if ($fh) {
                    $first = true;
                    while (($row = fgetcsv($fh)) !== false) {
                        if ($first) { $first = false; continue; }
                        $lineCount++;
                    }
                    fclose($fh);
                }
            }

            // Try to run a seeder if it exists (either namespaced or legacy)
            $seederOutput = '';
            $seederRan    = false;
            $candidates   = [
                'Database\\Seeders\\ImportInventoriesSeeder',
                'ImportInventoriesSeeder',
            ];

            // Pass the file path/org into config so the seeder can read them
            config([
                'imports.inventory_csv_path' => $absStorage,
                'imports.organization_id'    => $orgId,
            ]);

            foreach ($candidates as $class) {
                if (class_exists($class)) {
                    Artisan::call('db:seed', [
                        '--class' => $class,
                        '--force' => true,
                    ]);
                    $seederOutput = trim(Artisan::output());
                    $seederRan = true;
                    break;
                }
            }

            return response()->json([
                'ok'      => true,
                'message' => sprintf(
                    'Uploaded %s (%d data rows). %s',
                    basename($absStorage),
                    max(0, $lineCount),
                    $seederRan ? 'Import seeder executed.' : 'Seeder not found—file saved only.'
                ),
                'output'  => $seederOutput,
                'path'    => $absStorage,
            ]);
        } catch (Throwable $e) {
            Log::error("CSV import failed: {$e->getMessage()}\n".$e->getTraceAsString());
            // Return JSON so your modal shows a helpful error
            return response()->json([
                'ok'       => false,
                'message'  => 'Server error during CSV import. See logs.',
                'error'    => $e->getMessage(),
            ], 500);
        }
    }
}
