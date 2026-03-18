<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Branch;
use App\Models\MetrcTestResult;
use App\Models\MetrcPackage;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Traits\HasFallbackImage;

class Inventory extends Model
{
    use HasFactory, HasFallbackImage;

 protected $fillable = [
  'sku','product_id','organization_id','name','category_id','weight',
  'original_price','original_cost','selected_discount_tier','Quantity',
  'min_qty','storeQty','inventory_type','description','Label','THC','CBD',

];

    protected $casts = [
        'metrc_package' => 'array',
    ];

    /**
     * GLOBAL ORG SCOPE
     * Ensures every Inventory query is constrained to the current (or active) org.
     */
    protected static function booted()
    {
        static::addGlobalScope('org', function (Builder $q) {
            // Don’t block artisan/migrations and unauthenticated contexts
            if (app()->runningInConsole()) return;

            $user = Auth::user();
            if (!$user) return;

            $orgId = $user->organization_id;
            // If you support “switch org” for super admins, honor that:
            $activeOrgId = session('active_org_id', $orgId);

            $q->where('organization_id', $activeOrgId);
        });
    }

    /** Optional: escape hatch for admin maintenance scripts */
    public function scopeAllOrgs(Builder $q): Builder
    {
        return $q->withoutGlobalScope('org');
    }

    /** Relationships */
    public function categoryDetail() { return $this->belongsTo(Category::class, 'category_id'); }

    public function getImageUrlAttribute(): string
    {
        $invPath = "uploads/inventories/{$this->id}.jpg";
        $catPath = "uploads/category/{$this->category_id}.jpg";
        $default = "herbs/noimage.jpg";
        return $this->fallbackImageUrl($invPath, $catPath, $default);
    }

    public function branch()  { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }

    public function saleItems() { return $this->hasMany(SaleItem::class, 'product_id'); }

    public function sales()
    {
        return $this->belongsToMany(\App\Sale::class, 'sale_items', 'product_id', 'sale_id')
                    ->withPivot('quantity', 'price');
    }

    public function metrc_package()
    {
        return $this->hasOne(MetrcPackage::class, 'Label', 'Label');
    }

    public function metrc_full_labs()
    {
        return $this->hasMany(MetrcTestResult::class, 'PackageId', 'Label');
    }
}
