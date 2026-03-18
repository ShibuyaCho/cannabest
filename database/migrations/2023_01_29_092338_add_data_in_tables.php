<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the super admin user
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Super Admin',
            'apiKey' => 'api_key_1',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Remember to change this password in production
            'phone' => '1234567890',
            'address' => '123 Admin St',
            'city' => 'Admin City',
            'state' => 'AS',
            'zip' => '12345',
            'role_id' => 1,
            'remember_token' => 'rDWTyMV7OybRAqR2W5jCSb3a6O78yOfWbzLz1dspV6qlNoiv3IcXi9QBSxwp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert categories
        DB::table('categories')->insert([
           
    ['id' => 1,  'name' => 'Flower',            'sort' => 0, 'created_at' => '2023-01-24 23:06:09', 'updated_at' => '2023-01-24 23:06:09'],
    ['id' => 2,  'name' => 'Joints',            'sort' => 0, 'created_at' => '2023-01-24 23:09:14', 'updated_at' => '2023-01-24 23:09:14'],
    ['id' => 3,  'name' => 'Extract',           'sort' => 0, 'created_at' => '2023-01-24 23:06:55', 'updated_at' => '2023-01-24 23:06:55'],
    // new
    ['id' => 4,  'name' => 'Concentrate',       'sort' => 0, 'created_at' => '2025-07-07 00:00:00', 'updated_at' => '2025-07-07 00:00:00'],
    // shifted down
    ['id' => 5,  'name' => 'Infused Joints',    'sort' => 0, 'created_at' => '2023-01-24 23:09:14', 'updated_at' => '2023-01-24 23:09:14'],
    ['id' => 6,  'name' => 'Extract Carts',     'sort' => 0, 'created_at' => '2023-01-24 23:09:14', 'updated_at' => '2023-01-24 23:09:14'],
    ['id' => 7,  'name' => 'Flavored Carts',    'sort' => 0, 'created_at' => '2023-01-24 23:09:14', 'updated_at' => '2023-01-24 23:09:14'],
    ['id' => 8,  'name' => 'Edibles',           'sort' => 0, 'created_at' => '2023-01-24 23:07-43', 'updated_at' => '2023-01-24 23:07:43'],
    ['id' => 9,  'name' => 'Drinks/Tinctures',  'sort' => 0, 'created_at' => '2023-01-24 23:07:43', 'updated_at' => '2023-01-24 23:07:43'],
    ['id' => 10, 'name' => 'Clones',            'sort' => 0, 'created_at' => '2023-01-24 23:08:41', 'updated_at' => '2023-01-24 23:08:41'],
    ['id' => 11, 'name' => 'Accessories',       'sort' => 0, 'created_at' => '2023-01-24 23:09:53', 'updated_at' => '2023-01-24 23:09:53'],
    // new
    ['id' => 12, 'name' => 'Apparel',           'sort' => 0, 'created_at' => '2025-07-07 00:00:00', 'updated_at' => '2025-07-07 00:00:00'],


        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the super admin user
        DB::table('users')->where('id', 1)->delete();

        // Remove the categories
        DB::table('categories')->whereIn('id', range(1, 10))->delete();
    }
};