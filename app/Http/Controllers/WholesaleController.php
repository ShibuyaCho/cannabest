<?php
// app/Http/Controllers/WholesaleInventoryController.php

namespace App\Http\Controllers;

use App\Models\WholesaleInventory;
use App\Models\Category;
use App\Models\WholesaleProduct;
use Illuminate\Http\Request;
use App\Http\Requests\WholesaleInventoryRequest;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;
use App\Models\Brand;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use App\Models\WholesaleSetting; // Added import for WholesaleSetting
use App\Models\Organization;
use App\Models\WholesaleOrder;


class WholesaleController extends Controller
{
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
    public function recentOrders()
    {
        // Assuming you have a Sale model and a relationship with the wholesale user
        $recentOrders = auth()->user()->sales()->latest()->take(5)->get();

        return response()->json($recentOrders);
    }

    public function settings()
    {
        // Add any logic you need for the settings page
        return view('backend.wholesale.settings');
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        $wholesale = WholesaleInventory::where('organization_id', $user->organization_id)->first();
        $brands = Brand::where('organization_id', $user->organization_id)->get();
        $recentActivities = Activity::where('organization_id', $user->organization_id)
                                    ->orderBy('created_at', 'desc')
                                    ->take(5)
                                    ->get();

        return view('backend.wholesale.dashboard', compact('user', 'wholesale', 'brands', 'recentActivities'));
    }

public function brand()
{
    return $this->belongsTo(Brand::class);
}

public function category()
{
    return $this->belongsTo(Category::class);
}

    public function wholesale()
    {
        return $this->belongsTo(Wholesale::class);
    }

    public function products()
    {
        return $this->hasMany(WholesaleProduct::class);
    }


    public function profile()
    {
        $user = Auth::user();
        $organization = $user->organization;
        $wholesale = WholesaleInventory::where('organization_id', $user->organization_id)->first();
        
        // Fetch brands associated with the user's wholesale organization
        $brands = Brand::where('organization_id', $user->organization_id)->get();

        // Fetch wholesale settings if they exist
        $wholesaleSettings = WholesaleSetting::where('organization_id', $user->organization_id)->first();

        return view('backend.wholesale.profile', compact('user', 'wholesale', 'brands', 'organization', 'wholesaleSettings'));
    }

