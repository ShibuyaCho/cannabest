<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
     public $timestamps = false;  // This tells Laravel not to manage created_at and updated_at
     protected $dates = ['datetime'];  // This tells Laravel to treat the datetime column as a date
}
