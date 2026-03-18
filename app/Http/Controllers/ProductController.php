<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Inventory;
use App\Product;
use App\Models\WholesaleInventory;
use App\Models\Category;
use App\Setting;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\DB; 

class ProductController extends Controller
{
    /**
     * Display a listing of the inventory records.
     */
public function index(Request $request)
{
    $keyword = $request->get('q', '');
    $category = $request->get('category', '');
    $sortBy = $request->get('sort_by', 'id');
    $sortOrder = $request->get('sort_order', 'desc');
    $perPage = $request->get('per_page', 15);

    $query = Product::query();

    // Apply search filter
    if ($keyword) {
        $query->where(function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%")
              ->orWhere('sku', 'like', "%{$keyword}%");
        });
    }

    // Apply category filter
    if ($category) {
        $query->where('category_id', $category);
    }

    // Apply sorting
    $query->orderBy($sortBy, $sortOrder);

    // Fetch products with pagination
    $products = $query->with('category')->paginate($perPage);

    // Append query parameters to pagination links
    $products->appends([
        'q' => $keyword,
        'category' => $category,
        'sort_by' => $sortBy,
        'sort_order' => $sortOrder,
        'per_page' => $perPage
    ]);

    // Fetch all categories for the filter dropdown
    $categories = Category::all();

    return view('backend.products.index', [
        'products' => $products,
        'keyword' => $keyword,
        'selectedCategory' => $category,
        'categories' => $categories,
        'sortBy' => $sortBy,
        'sortOrder' => $sortOrder,
        'perPage' => $perPage
    ]);
}
    
        
     
    public function searchByName(Request $request)
{
    $name = $request->query('name');
    $product = Product::with('inventory')   // assume you have an inventory() relation
                  ->where('name','like', $name)
                  ->orWhere('name','like', "%{$name}%")
                  ->first();

    if (!$product) {
        return response()->json(null, 404);
    }

    return response()->json([
        'id'           => $product->id,
        'name'         => $product->name,
        'original_price' => $product->original_price,
        'original_cost'  => $product->original_cost,
        'image_url'    => asset("uploads/products/{$product->id}.jpg"),
    ]);
}

    /**
     * Show the form for creating a new inventory record.
     */
    public function create()
    {
        // Fetch existing package IDs from the inventory
       $existingPackageIds = Inventory::whereNotNull('Label')->pluck('Label')->toArray();

        // Fetch categories and other necessary data
        $categories = Category::all();

        return view('backend.products.create', compact('categories', 'existingPackageIds'));
    }
    

    /**
     * Store a newly created inventory record in storage.
     */


