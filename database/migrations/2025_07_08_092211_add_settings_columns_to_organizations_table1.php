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
        Schema::table('organizations', function (Blueprint $table) {
             Schema::table('organizations', function (Blueprint $table) {
        // basic contact
       
       

        // tax / VAT
      
        $table->unsignedTinyInteger('county_tax')->default(0);
        $table->unsignedTinyInteger('city_tax')->default(0)->after('county_tax');
        $table->unsignedTinyInteger('state_tax')->default(0)->after('city_tax');

        // display
        $table->string('currency', 5)->default('$')->after('state_tax');
        $table->text('footer_text')->nullable()->after('currency');
        
        // hours
        
        $table->string('sunday')->nullable();
        $table->string('monday')->nullable();
        $table->string('tuesday')->nullable();
        $table->string('wednesday')->nullable();
        $table->string('thursday')->nullable();
        $table->string('friday')->nullable();
        $table->string('saturday')->nullable();

        // complex JSON settings
        $table->json('discount_tiers')->nullable()->after('saturday');
        $table->json('sms_alert_phone_numbers')->nullable()->after('discount_tiers');
        $table->boolean('sms_alert_customer_creation')->default(false)->after('sms_alert_phone_numbers');
    });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down()
{
    Schema::table('organizations', function (Blueprint $table) {
        $table->dropColumn([
           
            
            'county_tax',
            'city_tax',
            'state_tax',
            'currency',
            'footer_text',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'discount_tiers',
            'sms_alert_phone_numbers',
            'sms_alert_customer_creation',
        ]);
    });
    }
};
