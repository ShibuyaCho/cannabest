<?php

namespace App\Http\Controllers;

use App\Models\WholesaleProduct;
use App\Models\Wholesale;
use App\Models\WholesaleInventory;
use App\Models\WholesaleSetting;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ImageHelper;

class WholesaleProductController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        $licenseNumber = WholesaleSetting::where('key', 'license_number')->value('value');
        $apiKey = $this->getApiKey();
        
        return view('backend.wholesale.products.create', compact('categories', 'licenseNumber', 'apiKey', 'brands'));
    }

    
    public function destroy($id)
    {
        $product = WholesaleProduct::findOrFail($id);

        // Check if the authenticated user owns this product
        if ($product->wholesaleInventory->user_id !== auth()->id()) {
            return redirect()->route('wholesale.products.index')->with('error', 'You are not authorized to delete this product.');
        }

        // Delete the product image if it exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Delete the product
        $product->delete();

        return redirect()->route('wholesale.products.index')->with('success', 'Product deleted successfully.');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = WholesaleProduct::query();

        // Join with wholesale_inventories
        $query->leftJoin('wholesale_inventories', function ($join) use ($user) {
            $join->on('wholesale_products.id', '=', 'wholesale_inventories.wholesale_product_id')
                 ->where('wholesale_inventories.user_id', $user->id);
        });

        // Select fields and calculate total quantity
        $query->select('wholesale_products.*')
              ->selectRaw('COALESCE(SUM(wholesale_inventories.quantity), 0) as total_quantity')
              ->groupBy('wholesale_products.id');

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('wholesale_products.name', 'like', "%{$searchTerm}%")
                  ->orWhere('wholesale_products.description', 'like', "%{$searchTerm}%")
                  ->orWhere('wholesale_inventories.package_id', 'like', "%{$searchTerm}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('wholesale_products.category_id', $request->input('category'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('wholesale_inventories.status', $request->input('status'));
        }

        // Sorting
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $validSortFields = ['name', 'price', 'total_quantity', 'created_at'];
        
        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'name';
        }

        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 12);
        $products = $query->paginate($perPage)->appends($request->query());

        // Get categories for filter dropdown
        $categories = Category::whereIn('id', $products->pluck('category_id')->unique())->get();


        return view('backend.wholesale.products.index', compact('products', 'categories'));
    }

    
    public function getLicenseNumber()
    {
        $user = auth()->user();
        if (!$user) {
            Log::warning('No authenticated user found when trying to get license number');
            return null;
        }
    
        $organization = $user->organization;
        if (!$organization) {
            Log::warning('User (ID: ' . $user->id . ') does not have an associated organization');
            return null;
        }
    
        $licenseNumber = $organization->license_number;
        if (!$licenseNumber) {
            Log::warning('Organization (ID: ' . $organization->id . ') does not have a license number');
        }
    
        return $licenseNumber;
    }

