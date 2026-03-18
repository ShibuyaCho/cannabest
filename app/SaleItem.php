<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    /**
     * setup variable mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'price',
        'size',
        'quantity',
        'p_qty',
        'metrc_package', 
        
    ];
    protected $casts = [
    'metrc_package' => 'array',
    // …
];

    public function getSubtotalAttribute()
    {
        return $this->attributes['price'] * $this->attributes['quantity'];
    }

    public function trackings()
    {
        return $this->morphOne('App\InventoryTracking', 'trackable');
    }

 public function inventory()
{
    return $this->belongsTo(\App\Inventory::class, 'product_id');
}
   public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }
    public function sale()
{
    return $this->belongsTo(\App\Sale::class, 'sale_id');
}
}
