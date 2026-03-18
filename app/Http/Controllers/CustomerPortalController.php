<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function index()
    {
        $organizations = Organization::with('branches')->get();
        return view('customer.portal', compact('organizations'));
    }

    public function branchProducts(Organization $organization, Branch $branch)
    {
        $products = $branch->products;
        return view('customer.branch_products', compact('organization', 'branch', 'products'));
    }
}