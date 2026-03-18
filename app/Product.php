<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\wholesaleProduct;
use App\Models\Category;


class Product extends Model
{
     use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * rules validasi untuk data products.
     *
     * @var array
     */
    public static $rules = [
        'name'  => 'required|unique:products'
    ];

    /**
     * setup variable mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'prices',
        'category_id',
        'description',
        'titles',
        'discount',
        'PackageId',
        'original_price',
        'Label',
        'original_cost',
        'IsDonation',
        'IsFinished', 
        'productcategorytype',
        'IsUseByDateRequired',
        'UnitOfMeasureNameItem',
        'UnitCbdPercent',
        'UnitThcPercent',
        'Unitvolume',
        'UnitWeight',
        'ServingSize',
        'sku',
    ];
    protected $casts = [
        'discount_tiers' => 'array',
        // ... other casts
    ];

    public function scopeSearchByKeyword($query, $keyword)
    {
        if ($keyword != '') {
            $query->where(
                function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%'.$keyword.'%')
                        ->orWhere('barcode', 'LIKE', '%'.$keyword.'%');
                }
            );
        }

        return $query;
    }
    public function category()
{
    return $this->belongsTo(Category::class);
}

public function wholesaleProduct()
{
    return $this->belongsTo(WholesaleInventory::class, 'wholesale_product_id');
}

public function getAspdAttribute()
{
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth   = Carbon::now()->endOfMonth();
    $totalSold    = $this->orderItems()
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->sum('quantity');
    $daysInMonth  = Carbon::now()->daysInMonth;
    return $daysInMonth > 0 ? round($totalSold / $daysInMonth, 2) : 0;
}
public function wholesaleInventory()
{
    return $this->belongsTo(WholesaleInventory::class, 'wholesale_product_id');
}
}
