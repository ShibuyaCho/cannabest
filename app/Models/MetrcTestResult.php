<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetrcTestResult extends Model
{
   

     public    $timestamps = false;
    protected $fillable   = ['PackageId','TestTypeName','TestResultLevel','LabFacilityName', 'LabFacilityLicenseNumber', 'DateTested'];

    public function package()
    {
        return $this->belongsTo(MetrcPackage::class, 'PackageId', 'Id');
    }
    
}
