<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; 
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Inventory;
use App\Http\Controllers\MetrcController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\DrawerController;

use App\Http\Controllers\{
    LocalizationController,
    OrganizationController,
    DashboardController,
    CustomerPortalController,
    NewsletterController,
    OrderController,
    SupplierController,
    ProductController,
    CategoryController,
    CustomerController,
    UserController,
    SaleController,
    ReportController,
    PageController,
    SliderController,
    ExpenseController,
    TableController,
    EditorController,
    RoleController,
    EmailController,
    SettingController,
    PermissionController,
    InventoryController,
    TrackingController,
    TemplateController,
    ProfileController,
    HomeController,
    ShiftController,
    WholesaleInventoryController,
    WholesaleController,
    WholesaleProductController,
    WholesaleSettingsController,
    BrandController,
    WholesaleFrontendController,
    BranchController,
    WholesaleCustomerController,
    ScrapingController,
    SuperAdminController,
    AdminController,
    WholesaleOrderController,
    PromotionalController,
    CustomizableContentController,
    SalesMetrcReconcileController,
    MetrcPushController,
    SalesExtraController,
    SalesMetrcController,
    InventoriesImportController
    
};

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
// --- METRC core ---
// routes/web.php


Route::middleware(['web','auth'])->group(function () {
    Route::post('/metrc/sync-inline/init',  [SalesMetrcReconcileController::class, 'syncInlineInit'])
        ->name('metrc.sync.inline.init');

    Route::post('/metrc/sync-inline/chunk', [SalesMetrcReconcileController::class, 'syncInlineChunk'])
        ->name('metrc.sync.inline.chunk');

    Route::post('/metrc/push-and-sync',     [MetrcPushController::class, 'pushAndSync'])
        ->name('metrc.push-and-sync');

    Route::get('/metrc/reconcile/candidates-ts', [SalesMetrcReconcileController::class, 'candidatesTs'])
        ->name('metrc.reconcile.candidates-ts');

    Route::post('/metrc/reconcile/link-ts',      [SalesMetrcReconcileController::class, 'linkTs'])
        ->name('metrc.reconcile.link-ts');

    Route::post('/metrc/relink/timestamp-window-inline', [SalesMetrcReconcileController::class, 'relinkTimestampWindowInline'])
        ->name('metrc.relink.window.inline');
});


Route::middleware(['web','auth'])->group(function () {
    Route::post('/metrc/sync-now', [MetrcController::class, 'syncNow'])
        ->name('metrc.sync.now');
});

// --- Sales extras (unchanged) ---
Route::get('/sales/items/{sale}', [SalesExtraController::class, 'items'])
    ->name('sales.items');
// receipt overrides / pkg-labels
Route::middleware('auth')->group(function () {
    Route::post('/sales/{sale}/receipt-overrides', [\App\Http\Controllers\SaleController::class, 'saveReceiptOverrides'])
        ->name('sales.receipt.overrides');
    Route::post('/sales/{sale}/receipt/pkg-labels', [\App\Http\Controllers\SaleController::class, 'saveReceiptPkgLabels'])
        ->name('sales.receipt.pkg');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/inventories/import-csv', [\App\Http\Controllers\InventoriesImportController::class, 'import'])
        ->name('inventories.import.csv');
    Route::get('/inventories/import-status/{k}', [\App\Http\Controllers\InventoriesImportController::class, 'status'])
        ->name('inventories.import.status');
});
// "Hard" sync alias → same action; clients can pass ?blocking=1 if needed
Route::match(['GET','POST'], '/metrc/sync/hard', [\App\Http\Controllers\SalesMetrcReconcileController::class, 'syncAndRefresh'])
    ->middleware('auth')
    ->name('metrc.sync.hard');

