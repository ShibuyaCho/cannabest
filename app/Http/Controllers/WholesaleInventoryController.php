<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WholesaleProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\WholesaleInventory;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\DB;

class WholesaleInventoryController extends Controller
{
    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        return view('backend.wholesale.wholesaleInventories.create', compact('categories', 'brands'));
    }

 public function store(Request $request)
{
    try {
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
            'products.*.weight' => 'required|numeric|min:0',
            'products.*.UnitThcContent' => 'nullable|numeric|min:0|max:100',
            'products.*.UnitCbdContent' => 'nullable|numeric|min:0|max:100',
            'products.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'products.*.remove_background' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        $user = auth()->user();
        $organizationId = $user->organization_id;

        if (!$organizationId) {
            throw new \Exception('User does not have an associated organization.');
        }

        foreach ($request->products as $productData) {
            $wholesaleProduct = WholesaleProduct::create([
                'name' => $productData['name'],
                'description' => $productData['description'] ?? null,
                'category_id' => $productData['category_id'],
                'weight' => $productData['weight'],
                'UnitThcContent' => $productData['UnitThcContent'] ?? null,
                'UnitCbdContent' => $productData['UnitCbdContent'] ?? null,
                'organization_id' => $organizationId, // Add this line
            ]);

            if (isset($productData['image']) && $productData['image'] instanceof \Illuminate\Http\UploadedFile) {
                $image = $productData['image'];
                $filename = time() . '_' . $wholesaleProduct->id . '.' . $image->getClientOriginalExtension();
                
                $img = Image::make($image->getRealPath());
                $img->fit(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $imagePath = 'wholesale_products/' . $filename;
                $img->save(public_path('public/' . $imagePath));

                if (isset($productData['remove_background']) && $productData['remove_background']) {
                    $newImagePath = ImageHelper::removeBackground(public_path('public/' . $imagePath));
                    $imagePath = str_replace(public_path('public/'), '', $newImagePath);
                }

                $wholesaleProduct->update(['image' => $imagePath]);
            }

            WholesaleInventory::create([
                'user_id' => auth()->id(),
                'wholesale_product_id' => $wholesaleProduct->id,
                'name' => $productData['name'],
                'display_name' => $productData['display_name'],
                'category_id' => $productData['category_id'],
                'sku' => $productData['sku'],
                'price' => $productData['price'],
                'quantity' => $productData['quantity'],
                'description' => $productData['description'] ?? null,
                'status' => $productData['status'],
                'organization_id' => $organizationId,

            ]);
        }

        DB::commit();
        return response()->json([
            'message' => 'Products and inventory added successfully'
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json(['error' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to create products and inventory: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create products and inventory. Please try again.'], 500);
    }
}
}