public function getWholesaleProducts(Request $request)
{
    $filter = $request->input('filter', 'organization');
    $query = WholesaleInventory::with(['wholesaleProduct', 'wholesaleProduct.organization', 'wholesaleProduct.category']);

    if ($filter === 'organization') {
        $products = $query->get()->groupBy('wholesaleProduct.organization.name');
    } elseif ($filter === 'category') {
        $products = $query->get()->groupBy('wholesaleProduct.category.name');
    } else {
        return response()->json(['error' => 'Invalid filter'], 400);
    }

    $formattedProducts = [];
    foreach ($products as $groupName => $groupProducts) {
        $formattedProducts[] = [
            'group' => $groupName ?? 'Uncategorized',
            'products' => $groupProducts->map(function ($inventory) {
                return [
                    'id' => $inventory->id,
                    'name' => $inventory->name,
                    'extraName' => $inventory->wholesaleProduct->extraName ?? null,
                    'image' => $inventory->wholesaleProduct->image ?? null,
                    'category_id' => $inventory->category_id,
                    'price' => $inventory->price,
                    'sku' => $inventory->sku,
                    'quantity' => $inventory->quantity,
                    'description' => $inventory->description,
                ];
            })
        ];
    }

    return response()->json($formattedProducts);
}

    public function getPackageInfo(Request $request)
    {
        $packageId = $request->input('packageId');
        $wholesale = WholesaleInventory::where('user_id', auth()->id())->first();
        $licenseNumber = $wholesale->license_number ?? config('app.metrc_license_number');
        $authHeader = config('app.metrc_auth_header');

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
        ])->get("https://sandbox-api-or.metrc.com/packages/v2/{$packageId}?licenseNumber={$licenseNumber}");

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Package not found'], 404);
        }
    }

    public function getAllPackages()
    {
        Log::info('getAllPackages function called');

        $wholesale = WholesaleInventory::where('user_id', auth()->id())->first();
        $licenseNumber = $wholesale->license_number ?? config('app.metrc_license_number');
        $authHeader = config('app.metrc_auth_header');

        Log::info('License Number: ' . $licenseNumber);

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
        ])->get("https://sandbox-api-or.metrc.com/packages/v2/active?licenseNumber={$licenseNumber}");

        Log::info('API Response Status: ' . $response->status());

        if ($response->successful()) {
            Log::info('API call successful');
            return response()->json($response->json());
        } else {
            Log::error('API call failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return response()->json(['error' => 'Could not retrieve packages'], $response->status());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.name' => 'required|string',
            'products.*.display_name' => 'required|string',
            'products.*.category_id' => 'required|exists:categories,id',
            'products.*.sku' => 'required|string|unique:wholesale_inventories,sku',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|numeric|min:0',
            'products.*.description' => 'nullable|string',
            'products.*.status' => 'required|in:active,inactive,out_of_stock',
            'products.*.packages' => 'required|array',
            'products.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'products.*.remove_background' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $savedPackageIds = [];

            foreach ($request->products as $productData) {
                // First, create or find the WholesaleProduct
                $wholesaleProduct = WholesaleProduct::firstOrCreate(
                    ['name' => $productData['name']],
                    [
                        'description' => $productData['description'] ?? null,
                        'category_id' => $productData['category_id'],
                    ]
                );

                // Handle image upload if present
                $imagePath = null;
                if (isset($productData['image']) && $productData['image'] instanceof UploadedFile) {
                    $image = $productData['image'];
                    $filename = time() . '.' . $image->getClientOriginalExtension();
                    
                    // Create an instance of the image
                    $img = Image::make($image->getRealPath());
                    
                    // Resize the image to 300x300
                    $img->fit(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    // Save the resized image
                    $imagePath = 'wholesale_products/' . $filename;
                    $img->save(public_path('public/' . $imagePath));

                    // Remove background if requested
                    if (isset($productData['remove_background']) && $productData['remove_background']) {
                        $newImagePath = ImageHelper::removeBackground(public_path('public/' . $imagePath));
                        $imagePath = str_replace(public_path('public/'), '', $newImagePath);
                    }
                }

                // Update the WholesaleProduct with the image path
                $wholesaleProduct->update(['image' => $imagePath]);

                // Now create the WholesaleInventory
                $wholesaleInventory = new WholesaleInventory();
                $wholesaleInventory->user_id = auth()->id();
                $wholesaleInventory->wholesale_product_id = $wholesaleProduct->id;
                $wholesaleInventory->name = $productData['name'];
                $wholesaleInventory->display_name = $productData['display_name'];
                $wholesaleInventory->category_id = $productData['category_id'];
                $wholesaleInventory->sku = $productData['sku'];
                $wholesaleInventory->price = $productData['price'];
                $wholesaleInventory->quantity = $productData['quantity'];
                $wholesaleInventory->description = $productData['description'] ?? null;
                $wholesaleInventory->status = $productData['status'];
                $wholesaleInventory->products = $productData['packages'];

                $wholesaleInventory->save();

                // Add package IDs to the saved list
                foreach ($productData['packages'] as $package) {
                    $savedPackageIds[] = $package['package_id'];
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Products created successfully',
                'savedPackageIds' => $savedPackageIds
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create products: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $product = WholesaleProduct::with('wholesaleInventories')->findOrFail($id);
        $categories = Category::all();
        $brands = Brand::all();

        // Check if the product has any associated inventory
        if ($product->wholesaleInventories->isEmpty()) {
            return redirect()->route('wholesale.products.index')->with('error', 'This product has no associated inventory.');
        }

        // Check if the authenticated user owns this product's inventory
        $userInventory = $product->wholesaleInventories->where('user_id', auth()->id())->first();
        if (!$userInventory) {
            return redirect()->route('wholesale.products.index')->with('error', 'You are not authorized to edit this product.');
        }

        return view('backend.wholesale.products.edit', compact('product', 'categories', 'userInventory', 'brands'));
    }

    public function update(Request $request, $id)
    {
        $product = WholesaleProduct::with('wholesaleInventories')->findOrFail($id);

        if ($product->wholesaleInventories->isEmpty()) {
            return redirect()->route('wholesale.products.index')->with('error', 'This product has no associated inventory.');
        }

        $userInventory = $product->wholesaleInventories->where('user_id', auth()->id())->first();
        if (!$userInventory) {
            return redirect()->route('wholesale.products.index')->with('error', 'You are not authorized to edit this product.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = $product->image; // Keep the existing image path by default

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = 'wholesale_products/' . $filename;
            
                // Create an instance of the image
                $img = Image::make($image->getRealPath());

                // Resize the image to 300x300
                $img->fit(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });

                // Save the resized image
                $img->save(public_path('public/' . $imagePath));

                // Update the product with the new image path
                $product->update(['image' => $imagePath]);
            }

            // Ensure $organizationId is defined
            $organizationId = auth()->user()->organization_id;

            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'image' => $imagePath,
                'brand_id' => $validated['brand_id'],
                'organization_id' => $organizationId,
            ]);

            $userInventory->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
            ]);

            DB::commit();
            return redirect()->route('wholesale.products.index')->with('success', 'Product and inventory updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating product and inventory: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating product and inventory. Please try again.');
        }
    }

    private function getApiKey()
    {
        $user = Auth::user();
        return $user ? $user->apiKey : null;
    }
}