// dashboard bits (kept)
Route::middleware(['auth'])->group(function () {
    Route::get('/org-dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('retail.dashboard');
    Route::get('/org-dashboard/receipt/{sale}', [\App\Http\Controllers\DashboardController::class, 'receiptFragment'])
        ->name('org.dashboard.receipt');
});

// --- Reconcile aliases (mapped to existing methods) ---
Route::prefix('metrc/reconcile')->middleware(['auth'])->group(function () {
    // probe → ping
    Route::get('probe', [\App\Http\Controllers\SalesMetrcReconcileController::class, 'ping'])
        ->name('metrc.reconcile.probe');

    // sync/sync-step/run → syncAndRefresh
    Route::get('sync',      [\App\Http\Controllers\SalesMetrcReconcileController::class, 'syncAndRefresh']);
    Route::get('sync-step', [\App\Http\Controllers\SalesMetrcReconcileController::class, 'syncAndRefresh']);
    Route::get('run',       [\App\Http\Controllers\SalesMetrcReconcileController::class, 'syncAndRefresh']);

    // candidates / link
    Route::get('candidates', [\App\Http\Controllers\SalesMetrcReconcileController::class, 'candidates']);
    Route::post('link',      [\App\Http\Controllers\SalesMetrcReconcileController::class, 'link']);

    // archive (only works if method exists)
    Route::post('archive',   [\App\Http\Controllers\SalesMetrcReconcileController::class, 'archive']);

    // peek → debugSale
    Route::get('peek',       [\App\Http\Controllers\SalesMetrcReconcileController::class, 'debugSale']);
    Route::get('debug-sale', [\App\Http\Controllers\SalesMetrcReconcileController::class, 'debugSale']);
});


Route::get('/sales/receipt/{sale}/numbers', [App\Http\Controllers\SaleController::class, 'receiptNumbers'])
     ->name('sales.receipt.numbers');
// routes/api.php
 // JSON endpoints for this page (no Sanctum)
    Route::prefix('/org-dashboard/api')->group(function () {
        Route::get('/summary',            [DashboardController::class, 'summary']);
        Route::get('/sales-by-day',       [DashboardController::class, 'salesByDay']);
        Route::get('/hourly-heatmap',     [DashboardController::class, 'hourlyHeatmap']);
        Route::get('/category-mix',       [DashboardController::class, 'categoryMix']);
        Route::get('/discounts',          [DashboardController::class, 'discounts']);
        Route::get('/top-products',       [DashboardController::class, 'topProducts']);
        Route::get('/voids-returns',      [DashboardController::class, 'voidsReturns']);
        Route::get('/metrc/discrepancies',[DashboardController::class, 'metrcDiscrepancies']);
        Route::get('/metrc/unlinked',     [DashboardController::class, 'metrcUnlinked']);
    });


Route::get('/print/label/flower/{id}', [SaleController::class, 'printFlowerLabel']);
Route::get('/metricsaleData', [SaleController::class, 'metricsaleData']);
Route::middleware('auth')->get('/metrc/deliveries/{id}/packages', [MetrcController::class, 'getDeliveryPackages']);
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->isOrganizationAdmin()) {
            // Check the organization type
            $organizationType = $user->organization->type;
            
            if ($organizationType === 'retail') {
                return redirect()->route('retail.dashboard');
            } elseif ($organizationType === 'wholesale') {
                return redirect()->route('wholesale.admin.dashboard');
            } else {
                // If the organization type is not recognized, redirect to a default dashboard
                return redirect()->route('admin.dashboard');
            }
        } elseif ($user->isStandardUser()) {
            // You might want to differentiate between retail and wholesale standard users as well
            $organizationType = $user->organization->type;
            
            if ($organizationType === 'retail') {
                return redirect()->route('user.dashboard');
            } elseif ($organizationType === 'wholesale') {
                return redirect()->route('wholesale.user.dashboard');
            } else {
                return redirect()->route('user.dashboard');
            }
        } else {
            // If the user role is not recognized, redirect to a default dashboard
            return redirect()->route('admin.dashboard');
        }
    } else {
        // If the user is not authenticated, redirect to the promotional page
        return redirect()->route('promotional.show');
    }
})->name('home');

Route::post('/timeclock/in', [TimeClockController::class, 'clockIn']);
Route::post('/timeclock/out', [TimeClockController::class, 'clockOut']);
Route::get('/retail-marketplace', [CustomerController::class, 'retailMarketplace'])
    ->name('retail.public-marketplace');

// Org-specific public menu
Route::get('/retail-marketplace/{organization}', [CustomerController::class, 'retailOrganizationMenu'])
    ->name('retail.org.menu');
Route::get('/marketplace/retail/{inventory}', [CustomerController::class, 'retailProduct'])
    ->name('retail.product.show');
Route::get('/wholesale-marketplace', [CustomerController::class, 'wholesaleMarketplace'])->name('wholesale.public-marketplace');

Route::post('/sales/{id}/cancel', [\App\Http\Controllers\SaleController::class, 'cancel'])
    ->name('sales.cancel');

Route::controller(App\Http\Controllers\SaleController::class)->group(function() {
    // Sales listing with date filter and EOD
    Route::get('sales', 'index')->name('sales.index');

    // Update local records after Metrc submission
    Route::get('updateSaleData', 'updateMetricData')->name('sales.update');
    // View receipt modal
    Route::get('sales/receipt/{sale}', 'receipt')->name('sales.receipt');
});

Route::get('sales', [SaleController::class,'index'])
     ->name('sales.index');

Route::get('sales/cancel/{sale}', [SaleController::class, 'cancel'])
     ->name('sales.cancel');
Route::post('inventories/sync-metrc', [InventoryController::class, 'syncMetrc'])
     ->name('inventories.syncMetrc')
     ->middleware('auth');
Route::get('sale/hold_orders/{id}', [SaleController::class, 'showHoldOrder']);

Route::get('/getSaleDataforMetric', [\App\Http\Controllers\SaleController::class, 'metricsaleData']);
Route::post('/sales/submit-to-metrc', [SaleController::class,'proxyReceipts'])
     ->middleware('role:2');
