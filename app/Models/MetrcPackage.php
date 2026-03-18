<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetrcPackage extends Model
{
      public $timestamps = false;
        public    $incrementing = false;
    protected $primaryKey   = 'Id';
    protected $fillable     = ['Id','Label','payload','LastModified'];

    public function tests()
    {
        return $this->hasMany(MetrcTestResult::class, 'PackageId', 'Id');
    }
}
