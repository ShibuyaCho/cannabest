<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Requests;
use DB;
class NewsletterController extends Controller
{

	public function __construct()
	{
		$this->middleware('auth')->except("store");
	}

	public function index() {
		$data["newsletters"] = DB::table("newsletters")->orderBy("id" , "DESC")->paginate(50);
		return view("backend.newsletters.home" , $data);
	}

	public function delete(Request $request)
	{
		$id = $request->input("id");
		DB::table("newsletters")->where("id", $id)->delete();
	}
    public function store(Request $request) { 
		$email = $request->input('email');
		$email_already = DB::table("newsletters")->where("email" , $email)->count();
		if($email_already > 0) { 
			echo "already"; exit;
		}
		DB::table("newsletters")->insert(array("email" => $email));
		echo "Thank you for Subscribing With Us";
	}
}
