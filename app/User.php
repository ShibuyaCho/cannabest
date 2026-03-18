<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use  Notifiable;
    /**
     * rules validasi untuk data suppliers.
     *
     * @var array
     */
    public static $rules = [
        'name'     => 'required',
        'email'    => 'required|email|unique:users',
        'password' => 'required|min:6|confirmed',
        'role_id'  => 'required',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role_id','apiKey', 'organization_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
    public function isWholesale()
    {
        return $this->role_id === 5;
    }

    // If you need to fetch wholesale-specific data, you can add a method like this:
    public function wholesaleData()
    {
        return $this->hasOne(WholesaleData::class);
    }


    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    // public function hasPermission($permission)
    // {
    //     return !! $this->role->get()->intersect($permission->roles)->count();
    // }
}
