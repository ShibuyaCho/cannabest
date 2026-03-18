<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function getDiscountedPrice(Request $request)
    {
        $basePrice = floatval($request->input('basePrice'));
        $quantity = floatval($request->input('quantity'));
        
        // Use your helper function. Make sure the helper file is loaded via composer or included.
        $discountedPrice = getDiscountedPrice($basePrice, $quantity);
        
        return response()->json(['discountedPrice' => $discountedPrice]);
    }
}