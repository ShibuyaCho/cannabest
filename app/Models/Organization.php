<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

  protected $fillable = [
    'name',
    'type',
    'email',
    'license_number',
    'business_name',
    'image',

    // the two contact fields you added
    'phone',
    'physical_address',

    // tax/VAT fields
    'county_tax',
    'city_tax',
    'state_tax',

    // display fields
    'currency',
    'footer_text',

    // hours — one column per day
    'sunday',
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',

    // JSON/boolean settings
    'discount_tiers',
    'sms_alert_phone_numbers',
    'sms_alert_customer_creation',
];

protected $casts = [
    'discount_tiers'             => 'array',
    'sms_alert_phone_numbers'    => 'array',
    'sms_alert_customer_creation'=> 'boolean',
    'county_tax'                 => 'integer',
    'city_tax'                   => 'integer',
    'state_tax'                  => 'integer',
];



    public function featuredProducts()
    {
        return $this->hasMany(Product::class)->where('is_featured', true);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function children()
    {
        return $this->belongsToMany(Organization::class, 'organization_links', 'parent_id', 'child_id');
    }

    public function parents()
    {
        return $this->belongsToMany(Organization::class, 'organization_links', 'child_id', 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function associatedUsers()
    {
        return $this->belongsToMany(User::class, 'user_organizations');
    }
    public function brands()
{
    return $this->hasMany(Brand::class);
}
public function wholesaleInventory()
{
    return $this->hasOne(WholesaleInventory::class);
}

public function wholesaleSetting()
{
    return $this->hasOne(WholesaleSetting::class);
}
public function wholesaleProducts()
{
    return $this->hasMany(WholesaleProduct::class);
}

}