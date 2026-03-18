<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DrawerSession extends Model
{
    protected $fillable = [
        'cash_drawer_id',
        'user_id',
        'organization_id',
        'starting_amount',
        'closing_amount',
        'opened_at',
        'closed_at',
    ];

    protected $dates = ['opened_at','closed_at'];

    protected static function booted()
    {
        static::addGlobalScope('org', function (Builder $q) {
            if ($user = auth()->user()) {
                $q->where('organization_id', $user->organization_id);
            }
        });
    }

    public function drawer()
    {
        return $this->belongsTo(CashDrawer::class, 'cash_drawer_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
