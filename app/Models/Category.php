<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Category extends Model
{
    /**
     * rules validasi untuk data customers.
     *
     * @var array
     */
    public static $rules = [
        'name'    => 'required'
       
    ];
    
    /**
     * setup variable mass assignment.
     *
     * @var array
     */
     protected $fillable = [
        'company_id',
        'name',
        'sales_limit_category',
        'image',
        'thumb',
        'printers',
        'sort',
        'print_on_sale',
        'taxable',          // ← add this
    ];
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
   public function wholesaleProducts()
{
    return $this->hasMany(WholesaleProduct::class);
} 

}
