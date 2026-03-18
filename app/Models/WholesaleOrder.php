<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesaleOrder extends Model
{
    protected $fillable = [
        'organization_id', 'created_by_user_id', 'total_amount', 'status', 'payment_method', 'notes', 'order_number'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function user()
{
    return $this->belongsTo(User::class, 'created_by_user_id');
}

    public function items()
    {
        return $this->hasMany(WholesaleOrderItem::class);
    }
    public function wholesaleOrderItems()
{
    return $this->hasMany(WholesaleOrderItem::class);
}
    public function getRouteKeyName()
{
    return 'id';
}

    public function getStatusColorAttribute()
    {
        return [
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
        ][$this->status] ?? 'secondary';
    }
}