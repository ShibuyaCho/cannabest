<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\WholesaleProduct;
use App\Product;


class WholesaleInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wholesale_product_id',
        'license_number',
        'package_id',
        'quantity',
        'price',
        'sku',
        'name',
        'display_name',
        'description',
        'category_id',
        'status',
        'metrc_data',
        'image',
        'products',
        'organization_id',
    ];

    protected $casts = [
        'metrc_data' => 'array',
        'price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'products' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wholesaleProduct()
    {
        return $this->belongsTo(WholesaleProduct::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', 0);
    }

    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }

    public function decreaseStock($amount)
    {
        if ($this->quantity >= $amount) {
            $this->decrement('quantity', $amount);
            return true;
        }
        return false;
    }

    public function increaseStock($amount)
    {
        $this->increment('quantity', $amount);
    }
    public function products()
{
    return $this->hasMany(Product::class, 'wholesale_product_id');
}
}
