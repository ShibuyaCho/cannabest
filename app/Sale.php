<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// Related models
use App\Models\Customer;
use App\SaleItem;
use App\Models\User;

class Sale extends Model
{
    // If your table is named anything other than "sales", uncomment:
    // protected $table = 'sales';

    public $invoice_prefix = 'INV';
    public $tax_percentage = 20;

    /**
     * Mass-assignable attributes
     */
    protected $fillable = [
        'customer_id',
        'user_id', // cashier
        'name',
        'email',
        'phone',
        'address',
        'type',
        'status',
        'discount',
        'vat',
        'county_tax',
        'city_tax',
        'state_tax',
        'total_given',
        'change',
        'customer_type',
        'payment_with',
        'comments',
        'drawer_session_id',
        'med_number',
        'caregiver_number',

        // allow mass-assign if you ever use update() on overrides
        'receipt_overrides',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'discount'          => 'float',
        'vat'               => 'float',
        'total_given'       => 'float',
        'change'            => 'float',
        // NEW: persisted JSON of receipt edits (numbers and optional meta)
        'receipt_overrides' => 'array',
    ];

    /**
     * Append computed attributes
     */
    protected $appends = [
        'invoice_no',
        'subtotal',
        'total_after_discount',
        'total',
    ];

    /**
     * Line items on this sale
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Alias for saleItems()
     */
    public function items()
    {
        return $this->saleItems();
    }

    /**
     * Cashier (user) who processed the sale
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Customer (optional)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Create sale + items atomically
     */
    public static function createAll(array $input)
    {
        return DB::transaction(function () use ($input) {
            $items = $input['items'] ?? [];
            unset($input['items']);

            $sale = self::create($input);

            foreach ($items as $data) {
                $sale->saleItems()->create([
                    'product_id' => $data['product_id'],
                    'quantity'   => $data['quantity'],
                    'price'      => $data['price'],
                ]);
            }

            return $sale;
        });
    }

    public function drawerSession()
    {
        return $this->belongsTo(\App\Models\DrawerSession::class, 'drawer_session_id');
    }

    /**
     * Computed invoice number (e.g. INV000123)
     */
    public function getInvoiceNoAttribute()
    {
        return $this->invoice_prefix . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Sum of line-item price × qty
     */
    public function getSubtotalAttribute()
    {
        return $this->saleItems->sum(fn($i) => $i->price * $i->quantity);
    }
// app/Models/Sale.php

public function metrcReceipt()
{
    return $this->belongsTo(\App\Models\MetrcReceipt::class, 'metrc_receipt_id');
}

    /**
     * After-discount total (discount as percent)
     */
    public function getTotalAfterDiscountAttribute()
    {
        return $this->subtotal - ($this->subtotal * ($this->discount / 100));
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    /**
     * Final total = after-discount + tax
     * (Legacy; your receipt endpoint now drives the UI, and will honor overrides)
     */
    public function getTotalAttribute()
    {
        $tax = $this->subtotal * ($this->tax_percentage / 100);
        return $this->total_after_discount + $tax;
    }
}
