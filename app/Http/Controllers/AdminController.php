<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Organization;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function dashboard()
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalOrganizations = Organization::count();

        return view('admin.dashboard', compact('totalUsers', 'totalOrders', 'totalProducts', 'totalOrganizations'));
    }

    public function users()
    {
        $users = User::paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function orders()
    {
        $orders = Order::with('user')->paginate(15);
        return view('admin.orders.index', compact('orders'));
    }

    public function products()
    {
        $products = Product::paginate(15);
        return view('admin.products.index', compact('products'));
    }

    public function organizations()
    {
        $organizations = Organization::paginate(15);
        return view('admin.organizations.index', compact('organizations'));
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function updateSettings(Request $request)
    {
        // Validate and update settings
        // This is just a placeholder, you'll need to implement the actual logic
        return redirect()->back()->with('success', 'Settings updated successfully');
    }
}