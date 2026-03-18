<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role; 
use App\Models\Organization;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const ROLE_SUPER_ADMIN = 1;
    const ROLE_ORG_ADMIN = 2;
    const ROLE_BUDTENDER = 3;
    const ROLE_CUSTOMER = 5;
    const ROLE_WHOLESALE_USER = 6;
    const ROLE_STANDARD_USER = 4;
    
    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'organization_id',
        'apiKey', 'phone', 'address', 'city', 'state', 'zip'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
protected $visible = [
    // ... other visible attributes ...
    'apiKey', 'name', 'email', 'role_id', 'organization_id',
];
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'user_organizations');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin()
    {
        return $this->role_id === self::ROLE_SUPER_ADMIN;
    }
    public function getApiKey()
    {
        return $this->apiKey;
    }
public function getTypeAttribute()
{
    return $this->role === 2 ? 'admin' : 'user';
}
    public function hasApiKey()
    {
        return !is_null($this->apiKey);
    }
    public function isOrganizationAdmin()
    {
        return $this->role_id === self::ROLE_ORG_ADMIN;
    }

    public function isStandardUser()
    {
        return $this->role_id === self::ROLE_STANDARD_USER;
    }

    public function canAccessOrganization(Organization $organization)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->organizations->contains($organization);
    }

    public function accessibleOrganizations()
    {
        if ($this->isSuperAdmin()) {
            return Organization::all();
        }

        return $this->organizations;
    }

    public function linkedOrganizations()
    {
       
            return $this->belongsToMany(Organization::class, 'user_organizations', 'user_id', 'organization_id');
        

        return $this->organizations->map(function ($org) {
            return $org->load('children', 'parents');
        });
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role->name, (array) $roles);
    }

    public function wholesaleSettings()
    {
        return $this->hasMany(WholesaleSetting::class, 'organization_id', 'organization_id');
    }
}