Route::middleware(['auth','role:2,3,4'])
     ->prefix('admin')
     ->group(function () {
    Route::get('drawers',   [DrawerController::class,'index'])
         ->name('admin.drawers.index');

    Route::post('drawers',  [DrawerController::class,'store'])
         ->name('admin.drawers.store');

    Route::put('drawers/{drawer}', [DrawerController::class,'update'])
         ->name('admin.drawers.update');

    Route::delete('drawers/{drawer}', [DrawerController::class,'destroy'])
         ->name('admin.drawers.destroy');
});
Route::post('/drawer/select', [DrawerController::class, 'select'])
     ->middleware('role:2')
     ->name('drawer.select');
// cashier/open API
Route::middleware('role:2')->group(function () {
    Route::post('drawers/open',  [DrawerController::class,'open'])
         ->name('drawers.open');
    Route::post('drawers/close', [DrawerController::class,'close'])
         ->name('drawers.close');
    Route::get('drawers/current',[DrawerController::class,'current'])
         ->name('drawers.current');
});

Route::get('/promotional', [PromotionalController::class, 'show'])->name('promotional.show');
Route::post('/promotional/signup', [PromotionalController::class, 'signup'])->name('promotional.signup');
// Customer Portal routes
Route::middleware(['auth', 'check.role:5'])->group(function () {
    Route::get('/retail-dashboard', [CustomerController::class, 'retailDashboard'])
        ->name('retail.customer.dashboard');

    Route::get('/retail-organization/{organization}', [CustomerController::class, 'organizationProducts'])
        ->name('retail.customer.organization-products');

    Route::get('/retail-product/{product}', [CustomerController::class, 'showProduct'])
        ->name('retail.customer.product.show');

    Route::post('/retail-order', [CustomerController::class, 'placeOrder'])
        ->name('retail.customer.order.place');

    Route::get('/retail-account', [CustomerController::class, 'account'])
        ->name('retail.customer.account');

    Route::get('/retail-orders', [CustomerController::class, 'orders'])
        ->name('retail.customer.orders');

    Route::get('/retail-order/{order}', [CustomerController::class, 'showOrder'])
        ->name('retail.customer.order.show');
});

// Customer Portal Routes

Route::get('/organization/{organization}/branch/{branch}', [CustomerPortalController::class, 'branchProducts'])->name('branch.products');
Route::get('/product/{product}', [CustomerPortalController::class, 'showProduct'])->name('product.show');

Route::get('/wholesale/profile', [WholesaleController::class, 'profile'])->name('wholesale.profile');
Route::get('/wholesale/products/create', [WholesaleProductController::class, 'create'])->name('wholesale.products.create');
Route::post('/wholesale/products', [WholesaleProductController::class, 'store'])->name('wholesale.products.store');

Route::get('/dashboard', function() {
    return redirect('/admin');
});
// Logout and cache-clear routes
Route::get('logout', [LoginController::class, 'logout']);
Route::get('clear_cache', function() { 
    \Artisan::call("config:cache");
    \Artisan::call("view:clear");
    \Artisan::call("route:clear");
    \Artisan::call("config:clear");
    \Artisan::call("cache:clear");
    echo "Done";
});

// Home and public pages
Route::controller(HomeController::class)->group(function () {
    // Add this route for /thcfg:
    Route::get('/thcfg', 'index')->name('frontend.thcfg');
    Route::get('/about', 'about');
    Route::get('/faq', 'faqs');
    Route::get('/terms-condition', 'termsCondition');
    Route::get('/our-menu', 'ourMenu');
    Route::get('/contact-us', 'contact');
    Route::post('contact/save', 'contactSave');
    Route::get('/orders/{id}', 'show');
    Route::get('clear_cache', 'clearCache');
});

// Localization route
Route::get('localization/{locale}', [LocalizationController::class, 'index'])->name('localization');

// Define your custom login route first:
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Then include the default authentication routes if needed:
// Auth::routes();
Route::controller(CustomerController::class)->group(function () {
    Route::get('customers', 'index')->name('customers.index');
    Route::get('customers/create', 'create')->name('customers.create');
    Route::post('customers', 'store')->name('customers.store');
    Route::get('customers/{id}', 'show')->name('customers.show');
    Route::get('customers/{id}/edit', 'edit')->name('customers.edit');
    Route::put('customers/{id}', 'update')->name('customers.update');
    Route::delete('customers/{id}', 'destroy')->name('customers.destroy');
});

Route::controller(CustomerController::class)->group(function () {
    Route::get('sales/findcustomer', 'findcustomer');
    Route::post('sales/store_customer', 'storeCustomer');
});
// Admin routes
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::middleware('check.role:1,2')->group(function () {
      
        // Orders
        Route::post('sales/online_order', [OrderController::class, 'completeSale'])->name('admin.online_order');
        Route::post('orders/save', [OrderController::class, 'ChangeStatus'])->name('admin.order.save');
        Route::get('online-orders', [OrderController::class, 'index'])->name('admin.online_orders');
        Route::get('orders', [OrderController::class, 'orders'])->name('admin.orders');

       
    });

    // Employee registration (you might want to restrict this to Super Admin only)
   
       

    // Routes that require authentication and additional sanitization
    Route::middleware('XssSanitizer')->group(function () {
        // ... (keep existing route groups)
    });

    // ... (keep other existing route groups)
});


 