public function store(Request $request)
{
    // 1) Validate
    $data = $request->validate([
        'name'                  => 'required|string|max:255',
        'sku'                   => 'required|string|unique:products,sku|max:255',
        'Label'                 => 'required|string|unique:products,sku|max:255',
        'weight'                => 'nullable|numeric',
        'THC'                   => 'nullable|numeric',
        'CBD'                   => 'nullable|numeric',
        'original_price'        => 'required|numeric',
        'original_cost'         => 'nullable|numeric',
        'description'           => 'nullable|string',
        'selected_discount_tier'=> 'nullable|string',
        'quantity'              => 'nullable|numeric',
        'inventory_type'        => 'nullable|in:inventories,hold_inventories',
        'category_id'           => 'required|exists:categories,id',
        'file'                  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'metrc_package'         => 'nullable|json',
    ]);


    $quantity = $data['storeQty'] ?? $data['original_PackageQty'] ?? 0;

    DB::transaction(function() use ($data, $quantity, $request) {
        
        if ($request->has('wholesale_product_id')) {
            $wholesaleProduct = WholesaleInventory::findOrFail($request->wholesale_product_id);
            $product->wholesale_product_id = $wholesaleProduct->id;
            $product->save();
        }

        // 3) Create Product
        $product = Product::create([
            'name'                    => $data['name'],
            'sku'                     => $data['sku'],
            'category_id'             => $data['category_id'],
            'original_price'          => $data['original_price'],
            'original_cost'           => $data['original_cost'] ?? 0,
            'description'             => $data['description'] ?? null,
            'selected_discount_tier'  => $data['selected_discount_tier'] ?? null,
            'weight'                  => $data['weight'] ?? null,
            'UnitThcContent'          => $data['THC'],
            'UnitCbdContent'          => $data['CBD'],
        ]);

        // 4) Create Inventory
        $inventory = Inventory::create([
            'product_id'              => $product->id,
            'sku'                     => $data['sku'],
            'weight'                  => $data['weight'] ?? null,
            'inventory_type'          => $data['inventory_type'] ?? 'hold_inventories',
            'name'                    => $data['name'],
            'original_name'           => $data['name'],
            'description'           => $data['description'] ?? null,
            'category_id'             => $data['category_id'],
            'original_price'          => $data['original_price'],
            'original_cost'           => $data['original_cost'] ?? 0,
            'storeQty'                => $data['quantity'] ?? 0,
            'selected_discount_tier'  => $data['selected_discount_tier'] ?? null,
            'Label'                   => $data['Label'] ?? null,
            'metrc_package'           => $data['metrc_package'],
            'THC'                     => $data['THC'],
            'CBD'                     => $data['CBD'],
        ]);

        // 5) Handle Image Upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $inventory->id . '.jpg';

            // inventories folder
            $invPath = public_path('public/uploads/inventories');
            if (!is_dir($invPath)) mkdir($invPath, 0777, true);
            $file->move($invPath, $filename);

            // products folder (copy or reupload)
            $prodPath = public_path('public/uploads/products');
            if (!is_dir($prodPath)) mkdir($prodPath, 0777, true);
            copy("$invPath/$filename", "$prodPath/{$product->id}.jpg");
        }

        // 6) If hold inventory, seed a hold record
        if ($data['inventory_type'] === 'hold_inventories' && $quantity > 0) {
            \App\HoldInventory::create([
                'inventory_id' => $inventory->id,
                'quantity'     => $quantity,
                'hold_reason'  => 'Initial hold',
            ]);
        }
    });

    return redirect()
        ->route('update_inventory')
        ->with('message-success', 'Product and inventory created successfully.');
}
    
    
    /**
     * Display the specified inventory record.
     */
    public function show($id)
    {
        $inventory = Inventory::findOrFail($id);
        $categories = Category::all();
        return view('backend.products.show', compact('inventory', 'categories'));
    }
    
    /**
     * Show the form for editing the specified inventory record.
     */
    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);
        $categories = Category::all();
        
        $discountTiersSetting = Setting::where('key', 'discount_tiers')->first();
        if ($discountTiersSetting) {
            $value = $discountTiersSetting->value;
            if (is_string($value)) {
                $discountTiers = json_decode($value, true);
            } elseif (is_array($value)) {
                $discountTiers = $value;
            } else {
                $discountTiers = [];
            }
        } else {
            $discountTiers = [];
        }
        
        return view('backend.products.edit', compact('inventory', 'categories', 'discountTiers'));
    }
    
    /**
     * Update the specified inventory record in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate inputs as needed.
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|string|max:255|unique:inventories,sku,' . $id,
        ]);
    
        // Get pricing and cost info.
        $prices = $request->input("original_price");
    
        // Use storeQty if provided; otherwise default to original_PackageQty.
        $storeQty = $request->input('storeQty') ?: $request->input('original_PackageQty');
    
        // Update the inventory record.
        $inventory = Inventory::findOrFail($id);
        $inventory->update([
            // Basic Info
            'name'                => $request->input('name'),
            'original_name'       => $request->input('name'),
            'original_desc'       => $request->input('original_desc'),
            'category_id'         => $request->input('category_id'),
            'sku'                 => $request->input('sku'),

            
            // Pricing and Cost
            'original_price'      => $prices,
            'original_cost'       => $request->input('original_cost'),
            
            // Quantities and Inventory
            'Quantity'            => $storeQty,
            'storeQty'            => $storeQty,
            'inventory_type'      => $request->input('inventory_type'),
            
            // Discount Tiers
            'selected_discount_tier' => $request->input('selected_discount_tier'),
            'tier_base_price'        => $request->input('tier_base_price'),
            
            // Extra Package Fields
            'extraName'           => $request->input('extraName'),
            'description'         => $request->input('original_desc'),
            'category'            => $request->input('category'),
            'original_productLabel' => $request->input('original_productLabel'),
            'UnitCbdPercent'      => $request->input('UnitCbdPercent'),
            'UnitThcPercent'      => $request->input('UnitThcPercent'),
            'UnitCbdContent'      => $request->input('UnitCbdContent'),
            'UnitThcContent'      => $request->input('UnitThcContent'),
            // API Package Fields
            'Api_Id'              => $request->input('Api_Id'),
            'Label'               => $request->input('Label'),
            'PackageType'         => $request->input('PackageType'),
            'SourceHarvestNames'  => $request->input('SourceHarvestNames'),
            'SourcePackageLabels' => $request->input('SourcePackageLabels'),
            'UnitOfMeasureName'   => $request->input('UnitOfMeasureName') ?: $request->input('unitofmeasurename'),
            'UnitOfMeasureAbbreviation' => $request->input('UnitOfMeasureAbbreviation'),
            'ItemFromFacilityLicenseNumber' => $request->input('ItemFromFacilityLicenseNumber'),
            'ItemFromFacilityName'=> $request->input('ItemFromFacilityName'),
            'PackagedDate'        => $request->input('PackagedDate'),
            'ExpirationDate'      => $request->input('ExpirationDate'),
            'SellByDate'          => $request->input('SellByDate'),
            'UseByDate'           => $request->input('UseByDate'),
            'InitialLabTestingState' => $request->input('InitialLabTestingState'),
            'LabTestingState'     => $request->input('LabTestingState'),
            'LabTestingStateDate' => $request->input('LabTestingStateDate'),
            'LabTestingPerformedDate' => $request->input('LabTestingPerformedDate'),
            'LabTestResultExpirationDateTime' => $request->input('LabTestResultExpirationDateTime'),
            'LabTestingRecordedDate' => $request->input('LabTestingRecordedDate'),
            'LabTestStageId'      => $request->input('LabTestStageId'),
            'LabTestStage'        => $request->input('LabTestStage'),
            'ProductionBatchNumber' => $request->input('ProductionBatchNumber'),
            'SourceProductionBatchNumbers' => $request->input('SourceProductionBatchNumbers'),
            'ReceivedDateTime'    => $request->input('ReceivedDateTime'),
            'ReceivedFromFacilityLicenseNumber' => $request->input('ReceivedFromFacilityLicenseNumber'),
            'ReceivedFromFacilityName' => $request->input('ReceivedFromFacilityName'),
            'LastModified'        => $request->input('LastModified'),
            
            // JSON Fields
            'Item'                => $request->has('Item') ? $request->input('Item') : null,
            'ProductLabel'        => $request->has('ProductLabel') ? $request->input('ProductLabel') : null,
        ]);
    
        return redirect('inventories')
            ->with('message-success', 'Inventory updated successfully!');
    }
    
    /**
     * Remove the specified inventory record from storage.
     */
  public function destroy(Product $product)
{
    try {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully'], 200);
    } catch (\Exception $e) {
        \Log::error('Product deletion failed: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to delete product'], 500);
    }
}
    
    /**
     * Upload photo to public/uploads/products.
     * This method is called via AJAX when using the Cropper.
     */
    public function uploadPhoto(Request $request)
    {
        if ($request->hasFile('croppedImage')) {
            $file = $request->file('croppedImage');
            $fileName = "temp.jpg";
            $destinationPath = public_path('uploads/products');
            
            $file->move($destinationPath, $fileName);
            $filePath = $destinationPath . DIRECTORY_SEPARATOR . $fileName;
            
            $manager = new ImageManager(['driver' => 'gd']);
            $img = $manager->make($filePath);
            
            if ($img->exif('Orientation')) {
                $img->orientate();
            }
            
            $thumbPath = public_path('uploads/products/thumb');
            if (!file_exists($thumbPath)) {
                mkdir($thumbPath, 0755, true);
            }
            $img->fit(250)->save($thumbPath . DIRECTORY_SEPARATOR . $fileName);
            
            return response()->json(['url' => url('uploads/products/' . $fileName)]);
        }
        return response()->json(['error' => 'No file provided'], 400);
    }
    
    /**
     * Update photo crop for category images.
     */
    public function updatePhotoCrop(Request $request)
    {
        try {
            $croppedValue = $request->input("cropped_value");
            $imageEdit = $request->input("image_edit");
            $cpValues = explode(",", $croppedValue);
    
            if (count($cpValues) < 5) {
                throw new \Exception("Invalid cropped value: " . $croppedValue);
            }
    
            $file = $request->file('file');
            $fileName = $imageEdit ? $imageEdit . ".jpg" : "temp.jpg";
            $storePath = public_path("uploads/category");
    
            if ($request->hasFile('file')) {
                $file->move($storePath, $fileName);
                $manager = new ImageManager(['driver' => 'gd']);
                $img = $manager->make($storePath . DIRECTORY_SEPARATOR . $fileName);
                
                if ($img->exif('Orientation')) {
                    $img->orientate();
                }
                
                $thumbPath = public_path("uploads/category/thumb");
                if (!file_exists($thumbPath)) {
                    mkdir($thumbPath, 0755, true);
                }
    
                $img->rotate((float)$cpValues[4] * -1)
                    ->crop((int)$cpValues[0], (int)$cpValues[1], (int)$cpValues[2], (int)$cpValues[3])
                    ->fit(265, 205)
                    ->save($thumbPath . DIRECTORY_SEPARATOR . $fileName);
    
                return response()->json(['url' => url("uploads/category/thumb/" . $fileName)]);
            }
    
            if ($imageEdit != "") {
                $path = public_path("uploads/category" . DIRECTORY_SEPARATOR . $fileName);
                $manager = new ImageManager(['driver' => 'gd']);
                $img = $manager->make($path);
                $img->rotate((float)$cpValues[4] * -1)
                    ->crop((int)$cpValues[0], (int)$cpValues[1], (int)$cpValues[2], (int)$cpValues[3])
                    ->fit(265, 205)
                    ->save($path);
    
                return response()->json(['url' => url("uploads/category/" . $fileName)]);
            }
    
            throw new \Exception("No file provided or image_edit is empty.");
        } catch (\Exception $e) {
            \Log::error("updatePhotoCrop error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Toggle archive status of an inventory record.
     */
    public function addToArchive(Request $request)
    {
        $id = $request->input("inventory_id");
        $inventory = Inventory::find($id);
        $value = $inventory->is_delete == 1 ? 0 : 1;
        $inventory->update(['is_delete' => $value]);
    }
}
