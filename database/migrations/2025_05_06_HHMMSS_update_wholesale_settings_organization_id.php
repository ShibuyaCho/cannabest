<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WholesaleSetting;
use App\Models\User;

class UpdateWholesaleSettingsOrganizationId extends Migration
{
    public function up()
    {
        $users = User::whereNotNull('organization_id')->get();

        foreach ($users as $user) {
            WholesaleSetting::where('organization_id', null)
                ->update(['organization_id' => $user->organization_id]);
        }
    }

    public function down()
    {
        // This migration cannot be reversed
    }
}