Route::get('/admin', [AdminController::class, 'index'])
    ->name('admin.index')
    ->middleware('check.role:1,3,2');

// Super Admin routes

Route::prefix('superadmin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/organizations', [SuperAdminController::class, 'getOrganizations']);
    Route::get('/organization/{id}', [SuperAdminController::class, 'getOrganization']);
    Route::put('/organization/{id}', [SuperAdminController::class, 'updateOrganization']);
    Route::get('/superadmin/users', [SuperAdminController::class, 'getUsers']);
    Route::post('/organization', [SuperAdminController::class, 'createOrganization']);
    Route::post('/user', [SuperAdminController::class, 'createUser']);
    Route::post('/link-user-org', [SuperAdminController::class, 'linkUserToOrganization']);
    Route::get('/organization/{id}/users', [SuperAdminController::class, 'getOrganizationUsers']);
    Route::get('/user/{id}/organizations', [SuperAdminController::class, 'getUserOrganizations']);
    Route::delete('/organization/{id}', [SuperAdminController::class, 'deleteOrganization']);
    Route::delete('/user/{id}', [SuperAdminController::class, 'deleteUser']);
   
});

// Add this route for the superadmin login
Route::get('/superadmin/login', [SuperAdminController::class, 'showLoginForm'])->name('superadmin.login');
Route::post('/superadmin/login', [SuperAdminController::class, 'login']);

// Customer-specific routes (protected by auth middleware and role check)


// If you want some routes accessible to all authenticated users (both wholesalers and retailers)
Route::middleware(['auth'])->prefix('wholesale')->name('wholesale.')->group(function () {
    Route::get('/products', [WholesaleProductController::class, 'index'])->name('products.index');
    Route::get('/products/{id}', [WholesaleProductController::class, 'show'])->name('products.show');
});

// Routes that require authentication and additional sanitization
Route::group(['middleware' => ['auth', 'XssSanitizer']], function () {
    
    Route::controller(NewsletterController::class)->group(function () {
        Route::get('newsletters', "index");
        Route::post('newsletter/delete', "delete");
        Route::post('newsletter/store', "store");
    });
    Route::post('/wholesale/store', [WholesaleInventoryController::class, 'store'])->name('wholesale.store');
    Route::post('/wholesale/import-products', [WholesaleController::class, 'importProducts'])->name('wholesale.import-products');
    Route::post('/wholesale/store-product', [WholesaleController::class, 'storeProduct'])->name('wholesale.store-product');
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
        // Removed the /login route from here.
    });
    
 
    
    Route::controller(ProductController::class)->group(function () {
        Route::get('products', 'index');
        Route::post('products', 'store')->name('products.store');
        Route::get('products/{id}/edit', 'edit');
        Route::get('products/create', [ProductController::class, 'create']);
        Route::put('products/{id}', 'update');
        Route::delete('products/{id}', 'destroy')->name('products.destroy');
        Route::post('product/upload_photo', 'uploadPhoto');
        Route::post('product/upload_photo_crop', 'updatePhotoCrop');
        Route::post('product/addToArchive', 'addToArchive');
    });
    
    Route::controller(CategoryController::class)->group(function () {
        Route::get('categories', 'index');
        Route::get('get_all_categories', 'get_all_categories');
         
        Route::post('categories', 'store');
        Route::post('categories_ajax', 'store_ajax');
        Route::get('categories/{id}/edit', 'edit');
        Route::get('categories/create', 'create');
        Route::put('categories/{id}', 'update');
        Route::delete('categories/{id}', 'destroy');
    });
    Route::post('/categories/closest', [CategoryController::class, 'findClosestCategory'])->name('categories.closest');





    Route::get('shift/clockin', function (Request $request) {
        return app(ShiftController::class)->clockIn($request);
    });
    
    Route::get('shift/clockout/{id}', function (Request $request, $id) {
        return app(ShiftController::class)->clockOut($request, $id);
    });
    Route::get('/products/search', [\App\Http\Controllers\ProductController::class,'searchByName']);
    Route::get('shift/last/{cashierId}', function (Request $request, $cashierId) {
        return app(ShiftController::class)->getLastShift($request, $cashierId);
    });
    Route::post('/sales/recalc-price', 'SaleController@recalcPrice')->name('sales.recalcPrice');
    // Retail User routes
