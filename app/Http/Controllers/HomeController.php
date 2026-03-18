<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Category;
use App\Product;
use App\Page;
use App\Sale;
use DB,
    Auth,
    Artisan,
    Mail;
use App\Mail\Test;
use App\Mail\Contact;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() 
    {
        // $this->middleware('auth');
    }

    public function index() 
    {
        $frontend = setting_by_key('frontend');
        // Check if the request path is not 'thcfg' before redirecting
        if ($frontend == "yes" && request()->path() !== 'thcfg') { 
           return redirect("dashboard");
        }
        $categories = Category::get();
        return view('home', compact('categories'));
    }
    
    public function about() 
    {
        $page = Page::find(3);
        return view('pages.about', ['page' => $page]);
    }

    public function faqs() 
    {
        $page = Page::find(2);
        return view('pages.dynamic', ['page' => $page]);
    }

    public function termsCondition() 
    {
        $page = Page::find(1);
        return view('pages.dynamic', ['page' => $page]);
    }

    public function contact() 
    {
        return view('pages.contact');
    }

    public function contactSave(Request $request) 
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $message = $request->input('message');
        $content = array(
            "name" => $name,
            "email" => $email,
            "message" => $message
        );
        Mail::to("arfan67@gmail.com")->send(new Contact($content));
        Mail::to($email)->send(new Contact($content));
        echo "success";
    }

    public function ourMenu()
    {
        $data = [
            'categories' => Category::paginate(15),
            'products'   => Product::all(), 
        ];
        return view('backend.sales.customercreate', $data);
    }
    

    public function testMail() 
    {
        $content = array(
            "name" => "Arfan"
        );

        //return view("emails.booking");
        Mail::to("arfan67@gmail.com")->send(new Test($content));

        echo 'Mail Sent!';
    }
	
	public function import() { 
		$sales = Sale::get();
		foreach($sales as $sale) { 
			$items = DB::table("sale_items")->where("sale_id" , $sale->id)->get();
			$amount = 0;
			foreach($items as $item) { 
				$amount = $item->quantity * $item->price;
			}
		 Sale::where("id" , $sale->id)->update(array("amount" => $amount));
		}
		echo "Done";
		//DB::unprepared(file_get_contents('db/pos.sql'));
	}

    public function clearCache() { 
		try { 
            Artisan::call("config:cache");
            Artisan::call("view:clear");
            Artisan::call("route:clear");
            Artisan::call("config:clear");
            Artisan::call("cache:clear");
        } catch(\Exception $e) { 

        }
		echo "Done";
		//DB::unprepared(file_get_contents('db/pos.sql'));
	}

}
