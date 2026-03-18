<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';
    
    protected $casts = [
  'quantity'=>'decimal:3','unit_price'=>'decimal:2',
  'line_total'=>'decimal:2','price'=>'decimal:2','price_is_line_total'=>'boolean',
];

    public function metrc_package()
    {
        return $this->belongsTo(MetrcPackage::class, 'package_id', 'Label');
    }
}
