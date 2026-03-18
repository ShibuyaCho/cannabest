<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wholesale extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brandNames',
        'api_key',
        'address',
        'phone',
        'e-mail',
        'products',
    ];

    protected $casts = [
        'products' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}
