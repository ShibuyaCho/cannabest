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
        Schema::create('organizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('type', ['retail', 'wholesale', 'producer', 'processor', 'laboratory', 'admin']);
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('license_number')->nullable();
            $table->string('business_name');
            $table->string('business_licenses')->nullable();
            $table->string('license_type')->nullable();
            $table->string('status')->default('active');
            $table->date('expiration_date')->nullable();
            $table->string('sos_registration_number')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('county')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();  // Changed from increments to bigIncrements
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('apiKey')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip', 10)->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            $table->foreign('role_id')->references('id')->on('roles');
        });
        
        Schema::create('user_organizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('organization_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
        Schema::create('organization_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('child_id');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unique(['parent_id', 'child_id']);
        });

        // Insert system-wide roles
        DB::table('roles')->insert([
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Has full access to all organizations and system-wide settings', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'org_admin', 'display_name' => 'Organization Admin', 'description' => 'Has full access within their organization', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'display_name' => 'Manager', 'description' => 'Can manage day-to-day operations within their organization', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'budtender', 'display_name' => 'Budtender', 'description' => 'Handles sales and customer interactions', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'customer', 'display_name' => 'Customer', 'description' => 'Regular customer account', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'wholesale_user', 'display_name' => 'Wholesale User', 'description' => 'Wholesale customer account', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_links');
        Schema::dropIfExists('user_organizations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('roles');
    }
};