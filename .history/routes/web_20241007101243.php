<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
// use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get( 'logout', [LoginController::class, 'logout']);
Route::get('clear_cache' , function() { 
    \Artisan::call("config:cache");
    \Artisan::call("view:clear");
    \Artisan::call("route:clear");
    \Artisan::call("config:clear");
    \Artisan::call("cache:clear");

    echo "Done";
});

Route::controller(HomeController::class)->group(function () {
	Route::get('/', 'index');
	Route::get('/home', 'index');
	Route::get('/about', 'about');
	Route::get('/faq', 'faqs');
	Route::get('/terms-condition', 'termsCondition');
	Route::get('/our-menu', 'ourMenu');
	Route::get('/contact-us', 'contact');
    Route::post('contact/save', 'contactSave');
    Route::get('/orders/{id}', 'show');
    Route::get('clear_cache', 'clearCache');
    // Route::post('/orders', 'store');
});



Route::get( 'localization/{locale}', [LocalizationController::class, 'index']);
// Route::get( 'admin', [DashboardController::class, 'index'])->name('home');
Route::get( 'admin', [DashboardController::class, 'index'])->name('home');
Route::get( 'dashboard', [DashboardController::class, 'index']);
Route::post( 'sales/online_order', [OrderController::class, 'completeSale']);
Route::post( 'orders/save', [OrderController::class, 'ChangeStatus']);
Route::get( 'online-orders', [OrderController::class, 'index']);
Route::get( 'orders', [OrderController::class, 'orders']);

