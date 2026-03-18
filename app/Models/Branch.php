<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'address',
        'city',
        'phone',
        'email',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class)->whereNotNull('organization_id')->whereNotNull('branch_id');
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'inventories')->withPivot('quantity');
    }

    // Add more relationships as needed, such as orders, etc.
}