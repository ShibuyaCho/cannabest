<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;



return new class extends Migration

{

    /**

     * Run the migrations.

     */

    public function up(): void

    {

        DB::table('settings')->insert([

            
            [

                'company_id' => 0,

                'key' => 'license_number',

                'label' => 'Facility License #',

                'value' => 'ABC-123',

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'company_id' => 0,

                'key' => 'county_tax',

                'label' => 'County Tax',

                'value' => '3',

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'company_id' => 0,

                'key' => 'CityTax',

                'label' => 'City Tax',

                'value' => '0',

                'created_at' => now(),

                'updated_at' => now(),

            ],

            [

                'company_id' => 0,

                'key' => 'StateTax',

                'label' => 'State Tax',

                'value' => '17',

                'created_at' => now(),

                'updated_at' => now(),

            ],

        ]);

    }



    /**

     * Reverse the migrations.

     */

    public function down(): void

    {

        DB::table('settings')->whereIn('key', [

            


            'license_number',

            'county_tax',

            'city_tax',

            'state_tax',

        ])->delete();

    }

};