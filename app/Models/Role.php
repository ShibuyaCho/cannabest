<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
// use Zizaco\Entrust\EntrustRole;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];


    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
