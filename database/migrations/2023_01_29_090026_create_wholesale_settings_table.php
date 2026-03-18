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
        Schema::create('wholesale_settings', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->integer('company_id')->nullable();
            $table->string('key');
            $table->string('label');
            $table->string('value');
            
            
        
            $table->timestamps();
        });


            DB::statement("INSERT INTO `wholesale_settings` (`id`, `company_id`, `key`, `label`, `value`, `created_at`, `updated_at`) VALUES
      
(1, 0, 'title', 'Site Title', 'Wholesale', NULL, '2023-01-25 22:32:14'),
(2, 0, 'phone', 'Phone', '5038675309', NULL, '2023-01-25 22:32:14'),
(3, 0, 'email', 'Email', 'wholesale@staff.com', NULL, '2017-09-06 22:08:34'),
(4, 0, 'address', 'Address', '3rd Floor Street 6 Gali 5', NULL, '2017-08-16 03:53:13'),
(5, 0, 'country', 'Country', 'USA', NULL, '2017-08-16 03:53:13'),
(6, 0, 'license_number', 'License Number', '020-X0001', NULL, '2017-10-03 15:35:48'),
(8, 0, 'facebook', 'Facebook', 'https://www.facebook.com/cent040', NULL, '2017-10-03 15:35:48'),
(9, 0, 'twitter', 'Twitter', 'https://www.twitter.com/cent040', NULL, '2017-10-03 15:35:48'),
(10, 0, 'instagram', 'Instagram', 'https://www.instagram.com/cent040', NULL, '2017-10-03 15:35:48'),
(11, 0, 'delivery_cost', 'Delivery Cost', '1', NULL, '2017-10-03 15:35:48'),
(12, 0, 'currency', 'Currency', '$', NULL, '2017-10-03 17:00:43'),
(13, 0, 'lng', 'Longitude', '43.8041', NULL, NULL),
(14, 0, 'lat', 'Latitude', '120.5542', NULL, NULL),
(16, 0, 'frontend', 'Hide Frontend', 'no', NULL, '2017-11-25 06:26:00'),
(19, 0, 'staff_allow_sales', 'Sales Staff to Complete Sales', 'yes', NULL, NULL),
(37, 0, 'footer_text', 'Footer Text', '<h5></h5><p>', NULL, '2021-08-19 11:42:48');         
");
            


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wholesale_settings');
    }
};