Route::middleware(['auth', 'check.role:3,2,4'])->group(function () {
    Route::controller(SaleController::class)->group(function () {
        Route::get('sales/create', 'create')->name('sales.create');
        Route::post('sales/store', 'store');
        Route::get('sales/receipt/{id}', 'receipt');
        Route::post('sales/complete_sale', 'completeSale')->name('sales.complete_sale');
        Route::get('sales', 'index');
        Route::post('sales/cancel/{id}', 'cancel');
        Route::post('sale/hold_order', 'holdOrder');
        Route::post('sale/hold_orders', 'holdOrders');
        Route::get('sale/hold_orders/{id}', 'getHoldOrder');
        Route::post('sale/view_hold_order', 'viewHoldOrder');
        Route::post('sale/hold_order_remove/{id}', 'removeHoldOrder');
        Route::post('sales/change_order_status', 'changeOrderStatus');
        Route::post('api/discounted-price', 'getDiscountedPrice')->name('api.discountedPrice');
    });

Route::prefix('admin')->group(function () {
    Route::delete('inventories/{inventory}', [InventoryController::class, 'destroy'])
        ->name('inventories.destroy');
});
Route::get('inventories/{inventory}/print-label', [InventoryController::class, 'printLabel'])
     ->name('inventories.print-label');

Route::get('/inventory/search', [\App\Http\Controllers\InventoryController::class, 'search']);

    Route::controller(InventoryController::class)->group(function () {
            Route::delete('inventories/{inventory}', 'destroy')
         ->name('inventories.destroy');
        Route::post('/inventory/{inventory}/reserve', 'reserve');
        Route::post('/inventory/{inventory}/release', 'release');
        Route::post('/inventory/release-all', 'releaseAll');     
        Route::get('/inventory/availability', 'availability');  
        Route::get('purchase_items', 'index')->name('inventories.index');
        Route::post('inventories/{inventory}/update-type', 'updateType')
         ->name('inventories.updateType');
        Route::get('purchase_inventory', 'purchasedItems');
        Route::get('purchase_detail/{id}', 'purchasedDetail');
        Route::get('inventories/{id}/edit', 'edit')->name('inventories.edit');
        Route::put('inventories/{id}', 'update')->name('inventories.update');
        Route::get('edit_purchase/{id}', 'edit_purchase');
        Route::post('add_product_ajax', 'addProductAjax');
        Route::post('save_purchase_inventory', 'savePurchaseInventory');
        Route::get('inventories/{inventory}/print-label', 'printLabel')
         ->name('inventories.printLabel');
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
Route::middleware('role:2')->group(function(){
    Route::get ('/organization/settings',  [OrganizationController::class,'edit'])
         ->name('organizations.edit');
    Route::put ('/organization/settings',  [OrganizationController::class,'update'])
         ->name('organizations.update');
});
Route::get('/api/inventories/stock', function(Request $request) {
    try {
        $ids = $request->query('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No IDs provided'], 400);
        }

        $stocks = \App\Inventory::whereIn('id', $ids)
                   ->get(['id','storeQty']);

        if ($stocks->isEmpty()) {
            return response()->json(['error' => 'No matching inventory items found'], 404);
        }

        return response()->json($stocks);
    } catch (\Exception $e) {
        \Log::error('Error in /api/inventories/stock: ' . $e->getMessage());
        return response()->json(['error' => 'An unexpected error occurred', 'message' => $e->getMessage()], 500);
    }
})->name('api.inventories.stock');
});

// Routes accessible to all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::controller(SaleController::class)->group(function () {
        Route::get('updateSaleData', 'updateSaleData');
        Route::get('getSaleDataforMetric', 'metricsaleData');
    });
});




Route::get('/metrc/sync/status', [\App\Http\Controllers\MetrcController::class, 'status'])
  ->name('metrc.sync.status')
  ->middleware('auth');

    Route::resource('wholesaleInventories', WholesaleInventoryController::class);

