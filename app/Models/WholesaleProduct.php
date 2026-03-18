<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Brand;
use App\Models\Category; 
use App\Models\WholesaleInventory;

class WholesaleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'display_name', 'extraName', 'brand_id', 'price', 'weight', 'image', 'organization_id',
        'description', 'category_id', 'UnitThcContent', 'UnitCbdContent', 'status'
    ];

    public function wholesaleInventories()
    {
        return $this->hasMany(WholesaleInventory::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getTotalQuantityAttribute()
    {
        $totalQuantity = 0;

        foreach ($this->wholesaleInventories as $inventory) {
            $products = $inventory->products;
            if (is_string($products)) {
                $products = json_decode($products, true);
            }
            if (is_array($products)) {
                foreach ($products as $product) {
                    $totalQuantity += floatval($product['quantity'] ?? 0);
                }
            }
        }

        return $totalQuantity;
    }
    public function wholesaleOrderItems()
{
    return $this->hasMany(WholesaleOrderItem::class);
}
}
