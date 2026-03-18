<?php

namespace App\Http\Controllers;

use App\Models\Wholesale;
use App\Brand;
use App\Product;
use Illuminate\Http\Request;

class WholesaleFrontendController extends Controller
{
    public function index()
    {
        $wholesalers = WholesaleInventory::with('brands', 'products')->get();
        return view('frontend.wholesale.index', compact('wholesalers'));
    }

    public function showWholesaler($id)
    {
        $wholesaler = WholesaleInventory::with('brands', 'products')->findOrFail($id);
        return view('frontend.wholesale.show', compact('wholesaler'));
    }

    public function showBrand($id)
    {
        $brand = Brand::with('products')->findOrFail($id);
        return view('frontend.wholesale.brand', compact('brand'));
    }

    public function showProduct($id)
    {
        $product = Product::findOrFail($id);
        return view('frontend.wholesale.product', compact('product'));
    }
}