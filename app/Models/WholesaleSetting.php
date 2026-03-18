<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WholesaleSetting extends Model
{
    use HasFactory;
    protected $fillable = ['organization_id', 'user_id', 'key', 'label', 'value'];


    // If you want to store JSON in the 'value' column
    protected $casts = [
        'value' => 'array',
         
    ];

    // Helper method to get a setting
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Helper method to set a setting
    public static function set($key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    
     public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
