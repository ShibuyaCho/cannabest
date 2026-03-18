<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table = "email_templates";
    protected $primaryKey = "id";
    protected $fillable = ['title','short_code','subject', 'message','status','created_by','updated_by'];
}
