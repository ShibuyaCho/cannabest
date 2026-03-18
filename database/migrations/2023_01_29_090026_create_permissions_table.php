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
        Schema::create('permissions', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::statement("INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
        (1, 'view_sale', 'View Sales ', NULL, NULL, NULL),
        (2, 'add_sale', 'Add Sales', NULL, NULL, NULL),
        (3, 'add_product', 'Add Product ', NULL, NULL, NULL),
        (4, 'view_products', 'View Products', NULL, NULL, NULL),
        (5, 'edit_products', 'Edit Products', NULL, NULL, NULL),
        (6, 'delete_products', 'Delete Products', NULL, NULL, NULL),
        (7, 'add_category', 'Add Category ', NULL, NULL, NULL),
        (8, 'view_categorys', 'View Categorys', NULL, NULL, NULL),
        (9, 'edit_categorys', 'Edit Categorys', NULL, NULL, NULL),
        (10, 'delete_categorys', 'Delete Categorys', NULL, NULL, NULL),
        (11, 'add_expense', 'Add Expense ', NULL, NULL, NULL),
        (12, 'view_expense', 'View Expenses', NULL, NULL, NULL),
        (13, 'edit_expense', 'Edit Expenses', NULL, NULL, NULL),
        (14, 'delete_expense', 'Delete Expenses', NULL, NULL, NULL),
        (15, 'setting', 'Overall Setting', NULL, NULL, NULL),
        (16, 'frontend_setting', 'Frontend Setting', NULL, NULL, NULL),
        (17, 'reports', 'View Reports ', NULL, NULL, NULL),
        (18, 'roles', 'Manage Roles ', NULL, NULL, NULL),
        (19, 'dashboard', 'Dashboard', NULL, NULL, NULL),
        (20, 'users', 'Manage Users', NULL, NULL, NULL),
        (21, 'Profile', 'View Profile', NULL, NULL, NULL),
        (22, 'suppliers', 'Manage Suppliers', NULL, NULL, NULL),
        (23, 'customers', 'Manage Customers', NULL, NULL, NULL),
        (24, 'update_inventory', 'Update Inventory', NULL, NULL, NULL),
        (25, 'inventory', 'Inventory', NULL, NULL, NULL),
        (26, 'reservations', 'Reservations', NULL, NULL, NULL),
        (27, 'bookings', 'Bookings (Book Now)', NULL, NULL, NULL),
        (28, 'sales_staff_to_compelte_sales', 'Sales Staff to Complete Sales', 'Sales Staff to Complete Sales', NULL, NULL),
        (29, 'purchases', 'Purchases', NULL, NULL, NULL),
        (30, 'customer_invoice', 'Customer Invoices', NULL, NULL, NULL),
        (31, 'newsletters', 'Newsletters', NULL, NULL, NULL),
        (32, 'purchases', 'Purchases', NULL, NULL, NULL),
        (33, 'customer_invoices', 'Customer Invoices', NULL, NULL, NULL),
        (34, 'inventory', 'Inventory', NULL, NULL, NULL),
        (35, 'dashboard', 'Dashboard', NULL, NULL, NULL);
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
