<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CashDrawer extends Model
{
    protected $fillable = ['name','status','organization_id'];

    // Always constrain by organization if a user is authenticated
    protected static function booted()
    {
        static::addGlobalScope('org', function (Builder $q) {
            if ($user = auth()->user()) {
                $q->where('organization_id', $user->organization_id);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

public function currentSession()
{
    return $this->hasOne(\App\Models\DrawerSession::class, 'cash_drawer_id')
        ->withoutGlobalScope('org')     // <- critical if some sessions had org_id 0/NULL
        ->whereNull('closed_at')
        ->latestOfMany('opened_at');    // if multiple open rows exist, take the newest
}

public function sessions()
{
    return $this->hasMany(\App\Models\DrawerSession::class, 'cash_drawer_id')
        ->withoutGlobalScope('org')     // history should still show
        ->orderBy('opened_at','desc');
}

public function assignedUser()
{
    return $this->hasOneThrough(
        \App\Models\User::class,
        \App\Models\DrawerSession::class,
        'cash_drawer_id',
        'id',
        'id',
        'user_id'
    )->withoutGlobalScope('org')        // assigned comes from the open session too
     ->whereNull('drawer_sessions.closed_at');
}
    
}
