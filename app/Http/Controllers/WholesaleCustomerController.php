<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Brand;
use App\Models\Product;
use App\Models\WholesaleProduct;
use App\Models\Customer;
use App\Models\CustomizableContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleCustomerController extends Controller
{
    public function index()
    {
        $organizations = Organization::with('brands')->get(); // Assuming Organization model has a 'brands' relationship
        return view('wholesale.customer.dashbord', compact('organizations'));
    }


public function organizationBrands($organizationId)
{
    $organization = Organization::findOrFail($organizationId);
    
    // Fetch customizable content for this organization
    $customContent = CustomizableContent::where('organization_id', $organizationId)
        ->where('page_name', 'organization-brands')
        ->first();

    // Parse the JSON string to an array
   $customStyles = $customContent ? ($customContent->content ?: []) : [];
   
    // Fetch all products for the organization, including brand information
    $products = WholesaleProduct::where('organization_id', $organizationId)
        ->with(['brand', 'wholesaleInventories' => function ($query) {
            $query->where('quantity', '>', 0);
        }])
        ->whereHas('wholesaleInventories', function ($query) {
            $query->where('quantity', '>', 0);
        })
        ->get();

    // Group products by brand
    $productsByBrand = $products->groupBy(function ($product) {
        return $product->brand ? $product->brand->id : 'unbranded';
    });

    // Extract featured products
    $featuredProducts = $products->where('is_featured', true)->take(8);

    // Extract unbranded products
    $unbrandedProducts = $productsByBrand->get('unbranded', collect());

    // Get brands with products
    $brands = Brand::whereIn('id', $productsByBrand->keys()->filter())->get();

    return view('wholesale.customer.organization-brands', compact(
        'organization',
        'brands',
        'productsByBrand',
        'featuredProducts',
        'unbrandedProducts',
        'customStyles'
    ));
}
}