Route::controller(InventoryController::class)->group(function () {
    Route::get("/booking_types", 'index');
    Route::post("booking_types/save", 'bookingTypesSave'); // ✅ define this new method if you need it
});

    Route::controller(InventoryController::class)->group(function () {
        Route::get("/booking_types", 'index');
      
        Route::post("booking_types/get", 'get');
        Route::post("booking_types/delete", 'delete');
    });
    Route::middleware('auth')->group(function () {
    Route::post('/inventories/{inventory}/subtype', [InventoryController::class, 'updateSubtype'])
        ->name('inventories.updateSubtype');
    Route::get('/inventories/subtypes', [InventoryController::class, 'listSubtypes'])
        ->name('inventories.listSubtypes');
});
    Route::controller(EmailController::class)->group(function () {
        Route::get("email/staff_sold", "index");
        Route::get("email/daily_sales", "DailySales");
        Route::get('admin/email_templates', 'email_templates');
        Route::post('admin/email_templates/store', 'storeTemplate');
        Route::get('admin/email_template/edit/{id}', 'edit_templates');
    });
    
    Route::controller(TemplateController::class)->group(function () {
        Route::get('admin/email_templates/get', 'getTemplate');
        Route::post('admin/email_templates/delete', 'deleteTemplate');
        Route::get('admin/email_template/test/{code}', 'testEmail');
    });
    
    Route::controller(EditorController::class)->group(function () {
        Route::get('editor/html', 'siteHtml');
        Route::post('html/save', 'saveHtml');
    });
    
    Route::controller(TrackingController::class)->group(function () {
        Route::get('update_inventory', 'index')->name('update_inventory');
        Route::get('inventory', 'inventories');
        Route::post('adjust_quantity', 'updateQuantity');
        Route::get('update_werehouse_inventory', 'wherehouseInventory');
        Route::post('adjust_werehouse_quantity', 'updateWhereHouseQuantity');
    });
    
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
    
    // General settings for all authenticated users
    Route::middleware(['auth'])->group(function () {
        Route::prefix('settings')->group(function () {
            Route::controller(SettingController::class)->group(function () {
                Route::get('general', 'edit')->name('settings.general.edit');
                Route::post('general', 'update')->name('settings.general.update');
                Route::get('homepage', 'homePage')->name('settings.homepage.edit');
                Route::post('homepage', 'homePageUpdate')->name('settings.homepage.update');
                Route::post('update', 'update')->name('settings.update');
            });
            
            Route::controller(ProfileController::class)->group(function () {
                Route::get('profile', 'edit')->name('settings.profile.edit');
                Route::post('profile', 'update')->name('settings.profile.update');
                Route::post('update_password', 'updatePassword')->name('settings.profile.update_password');
            });
        });
    });
    Route::get('/api/wholesale-products', [WholesaleProductController::class, 'getWholesaleProducts'])
    ->name('api.wholesale-products')
    ->middleware('auth');
    Route::get('/products/existing', [ProductController::class, 'getExistingProducts'])->name('products.existing');
    
    // Instead, place this outside any other group
    // Wholesaler-specific routes
    Route::middleware(['auth', 'wholesale'])->prefix('wholesale')->name('wholesale.')->group(function () {
            Route::get('/admin/dashboard', [WholesaleController::class, 'adminDashboard'])->name('admin.dashboard');
            
            // Wholesale Settings
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [WholesaleSettingsController::class, 'index'])->name('index');
                Route::get('/edit', [WholesaleSettingsController::class, 'edit'])->name('edit');
                Route::post('/', [WholesaleSettingsController::class, 'update'])->name('update');
            });
    
            Route::get('admin/customize/{pageName}', [CustomizableContentController::class, 'edit'])->name('admin.customize');
            Route::put('admin/customize/{pageName}', [CustomizableContentController::class, 'update'])->name('admin.customize.update');
        
        Route::middleware(['auth', 'wholesale'])->prefix('wholesale')->name('wholesale.')->group(function () {
            // ... existing routes ...
        
            Route::controller(WholesaleOrderController::class)->group(function () {
                Route::get('/orders', 'index')->name('orders.index');
                Route::get('/orders/create', 'create')->name('orders.create');
                Route::post('/orders', 'store')->name('orders.store');
                Route::get('/orders/{order}', 'show')->name('orders.show');
                Route::get('/orders/{order}/edit', 'edit')->name('orders.edit');
                Route::put('/orders/{order}', 'update')->name('orders.update');
                Route::delete('/orders/{order}', 'destroy')->name('orders.destroy');
                Route::post('/orders/{order}/update-status', 'updateStatus')->name('orders.update-status');
                Route::get('/orders/manage', 'manage')->name('orders.manage');
            });
        
            // ... other existing routes ...
        });
            Route::get('/cart', [WholesaleOrderController::class, 'cart'])->name('cart');
            Route::post('/cart/add', [WholesaleOrderController::class, 'addToCart'])->name('cart.add');
            Route::post('/cart/remove', [WholesaleOrderController::class, 'removeFromCart'])->name('cart.remove');
            Route::post('/cart/clear', [WholesaleOrderController::class, 'clearCart'])->name('cart.clear');
            Route::post('/order/place', [WholesaleOrderController::class, 'placeOrder'])->name('order.place');
        
            Route::get('/recent-orders', [WholesaleController::class, 'recentOrders'])->name('recent-orders');
        
            // ... other existing routes ...
        });
        // Wholesale Brands
        Route::resource('brands', BrandController::class);
        
        // Inventory Management
        Route::get('/inventory', [WholesaleInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/update', [WholesaleInventoryController::class, 'update'])->name('inventory.update');
        
        Route::get('/recent-orders', [WholesaleController::class, 'recentOrders'])->name('recent-orders');
    });
    
    // Wholesale routes

    
    Route::group(['middleware' => ['auth'], 'prefix' => 'wholesale', 'as' => 'wholesale.'], function () {
        Route::get('/employee/dashboard', [WholesaleController::class, 'employeeDashboard'])->name('employee.dashboard');
    });
    
    // Authenticated Wholesale routes (for both wholesalers and retailers)
    Route::middleware(['auth'])->prefix('wholesale')->name('wholesale.')->group(function () {
       
        Route::get('/profile', [WholesaleController::class, 'profile'])->name('profile');
        Route::post('/update-user', [WholesaleController::class, 'updateUser'])->name('update-user');
        
        // Product browsing for retailers
        Route::get('/products', [WholesaleProductController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [WholesaleProductController::class, 'show'])->name('products.show');
        
        // Order management for retailers
        Route::get('/cart', [WholesaleOrderController::class, 'cart'])->name('cart');
        Route::post('/cart/add', [WholesaleOrderController::class, 'addToCart'])->name('cart.add');
        Route::post('/cart/remove', [WholesaleOrderController::class, 'removeFromCart'])->name('cart.remove');
        Route::post('/order/place', [WholesaleOrderController::class, 'placeOrder'])->name('order.place');
       
        Route::get('/orders/{id}', [WholesaleOrderController::class, 'show'])->name('orders.show');
    });
    
    Route::group(['middleware' => ['auth', 'check.role:1,2,6'], 'prefix' => 'wholesale', 'as' => 'wholesale.'], function () {
        Route::get('/profile', [WholesaleController::class, 'dashboard'])->name('profile');
        // You can add more public-facing wholesale routes here
    
        
        // Wholesale Settings
        Route::get('/settings', [WholesaleSettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/edit', [WholesaleSettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [WholesaleSettingsController::class, 'update'])->name('settings.update');
        
        // Wholesale Products
        Route::resource('products', WholesaleProductController::class)->except(['update']);
        Route::post('/wholesale/products', [WholesaleProductController::class, 'store'])->name('wholesale.products.store');
        Route::get('/wholesale/products/get-package-info', [WholesaleProductController::class, 'getPackageInfo'])->name('wholesale.products.getPackageInfo');
        
        // Wholesale Orders
      
        
        // Wholesale License
        Route::get('/license', [WholesaleSettingsController::class, 'getLicenseNumber'])->name('license.get');
        Route::post('/license', [WholesaleSettingsController::class, 'saveLicenseNumber'])->name('license.save');
        
        // Wholesale Brands
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
 
        Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    });
 // Admin routes
Route::prefix('admin')->middleware('auth')->group(function () {

    Route::controller(OrderController::class)->group(function () {
        Route::post('sales/online_order', 'completeSale')->name('admin.online_order');
        Route::post('orders/save', 'ChangeStatus')->name('admin.order.save');
        Route::get('online-orders', 'index')->name('admin.online_orders');
        Route::get('orders', 'orders')->name('admin.orders');
    });
    Route::resource('orders', OrderController::class);

    // Employee registration
    Route::post('/employee/register', [UserController::class, 'store'])->name('employee.register');

    // Routes that require authentication and additional sanitization
    Route::middleware('XssSanitizer')->group(function () {
        // Newsletter routes
        Route::controller(NewsletterController::class)->group(function () {
            Route::get('newsletters', 'index');
            Route::post('newsletter/delete', 'delete');
            Route::post('newsletter/store', 'store');
        });

        // Wholesale routes
       

        // Other resource routes
        Route::resources([
            'suppliers' => SupplierController::class,
            'users' => UserController::class,
            'categories' => CategoryController::class,
            'inventories' => InventoryController::class,
            'wholesaleInventories' => WholesaleInventoryController::class,
        ]);

        // Product routes
        Route::controller(ProductController::class)->group(function () {
            // ... (keep existing product routes)
        });

        // ... (keep other existing route groups)

        // Wholesale specific routes
        // Wholesale routes
        Route::middleware(['auth', 'wholesale'])->group(function () {
            // Admin Wholesale routes
            Route::prefix('admin/wholesale')->name('admin.wholesale.')->group(function () {
                Route::get('/dashboard', [WholesaleController::class, 'adminDashboard'])->name('dashboard');
                Route::get('/profile', [WholesaleController::class, 'profile'])->name('profile');
                Route::get('/customize/{pageName}', [CustomizableContentController::class, 'edit'])->name('customize');
                Route::put('/customize/{pageName}', [CustomizableContentController::class, 'update'])->name('customize.update');
        
                // Wholesale Settings
                Route::prefix('settings')->name('settings.')->group(function () {
                    Route::get('/', [WholesaleSettingsController::class, 'index'])->name('index');
                    Route::get('/edit', [WholesaleSettingsController::class, 'edit'])->name('edit');
                    Route::post('/', [WholesaleSettingsController::class, 'update'])->name('update');
                });
                
                // Wholesale Products
                Route::resource('products', WholesaleProductController::class);
                Route::get('/products/get-package-info', [WholesaleProductController::class, 'getPackageInfo'])->name('products.getPackageInfo');
                
               Route::get('/inventories/create', [WholesaleInventoryController::class, 'create'])->name('inventories.create');
               Route::resource('wholesaleInventories', WholesaleInventoryController::class);

                // Wholesale Orders
               
                Route::get('/orders/create', [WholesaleOrderController::class, 'create'])->name('orders.create');
                Route::post('/orders', [WholesaleOrderController::class, 'store'])->name('orders.store');
                Route::get('/orders/{order}', [WholesaleOrderController::class, 'show'])->name('orders.show');
                Route::get('/orders/{order}/edit', [WholesaleOrderController::class, 'edit'])->name('orders.edit');
                Route::put('/orders/{order}', [WholesaleOrderController::class, 'update'])->name('orders.update');
                Route::delete('/orders/{order}', [WholesaleOrderController::class, 'destroy'])->name('orders.destroy');
                Route::post('/orders/{order}/update-status', [WholesaleOrderController::class, 'updateStatus'])->name('orders.update-status');
                
                // Wholesale License
                Route::get('/license', [WholesaleSettingsController::class, 'getLicenseNumber'])->name('license.get');
                Route::post('/license', [WholesaleSettingsController::class, 'saveLicenseNumber'])->name('license.save');
                
                // Wholesale Brands
                Route::resource('brands', BrandController::class);
            });
        
            // Employee Wholesale routes
            Route::prefix('wholesale')->name('wholesale.')->group(function () {
                Route::get('/employee/dashboard', [WholesaleController::class, 'employeeDashboard'])->name('employee.dashboard');
                Route::get('/dashboard', [WholesaleController::class, 'dashboard'])->name('dashboard');
                Route::get('/profile', [WholesaleController::class, 'profile'])->name('profile');
                Route::get('/recent-orders', [WholesaleController::class, 'recentOrders'])->name('recent-orders');
                Route::post('/wholesale/update-user', [WholesaleController::class, 'updateUser'])->name('wholesale.update-user');

            });
        });
    });
});



Route::get('/admin/wholesale/products/packages', [WholesaleProductController::class, 'getAllPackages'])
    ->name('wholesale.products.packages')
    ->middleware(['auth', 'wholesale']);

// Routes that need to be accessible outside the wholesale middleware
Route::middleware(['auth'])->group(function () {
    Route::get('/wholesale/products/get-all-packages', [WholesaleProductController::class, 'getAllPackages'])
        ->name('wholesale.products.getAllPackages');
});

Route::middleware(['auth', 'check.role:6,2'])->group(function () {
    Route::get('/wholesale-dashboard', [WholesaleCustomerController::class, 'index'])
        ->name('wholesale.customer.dashboard');
     
    Route::get('/organization/{organization}/brands', [WholesaleCustomerController::class, 'organizationBrands'])
        ->name('wholesale.customer.organization-brands');
    });

Route::middleware(['auth', 'wholesale'])->prefix('wholesale')->name('wholesale.')->group(function () {
    // ... existing routes ...

    Route::controller(WholesaleOrderController::class)->group(function () {
        Route::get('/orders', 'index')->name('orders.index');
        Route::get('/orders/create', 'create')->name('orders.create');
        Route::post('/orders', 'store')->name('orders.store');
        Route::get('/orders/{order}', 'show')->name('orders.show');
        Route::get('/orders/{order}/edit', 'edit')->name('orders.edit');
        Route::put('/orders/{order}', 'update')->name('orders.update');
        Route::delete('/orders/{order}', 'destroy')->name('orders.destroy');
        Route::post('/orders/{order}/update-status', 'updateStatus')->name('orders.update-status');
        Route::get('/orders/manage', 'manage')->name('orders.manage');
    });

    // ... other existing routes ...
});
// Public Wholesale routes (no auth required)
Route::prefix('wholesale')->name('wholesale.')->group(function () {
    Route::get('/', [WholesaleFrontendController::class, 'index'])->name('frontend.index');
    Route::get('/brands/{id}', [WholesaleFrontendController::class, 'showBrand'])->name('frontend.brand');
    Route::get('/products/{id}', [WholesaleFrontendController::class, 'showProduct'])->name('frontend.product');
    Route::get('/wholesalers/{id}', [WholesaleFrontendController::class, 'showWholesaler'])->name('frontend.wholesaler');
});

// Retail routes
Route::group(['middleware' => ['auth'], 'prefix' => 'admin/retail', 'as' => 'admin.retail.'], function () {
    Route::get('/dashboard', [RetailController::class, 'adminDashboard'])->name('dashboard');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'retail', 'as' => 'retail.'], function () {
    Route::get('/employee/dashboard', [RetailController::class, 'employeeDashboard'])->name('employee.dashboard');
});

// Error routes
Route::get('/error/no-organization', [ErrorController::class, 'noOrganization'])->name('error.no-organization');
Route::get('/error/unknown-org-type', [ErrorController::class, 'unknownOrgType'])->name('error.unknown-org-type');
Route::get('/error/unknown-role', [ErrorController::class, 'unknownRole'])->name('error.unknown-role');
Route::prefix('admin/wholesale')->name('admin.wholesale.')->group(function () {
    // ... existing routes ...

    // Wholesale Orders
  
    Route::get('/orders/{id}', [WholesaleOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/create', [WholesaleOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [WholesaleOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}/edit', [WholesaleOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}', [WholesaleOrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{id}', [WholesaleOrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('/orders/{id}/update-status', [WholesaleOrderController::class, 'updateStatus'])->name('orders.update-status');
    
    // ... other routes ...
});
   
    // ... other routes ...

Route::resource('branches', BranchController::class);

Route::get('/wholesale-customer', [WholesaleCustomerController::class, 'index'])->name('wholesale.customer.index');
Route::get('/wholesale-customer/brand/{brand}', [WholesaleCustomerController::class, 'brandProducts'])->name('wholesale.customer.brand-products');
Route::resource('custom_pages', CustomPageController::class);
