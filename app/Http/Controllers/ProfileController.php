<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\User;
use App\Sale;
use Auth;
use Illuminate\Http\Request;
use Hash;
use Session;
use DB;
class ProfileController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $data = [
            'user'  => Auth::user(),
			'roles' => Role::get()
        ];

        return view('backend.settings.users.profile', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
public function update(Request $request)
{
    $user = Auth::user();

    $data = $request->only(['name','email']);
    $data['apiKey'] = $request->input('api_key');

    if (Auth::user()->role_id == 1) {
        $data['role_id'] = $request->input('role_id');
    }

    $user->update($data);

    return redirect('settings/profile')
        ->with('message-success', 'Profile updated!');
}

	
	
	//// User Password Change request from profile
    public function updatePassword(Request $request) 
    {
       $user = Auth::user();
        $user_id = Auth::user()->id;
        $old_password = $request->input("user_password");
        
        if(strlen($request->input("user_password")) < 6) { 
            $success = array(
            "error" => 1,
            "message" => "Password must be at least 6 character"
            );
                    
            echo  json_encode($success);exit;
        }
            
        if(Hash::check($old_password, $user->password)) {
            
    
            if(!empty($request->input("new_email"))) {
                $user = User::where("email", $request->input("new_email"))->first();
                if(count($user) > 0) { 
                    $success = array(
                    "error" => 1,
                    "message" => ""
                    );
                    
                    echo  json_encode($success);exit;
                }
            
                $data = array(
					'email' => $request->input('new_email')
                );
                User::where("id", $user_id)->update($data);
                $success = array(
                "error" => 0,
                "message" => "Password Changed"
                );
                    
                echo  json_encode($success);exit;
            }
        
            if(!empty($request->input("new_password"))) { 
                $data = array(
                'password' => bcrypt($request->input("new_password"))
                );
                User::where("id", $user_id)->update($data);
                $success = array(
                "error" => 0,
                "message" => "Password Changed"
                );
                    
                echo  json_encode($success);exit;
            }
        
        } else {
            $success = array(
            "error" => 1,
            "message" => "Incorrect Current Password"
            );
                    
            echo  json_encode($success);exit;
        }
        

    }



    public function updateCustomerPassword(Request $request)
    {
        $user_id = Auth::user()->id;
        $user = Auth::user();
        $old_password = $request->input("user_password");

        if (strlen($request->input("user_password")) < 6) {
            $success = array(
                "error" => 1,
                "message" => "Password must at least 6 character"
            );

            echo json_encode($success);
            exit;
        }

        if (Hash::check($old_password, $user->password)) {


            if (!empty($request->input("new_email"))) {
                $user = User::where("email", $request->input("new_email"))->first();
                if (!empty($user) > 0) {
                    $success = array(
                        "error" => 1,
                        "message" => "Email Already exist"
                    );

                    echo json_encode($success);
                    exit;
                }
                $token = $this->generateRandomString(25);
                $data = array(
                    'new_email' => $request->input('new_email'),
                    "token" => $token
                );
                User::where("id", $user_id)->update($data);

                $link = url("verify_token/" . $token . "?new_email=yes");
                $content = array(
                    'subject' => "Verify Your Email - Find Book Beauty",
                    'code' => $token,
                    'name' => Auth::user()->firstname,
                    'message' => "Could you please do us HUGE favour and click on the link below, so we can verify your email: <br><br> $link"
                );
                Mail::to($request->input('new_email'))->send(new ThankyouRegister($content));

                $success = array(
                    "error" => 0,
                    "message" => "Please click on the link to verify your email in your inbox."
                );

                echo json_encode($success);
                exit;
            }

            if (!empty($request->input("new_password"))) {
                if (strlen($request->input("new_password")) < 8) {
                    $success = array(
                        "error" => 1,
                        "message" => "New Password must be at least 8 characters"
                    );

                    echo json_encode($success);
                    exit;
                }

                $data = array(
                    'password' => bcrypt($request->input("new_password"))
                );
                User::where("id", $user_id)->update($data);
                $success = array(
                    "error" => 0,
                    "message" => "Password Changed Successfully"
                );

                echo json_encode($success);
                exit;
            }

        } else {
            $success = array(
                "error" => 1,
                "message" => "Existing Password is incorrect"
            );

            echo json_encode($success);
            exit;
        }
    }

    public function updateProfile(Request $request) { 
        $name = $request->input("name");
        $phone = $request->input("phone");
        $email = $request->input("email");
        $address = $request->input("address");

        $data = array(
            "name" => $name,
            "phone" => $phone,
            "email" => $email,
            "address" => $address,
        );

        User::where("id" , Auth::user()->id)->update($data);
        \Session::flash("success_message", "Profile update successfully");
        return redirect("profile");
    }

    public function myReservations() {
        $data['reservations'] = DB::table("reservations")->where("customer_id" , Auth::user()->id)->orderBy("id" , "DESC")->get(); 
        return view("pages.my_reservation" , $data);
    }

    public function myBookings()
    {
        $data['bookings'] = DB::table("bookings")->where("customer_id", Auth::user()->id)->orderBy("id", "DESC")->get();
        return view("pages.my_bookings", $data);
    }
    public function myOrders()
    {
        $data['sales'] = Sale::select("*", "sales.id as id")->leftJoin("sale_items as s", "s.sale_id", '=', "sales.id")->groupBy("sales.id")->orderBy("sales.id", "DESC")->paginate(25);
        return view("pages.my_orders", $data);
    }


}
