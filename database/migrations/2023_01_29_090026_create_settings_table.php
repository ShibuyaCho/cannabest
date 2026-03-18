<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->integer('company_id')->nullable();
            $table->string('key');
            $table->string('label');
            $table->string('value');
            
        
            $table->timestamps();
        });


            DB::statement("INSERT INTO `settings` (`id`, `company_id`, `key`, `label`, `value`, `created_at`, `updated_at`) VALUES
            (1, 0, 'title', 'Site Title', 'Cannbest', NULL, '2023-01-10 11:46:02'),
            (2, 0, 'phone', 'Phone', '5038675309', NULL, '2017-09-06 22:08:34'),
            (3, 0, 'email', 'Email', '123abc@gmail.com', NULL, '2017-09-06 22:08:34'),
            (4, 0, 'address', 'Adress', '123 Nowhere Ln', NULL, '2017-08-16 03:53:13'),
            
            (5, 0, 'timing1', 'Monday To Saturday', '9AM to 9PM', NULL, '2017-09-18 18:19:33'),
            (6, 0, 'sunday', 'Sunday', '10AM to 9PM', NULL, '2017-09-18 18:19:34'),           
            (7, 0, 'vat', 'VAT', '1', NULL, '2017-10-03 16:50:12'),           
            (8, 0, 'currency', 'Currency', '$', NULL, '2017-10-03 17:00:43'),                      
                  
            (9, 0, 'footer_text', 'Footer Text', '<h5>F</h5><p>F', NULL, '2021-08-19 11:42:48'),
             (10, 0, 'discount_tiers', 'Discount Tier', '[]', NULL, '2021-08-19 11:42:48')
              
");
            


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
