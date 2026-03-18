<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Product; 
class Brand extends Model
{
    protected $fillable = ['name', 'description', 'image'];

    public function user()
    {
        return $this->belongsTo(User::class);
 
}

    public function wholesaleProducts()
    {
        return $this->hasMany(WholesaleProduct::class);
    }
    public function organization()
{
    return $this->belongsTo(Organization::class);
}
}