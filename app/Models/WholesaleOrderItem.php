<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesaleOrderItem extends Model
{
    protected $fillable = [
        'wholesale_order_id', 'wholesale_product_id', 'quantity', 'price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

  public function wholesaleOrder()
{
    return $this->belongsTo(WholesaleOrder::class);
}

public function wholesaleProduct()
{
    return $this->belongsTo(WholesaleProduct::class);
}

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }
}