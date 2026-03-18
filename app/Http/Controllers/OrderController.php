<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\Order; // Updated namespace for Order model
use Session;
use Validator;
use App\Http\Requests;

class OrderController extends Controller
{

    public function __construct() 
    {
        $this->middleware('auth');
    }

    /**
     * Page Lisitng on admin.
     */
    public function index() 
    {
        $orders = Order::with('user')->paginate(15); // Updated to use Order model
        return view('orders.index', compact('orders')); // Updated view path
    }

    public function orders() 
    {
        $orders = Order::select("*" , "orders.id as id")->where("type", "order")->leftJoin("sale_items as s" , "s.sale_id" , '=', "orders.id" )->orderBy("orders.id", "DESC")->paginate(25); // Updated to use Order model
        return view('backend.orders.allorders', ["orders" => $orders, "title" => "Orders"]);
    }

    public function ChangeStatus(Request $request) 
    {
        $incomplete = $request->input('incomplete');
        $canceled = $request->input('canceled');
        $completed = $request->input('completed');
        $IncompleteIds = array();
        $canceledIds = array();
        $CompletedIds = array();
        if (!empty($incomplete)) {
            foreach ($incomplete as $todo) {
                $IncompleteIds[] = $todo;
            }
        }
        if (!empty($completed)) {
            foreach ($completed as $inp) {
                $CompletedIds[] = $inp;
            }
        }
        if (!empty($canceled)) {
            foreach ($canceled as $com) {
                $canceledIds[] = $com;
            }
        }
        Order::whereIn('id', $IncompleteIds)->update(array("status" => 2)); // Updated to use Order model
        Order::whereIn('id', $CompletedIds)->update(array("status" => 1)); // Updated to use Order model
        Order::whereIn('id', $canceledIds)->update(array("status" => 0)); // Updated to use Order model
    }
	
	
	public function completeSale(Request $request)
    {
        $form = $request->all();
        $items = $request->input('items');
		$amount = 0;
		foreach($items as $item) { 
			$amount += $item['price'] * $item['quantity'];
		}	
		$amount += $request->input('vat') + $request->input('delivery_cost') - $request->input('discount');
		$form['amount'] = $amount;
		
        $rules = Order::$rules; // Updated to use Order model
        $rules['items'] = 'required';

        $validator = Validator::make($form, $rules);

        if ($validator->fails()) {
            return response()->json(
                [
                'errors' => $validator->errors()->all(),
                ], 400
            );
        }
		
		
		if($request->input("payment_with") == "card") { 
			$cc_number = $request->input("cc_number");
			$cc_month = $request->input("cc_month");
			$cc_year = $request->input("cc_year");
			$cc_code = $request->input("cc_code");
			$amount = $request->input("total_cost");
			$amount *= 100;
			\Stripe\Stripe::setApiKey(env("STRIPE_SECRET"));
			try {
                    $token = \Stripe\Token::create(
                        array(
                        "card" => array(
                        "number" => $cc_number,
                        "exp_month" => $cc_month,
                        "exp_year" => $cc_year,
                        "cvc" => $cc_code
                        )
                        )
                    );
                } catch (\Stripe\Error\Card $e) {
                    $token = $e->getJsonBody();
                    $errors = array(
                    "error" => 1,
                    "message" => $token['error']['message']
                    );

                    echo  json_encode($errors);exit;
                }

                // Get the payment token submitted by the form:
                $stripeToken = $token['id'];

                // Create a Customer:
                $customer = \Stripe\Customer::create(
                    array(
                    "email" => Auth::user()->email,
                    "source" => $token,
                    )
                );

                // Charge the Customer instead of the card:
                $charge = \Stripe\Charge::create(
                    array(
                    "amount" => round($amount),
                    "currency" => "USD",
                    "customer" => $customer->id
                    )
                );
		}
		
			unset($form["cc_number"]);
			unset($form["cc_month"]);
			unset($form["cc_year"]);
			unset($form["cc_code"]);
			unset($form["total_cost"]);
            
            // print_r($form); exit;
			$order = Order::createAll($form); // Updated to use Order model

				$errors = array(
                    "error" => 0,
                    "message" => "Thank you for your Order. We will contact you soon."
                    );
				echo  json_encode($errors);exit;
    }

    public function show(Order $order) // Added method from update
    {
        $order->load('user', 'products'); // Added method from update
        return view('orders.show', compact('order')); // Added method from update
    }

    public function store(Request $request) // Added method from update
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'shipping_address' => 'required|string',
            'billing_address' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $order = Order::create($validatedData); // Added method from update

        // Assuming you're sending product IDs and quantities in the request
        $products = $request->input('products', []); // Added method from update
        foreach ($products as $productId => $quantity) { // Added method from update
            $order->products()->attach($productId, ['quantity' => $quantity]); // Added method from update
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.'); // Added method from update
    }

    public function update(Request $request, Order $order) // Added method from update
    {
        $validatedData = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update($validatedData); // Added method from update

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.'); // Added method from update
    }

    public function destroy(Order $order) // Added method from update
    {
        $order->delete(); // Added method from update
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.'); // Added method from update
    }
	
	
}