    public function storeProduct(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            foreach ($request->products as $productData) {
                $product = WholesaleProduct::create([
                    'name' => $productData['name'],
                    'display_name' => $productData['display_name'] ?? $productData['name'],
                    'description' => $productData['description'] ?? '',
                    'price' => $productData['price'] ?? 0,
                    'sku' => $productData['sku'] ?? '',
                    'category_id' => $productData['category_id'] ?? null,
                    'extraName' => $productData['display_name'] ?? $productData['name'],
                    'organization_id' => $user->organization_id,
                ]);

                if (isset($productData['packages']) && is_array($productData['packages'])) {
                    foreach ($productData['packages'] as $package) {
                        WholesaleInventory::create([
                            'organization_id' => $user->organization_id,
                            'product_id' => $product->id,
                            'package_id' => $package['Label'] ?? $package['package_id'],
                            'name' => $product->name,
                            'quantity' => $package['Quantity'] ?? $package['quantity'] ?? 0,
                            'price' => $package['Price'] ?? $product->price,
                            'values' => json_encode($package),
                            'license_number' => $package['LicenseNumber'] ?? $package['license_number'] ?? null,
                            'sku' => $productData['sku'] ?? '',
                        ]);
                    }
                }

                // Handle image upload if needed
                if (isset($productData['image']) && $productData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $productData['image']->store('wholesale_products', 'public');
                    $product->update(['image' => $imagePath]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Products and inventories saved successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saving products and inventories: ' . $e->getMessage());
            return response()->json(['message' => 'Error saving products and inventories: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $wholesales = WholesaleInventory::all();
    
        // build a flat list of all brand names
        $brands = [];
        foreach ($wholesales as $w) {
            if ($w->brandNames) {
                foreach (explode(',', $w->brandNames) as $b) {
                    $brands[] = trim($b);
                }
            }
        }
        $brands = array_unique($brands);
    
        return view('backend.wholesale.wholesaleInventories.index', compact('wholesales','brands'));
    }

    public function create()
    {
        return view('backend.wholesale.wholesaleInventories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'brandNames'   => 'nullable|string|max:255',
            'products'     => 'required|array|min:1',
            // Validate each product entry:
            'products.*.pId'                              => 'required',
            'products.*.pName'                            => 'required|string',
            'products.*.price'                            => 'required|numeric',
            'products.*.cost'                             => 'nullable|numeric',
            'products.*.sku'                              => 'nullable|string',
            'products.*.weight'                           => 'nullable|numeric',
            'products.*.UnitCbdPercent'                   => 'nullable|numeric',
            'products.*.UnitThcPercent'                   => 'nullable|numeric',
            'products.*.UnitCbdContent'                   => 'nullable|numeric',
            'products.*.UnitThcContent'                   => 'nullable|numeric',
            'products.*.Label'                            => 'nullable|string',
            'products.*.SourcePackageLabels'              => 'nullable|string',
            'products.*.UnitThcContentUnitOfMeasure'      => 'nullable|string',
            'products.*.UnitWeightUnitOfMeasure'          => 'nullable|string',
            'products.*.ItemFromFacilityLicenseNumber'    => 'nullable|string',
            'products.*.ItemFromFacilityName'             => 'nullable|string',
            'products.*.PackagedDate'                     => 'nullable|date',
            'products.*.ExpirationDate'                   => 'nullable|date',
            'products.*.SellByDate'                       => 'nullable|date',
            'products.*.UseByDate'                        => 'nullable|date',
            'products.*.InitialLabTestingState'           => 'nullable|string',
            'products.*.LabTestingStateDate'              => 'nullable|date',
        ]);

        $data['organization_id'] = Auth::user()->organization_id;

        WholesaleInventory::create($data);

        return redirect()
            ->route('wholesaleInventories.index')
            ->with('success', 'Wholesale entry created.');
    }

    public function show(WholesaleInventory $wholesaleInventory)
    {
        return view('backend.wholesale.wholesaleInventories.show', compact('wholesaleInventory'));
    }

    public function edit(WholesaleInventory $wholesaleInventory)
    {
        return view('backend.wholesale.wholesaleInventories.edit', compact('wholesaleInventory'));
    }

    public function update(Request $request, WholesaleInventory $wholesaleInventory)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'brandNames'   => 'nullable|string|max:255',
            'products'     => 'required|array|min:1',
            // same product‐field rules as store()…
        ]);

        $wholesaleInventory->update($data);

        return redirect()
            ->route('wholesaleInventories.index')
            ->with('success', 'Wholesale entry updated.');
    }

    public function destroy(WholesaleInventory $wholesaleInventory)
    {
        $wholesaleInventory->delete();

        return redirect()
            ->route('wholesaleInventories.index')
            ->with('success', 'Wholesale entry deleted.');
    }

    public function importProducts(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric',
            'products.*.category_id' => 'required|exists:categories,id',
            // Add other necessary validations
        ]);

        $importedProducts = [];
        $user = Auth::user();

