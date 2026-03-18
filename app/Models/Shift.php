<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'shift_start_time',
        'shift_stop_time',
        'is_complete',
        'status',
        'total_sales',
    ];

    public function cashier()
    {
        return $this->belongsTo('App\User', 'cashier_id');
    }
}

