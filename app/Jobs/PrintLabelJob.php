<?php

namespace App\Jobs;

use App\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PrintLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;

    /**
     * Create a new job instance.
     */
    public function __construct(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $url = route('inventory.printLabel', $this->inventory->id);
            file_get_contents($url); // triggers the label print (or PDF generation, etc)
        } catch (\Throwable $e) {
            Log::error('Failed to print label for inventory ID: ' . $this->inventory->id, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
