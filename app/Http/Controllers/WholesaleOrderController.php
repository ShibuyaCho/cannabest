<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WholesaleOrder;
use App\Models\WholesaleOrderItem;
use App\Models\WholesaleProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Log; // Ensure Log facade is imported

class WholesaleOrderController extends Controller
{
 public function index()
{
    $orders = WholesaleOrder::with(['organization', 'items', 'createdByUser'])
        ->where('organization_id', auth()->user()->organization_id)
        ->orderBy('created_at', 'desc')
        ->paginate(6); // 6 orders per page (3 rows of 2 orders each)

    return view('wholesale.orders.index', compact('orders'));
}
    public function create()
    {
        $products = WholesaleProduct::where('is_wholesale', true)->get();
        return view('wholesale.orders.create', compact('products'));
    }

   public function store(Request $request)
{
    Log::info('Received order data: ' . json_encode($request->all()));
    
    try {
        $validatedData = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:wholesale_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paymentMethod' => 'required|in:cash,net',
            'specialNotes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        // Get the first product to determine the organization_id
        $firstProductId = $validatedData['items'][0]['id'];
        $product = WholesaleProduct::findOrFail($firstProductId);
        $organizationId = $product->organization_id;

        $order = WholesaleOrder::create([
            'organization_id' => $organizationId,
            'created_by_user_id' => auth()->id(),
            'total_amount' => $validatedData['total'],
            'status' => 'pending',
            'payment_method' => $validatedData['paymentMethod'],
            'notes' => $validatedData['specialNotes'],
            'order_number' => 'WO-' . strtoupper(uniqid()),
        ]);

        foreach ($validatedData['items'] as $item) {
            WholesaleOrderItem::create([
                'wholesale_order_id' => $order->id,
                'wholesale_product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully',
            'redirect' => route('admin.wholesale.orders.show', $order),
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error in WholesaleOrderController@store: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error in WholesaleOrderController@store: ' . $e->getMessage());
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error placing order: ' . $e->getMessage(),
        ], 500);
    }
}

   public function show($id)
{
    $order = WholesaleOrder::findOrFail($id);
    
    if ($order->organization_id !== auth()->user()->organization_id && !auth()->user()->hasRole('org_admin')) {
        abort(403);
    }


        $order->load(['items', 'createdByUser', 'organization']);
 
        return view('wholesale.orders.show', compact('order'));
    }

  
    public function edit($id)
{
    $order = WholesaleOrder::findOrFail($id);
    
    // Check if the user has permission to edit this order
    if ($order->organization_id !== auth()->user()->organization_id && !auth()->user()->hasRole('org_admin')) {
        abort(403);
    }

    // Eager load relationships
    $order->load('items.product', 'createdByUser');

    return view('wholesale.orders.edit', compact('order'));
}

public function update(Request $request, $id)
{
    $order = WholesaleOrder::findOrFail($id);

    // Check if the user has permission to update this order
    if ($order->organization_id !== auth()->user()->organization_id && !auth()->user()->hasRole('org_admin')) {
        abort(403);
    }

    $validatedData = $request->validate([
        'status' => 'required|in:pending,processing,completed,cancelled',
        'payment_method' => 'required|string',
        'notes' => 'nullable|string',
        'items' => 'required|array',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
    ]);

    DB::transaction(function () use ($order, $validatedData) {
        $order->update([
            'status' => $validatedData['status'],
            'payment_method' => $validatedData['payment_method'],
            'notes' => $validatedData['notes'],
        ]);

        foreach ($validatedData['items'] as $index => $itemData) {
            $order->items[$index]->update([
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
            ]);
        }

        $order->total_amount = $order->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        $order->save();
    });

    return redirect()->route('admin.wholesale.orders.show', $order)
        ->with('success', 'Order updated successfully');
}

    public function destroy(WholesaleOrder $order)
    {
        if ($order->organization_id !== auth()->user()->organization_id && !Auth::user()->isAdmin()) {
            abort(403);
        }

        try {
            DB::beginTransaction();
            $order->items()->delete();
            $order->delete();
            DB::commit();

            return redirect()->route('wholesale.orders.index')->with('success', 'Order deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting order: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, WholesaleOrder $order)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $validatedData = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $validatedData['status']]);

        return redirect()->back()->with('success', 'Order status updated successfully');
    }

    public function manage()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $orders = WholesaleOrder::with(['user', 'items.product'])
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);

        return view('wholesale.orders.manage', compact('orders'));
    }

    public function cart()
    {
        $cartItems = session('cart', []);
        $products = Product::whereIn('id', array_keys($cartItems))->get();
        return view('wholesale.cart', compact('cartItems', 'products'));
    }

    public function addToCart(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$validatedData['product_id']])) {
            $cart[$validatedData['product_id']] += $validatedData['quantity'];
        } else {
            $cart[$validatedData['product_id']] = $validatedData['quantity'];
        }

        session()->put('cart', $cart);

        return response()->json(['success' => true, 'message' => 'Product added to cart']);
    }

    public function removeFromCart(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$validatedData['product_id']])) {
            unset($cart[$validatedData['product_id']]);
            session()->put('cart', $cart);
        }

        return response()->json(['success' => true, 'message' => 'Product removed from cart']);
    }

    public function clearCart()
    {
        session()->forget('cart');
        return redirect()->back()->with('success', 'Cart cleared successfully');
    }

    public function placeOrder(Request $request)
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->back()->with('error', 'Your cart is empty');
        }

        $validatedData = $request->validate([
            'payment_method' => 'required|in:cash,net',
            'special_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $orderItems = [];

            foreach ($cart as $productId => $quantity) {
                $product = Product::findOrFail($productId);
                $price = $product->wholesale_price ?? $product->price;
                $subtotal = $price * $quantity;
                $total += $subtotal;

                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }

            $order = WholesaleOrder::create([
                'organization_id' => auth()->user()->organization_id,
                'created_by_user_id' => auth()->id(),
                'total_amount' => $total,
                'status' => 'pending',
                'payment_method' => $validatedData['payment_method'],
                'notes' => $validatedData['special_notes'],
                'order_number' => 'WO-' . strtoupper(uniqid()),
            ]);

            foreach ($orderItems as $item) {
                WholesaleOrderItem::create([
                    'wholesale_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();
            session()->forget('cart');

            return redirect()->route('wholesale.orders.show', $order)->with('success', 'Order placed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error placing order: ' . $e->getMessage());
        }
    }
    
}