Route::group(['middleware' => ['auth' , 'XssSanitizer']], function () {
    
  
    Route::controller(NewsletterController::class)->group(function () {
        Route::get('newsletters', "index");
        Route::post('newsletter/delete', "delete");
        Route::post('newsletter/store', "store");
    });
    Route::controller(SupplierController::class)->group(function () {
        Route::get('suppliers', 'index');
        Route::post('suppliers', 'store');
        Route::get('suppliers/{id}/edit', 'edit');
        Route::get('suppliers/create', 'create');
        Route::put('suppliers/{id}', 'update');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index');
        Route::post('users', 'store');
        Route::get('users/{id}/edit', 'edit');
        Route::get('users/create', 'create');
        Route::put('users/{id}', 'update');
    });
    Route::controller(CustomerController::class)->group(function () {
        Route::get('customers', 'index');
        Route::put('customers', 'store');
        Route::get('customers/{id}/edit', 'edit');
        Route::get('customers/create', 'create');
        Route::put('customers/{id}', 'update');
    });
    Route::controller(ProductController::class)->group(function () {
        Route::get('products', 'index');
        Route::post('products', 'store');
        Route::get('products/{id}/edit', 'edit');
        Route::get('products/create', 'create');
        Route::put('products/{id}', 'update');
        Route::delete('products/{id}', 'destroy');
        Route::post('product/upload_photo', 'uploadPhoto');
        Route::post('product/upload_photo_crop', 'updatePhotoCrop');
        Route::post('product/addToArchive', 'addToArchive');
    });
    
    Route::controller(CategoryController::class)->group(function () {
        Route::get('categories', 'index');
        Route::post('categories', 'store');
        Route::get('categories/{id}/edit', 'edit');
        Route::get('categories/create', 'create');
        Route::put('categories/{id}', 'update');
        Route::delete('categories/{id}', 'destroy');
        Route::post('category/upload_photo_crop', 'updatePhotoCrop');
    });
    Route::controller(SaleController::class)->group(function () {
        Route::get('sales/create', 'create');
        Route::post('sales/store', 'store');
        Route::get('sales/receipt/{id}', 'receipt');
        Route::post('sales/complete_sale', 'completeSale');
        Route::get('sales', 'index');
        Route::post('sales/cancel/{id}', 'cancel');
        Route::post('sale/hold_order', 'holdOrder');
        Route::post('sale/hold_orders', 'holdOrders');
        Route::post('sale/view_hold_order', 'viewHoldOrder');
        Route::post('sale/hold_order_remove', 'removeHoldOrder');
        Route::post('sales/change_order_status', 'changeOrderStatus');
    });
    Route::controller(InventoryController::class)->group(function () {
        Route::get('purchase_inventory', 'purchasedItems');
        Route::get('purchase_detail/{id}', 'purchasedDetail');
        Route::get('purchase_items', 'index');
        Route::get('edit_purchase/{id}', 'edit_purchase');
        Route::post('add_product_ajax', 'addProductAjax');
        Route::post('save_purchase_inventory', 'savePurchaseInventory');

        Route::get('customer_invoices', 'customerInvoices');
        Route::get('invoice_detail/{id}', 'invoiceDetail');
        Route::get('edit_customer_invoice/{id}', 'editCustomerInvoice');
        
        Route::get('create_customer_invoice', 'createCustomerInvoice');
        Route::post('save_customer_invoice', 'saveCustomerInvoice');
        Route::post('get_customer_product_ajax', 'addProductCustomerAjax');


        Route::get('quantity_alerts', 'quantityAlerts');
        Route::get('min_quantity_alert', 'minQuantity');
        Route::post('min_quantity_update', 'updateMinQuantity');

    });
    Route::controller(InventoryController::class)->group(function () {
        Route::get("/booking_types", 'index');
        Route::post("booking_types/save", 'store');
        Route::post("booking_types/get", 'get');
        Route::post("booking_types/delete", 'delete');

    });

    Route::controller(EmailController::class)->group(function () {
    //// Emails for Reports 
		Route::get("email/staff_sold", "index");
        Route::get("email/daily_sales", "DailySales");
        Route::get('admin/email_templates', 'email_templates');
        Route::post('admin/email_templates/store', 'storeTemplate');
        Route::get('admin/email_template/edit/{id}', 'edit_templates');

    });
    Route::controller(TemplateController::class)->group(function () {
    //// Emails for Reports 
    Route::get('admin/email_templates/get', 'getTemplate');
    Route::post('admin/email_templates/delete', 'deleteTemplate');
    Route::get('admin/email_template/test/{code}', 'testEmail');

    });
    Route::controller(EditorController::class)->group(function () {
    //// Emails for Reports 
    Route::get('editor/html', 'siteHtml');
    Route::post('html/save', 'saveHtml');

    });

    Route::controller(TrackingController::class)->group(function () {

        Route::get('update_inventory', 'index');
        Route::get('inventory', 'inventories');
        Route::post('adjust_quantity', 'updateQuantity');
        Route::get('update_werehouse_inventory', 'wherehouseInventory');
        Route::post('adjust_werehouse_quantity', 'updateWhereHouseQuantity');

    });

    


    // Route::resource('customers', 'CustomerController');
    Route::controller(CustomerController::class)->group(function () {
        Route::get('sales/findcustomer', 'findcustomer');
        Route::post('sales/store_customer', 'storeCustomer');
    });


    
    Route::controller(ReportController::class)->group(function () {
        Route::get('reports/sales_by_products', 'SalesByProduct');
        Route::get('reports/graphs', 'Graphs');
        Route::get('reports/expenses', 'expenses');
        Route::get('reports/staff_sold', 'staffSold');
        Route::get('reports/staff_log', 'staffLogs');
        Route::get('reports/staff_log/{id}', 'staffLogs');
		Route::get('reports/{type}', 'index');
        Route::get('reports/{type}/{id}', 'show');
        
    });


    Route::controller(PageController::class)->group(function () {
        Route::get('/pages', 'index');
        Route::post('/pages/save', 'save');
        Route::get('/pages/add', 'add');
        Route::get('/pages/delete/{id}', 'delete');
        Route::get('/pages/edit/{id}', 'edit');
    });
    Route::controller(SliderController::class)->group(function () {
        Route::get("/sliders", 'index');
        Route::post("slider/save", 'save');
        Route::post("slider/get", 'get');
        Route::post("slider/delete", 'delete');
    });
    Route::controller(ExpenseController::class)->group(function () {
        Route::get("/expenses", 'index');
        Route::post("expenses/save", 'store');
        Route::post("expenses/get", 'get');
        Route::post("expenses/delete", 'delete');
    });
    Route::controller(TableController::class)->group(function () {
        Route::get("/tables", 'index');
        Route::post("tables/save", 'store');
        Route::post("tables/get", 'get');
        Route::post("tables/delete", 'delete');
    });


    Route::controller(RoleController::class)->group(function () {
        Route::get('roles', 'index');
        Route::post('roles', 'store');
        Route::get('roles/edit/{id}', 'edit');
        Route::get('roles/create', 'create');
        Route::post('roles/update', 'update');
    });

  
    Route::group(['prefix' => 'settings'], function () {

        Route::controller(SettingController::class)->group(function () {
            Route::get('homepage', 'homePage');
            Route::post('homepage', 'homePageUpdate');
            Route::get('general', 'edit');
            Route::post('update', 'update');
        });
        Route::controller(ProfileController::class)->group(function () {
            Route::get('profile', 'edit');
            Route::post('profile', 'update');
            Route::post('update_password', 'updatePassword');
        });
    }
);


    
   


});




// Route::get('/', function () {
//     return view('welcome');
// });

Auth::routes();

