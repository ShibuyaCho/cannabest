<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\User;

class WholesaleSettingsSeeder extends Seeder
{
    public function run()
    {
        $defaultSettings = [
            ['key' => 'title', 'label' => 'Organization Name', 'value' => 'Wholesale Org'],
            ['key' => 'phone', 'label' => 'Phone', 'value' => ''],
            ['key' => 'email', 'label' => 'Email', 'value' => ''],
            ['key' => 'address', 'label' => 'Address', 'value' => ''],
            ['key' => 'user_discount_percentage', 'label' => 'User Discount Percentage', 'value' => '5'],
            ['key' => 'min_order_amount', 'label' => 'Minimum Order Amount', 'value' => '100'],
        ];

        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            // Insert default settings for each organization
            foreach ($defaultSettings as $setting) {
                DB::table('wholesale_settings')->updateOrInsert(
                    [
                        'organization_id' => $organization->id,
                        'key' => $setting['key'],
                    ],
                    [
                        'label' => $setting['label'],
                        'value' => $setting['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Get users associated with this organization
            $users = User::where('organization_id', $organization->id)->get();

            // Insert API key for each user in the organization
           
        }
    }
}