        foreach ($request->products as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'prices' => $productData['price'],
                'category_id' => $productData['category_id'],
                'organization_id' => $user->organization_id,
                // Add other fields as necessary
            ]);

            // Create a WholesaleInventory entry for this product
            WholesaleInventory::create([
                'name' => $product->name,
                'products' => json_encode([$product->toArray()]),
                'organization_id' => $user->organization_id,
                // Add other necessary fields
            ]);

            $importedProducts[] = $product;
        }

        return response()->json([
            'message' => 'Products imported successfully',
            'products' => $importedProducts
        ], 201);
    }

    public function adminDashboard()
    {
        $user = Auth::user();
        $organization = $user->organization;

      $topProducts = WholesaleProduct::select('wholesale_products.*')
    ->join('wholesale_order_items', 'wholesale_products.id', '=', 'wholesale_order_items.wholesale_product_id')
    ->join('wholesale_orders', 'wholesale_order_items.wholesale_order_id', '=', 'wholesale_orders.id')
    ->where('wholesale_orders.organization_id', $organization->id)
    ->where('wholesale_orders.status', 'completed')
    ->groupBy('wholesale_products.id')
    ->orderByRaw('SUM(wholesale_order_items.quantity) DESC')
    ->take(5)
    ->get();
$salesByCategory = Category::withCount([
    'wholesaleProducts as sales_count' => function ($query) use ($organization) {
        $query->whereHas('wholesaleOrderItems.wholesaleOrder', function ($orderQuery) use ($organization) {
            $orderQuery->where('organization_id', $organization->id)
                       ->where('status', 'completed');
        });
    }
])
->withSum(['wholesaleProducts' => function ($query) use ($organization) {
    $query->join('wholesale_order_items', 'wholesale_products.id', '=', 'wholesale_order_items.wholesale_product_id')
          ->join('wholesale_orders', 'wholesale_order_items.wholesale_order_id', '=', 'wholesale_orders.id')
          ->where('wholesale_orders.organization_id', $organization->id)
          ->where('wholesale_orders.status', 'completed');
}], 'wholesale_order_items.quantity')
->withSum(['wholesaleProducts' => function ($query) use ($organization) {
    $query->join('wholesale_order_items', 'wholesale_products.id', '=', 'wholesale_order_items.wholesale_product_id')
          ->join('wholesale_orders', 'wholesale_order_items.wholesale_order_id', '=', 'wholesale_orders.id')
          ->where('wholesale_orders.organization_id', $organization->id)
          ->where('wholesale_orders.status', 'completed');
}], 'wholesale_order_items.price')
->get();
        $totalSales = WholesaleOrder::where('organization_id', $organization->id)
                                ->where('status', 'completed')
                                ->sum('total_amount');
        $settings = WholesaleSetting::first() ?? new WholesaleSetting();
         $completedOrders = WholesaleOrder::where('organization_id', $organization->id)
        ->where('status', 'completed')
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

    $totalCompletedSales = WholesaleOrder::where('organization_id', $organization->id)
        ->where('status', 'completed')
        ->sum('total_amount');

    $totalOrders = WholesaleOrder::where('organization_id', $organization->id)->count();


return view('wholesale.admin_dashboard', compact(
        'user',
        'organization',
        'settings',
        'topProducts',
        'salesByCategory',
        'completedOrders',
        'totalCompletedSales',
        'totalOrders'
    ));    }

    public function updateUserInfo(Request $request)
    {
        $user = Auth::user();
        $user->update($request->only(['name', 'email', 'phone']));
        return redirect()->back()->with('success', 'User information updated successfully.');
    }

    public function updateOrganizationInfo(Request $request)
    {
        $organization = Auth::user()->organization;
        $organization->update($request->only(['name', 'address', 'phone', 'email']));
        return redirect()->back()->with('success', 'Organization information updated successfully.');
    }

    public function updateSettings(Request $request)
    {
        $settings = WholesaleSetting::first() ?? new WholesaleSetting();
        $settings->fill($request->all());
        $settings->save();
        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updateUser(Request $request)
    {
        // Implement the logic to update the user here
        // For example:
        $user = Auth::user();
        $user->update($request->validated());
        
        return redirect()->route('backend.wholesale.profile')->with('success', 'Profile updated successfully');
    }
    public function showBrandProducts($brandId)
{
    // Fetch the brand
    $brand = Brand::findOrFail($brandId);

    // Fetch products associated with the brand
    $products = Product::where('brand_id', $brandId)->get();

    // Pass the brand and products to the view
    return view('wholesale.customer.brand-products', compact('brand', 'products'));
}
}
