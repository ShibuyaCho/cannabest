<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Inventory;

class FixScientificSKUs extends Command
{
    protected $signature = 'fix:skus';
    protected $description = 'Fix SKUs that were auto-converted to scientific notation';

    public function handle()
    {
        $this->info("🔍 Checking products for scientific notation SKUs...");

        $count = 0;

        $products = Product::all();

        foreach ($products as $product) {
            if (is_numeric($product->sku) && strpos((string) $product->sku, 'E') !== false) {
                $originalSku = $product->sku;
                $correctedSku = number_format((float) $originalSku, 0, '', '');

                $this->line("↪ Fixing SKU {$originalSku} → {$correctedSku}");

                // Update product
                $product->sku = $correctedSku;
                $product->save();

                // Update related inventory
                Inventory::where('sku', $originalSku)->update(['sku' => $correctedSku]);

                $count++;
            }
        }

        $this->info("✅ Fixed {$count} SKUs.");
        return Command::SUCCESS;
    }
}
