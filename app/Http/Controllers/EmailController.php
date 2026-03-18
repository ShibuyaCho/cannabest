<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ReportsEmail;
use App\Mail\NewsletterMail;
use DB;
use Mail;
use App\User;
use Response;
use PDF;
use App\EmailTemplate as Template;
class EmailController extends Controller
{
	public function index() {
		
		    $query = DB::table("sales");
		    $query->whereDay('sales.created_at', '=', date('d'));
			
			$sales = $query->select("*", DB::raw('SUM(amount) as total_amount'))->groupBy("cashier_id")->get();
			foreach($sales as $sale) {  
				$sale->user = User::find($sale->cashier_id);
			}
			
			 $data['sales'] = $sales;
			
			if(!empty($_GET['pdf'])) { 
				$pdf = $_GET['pdf'];
			}
			if($pdf == "yes") { 
				$data['title'] = "Staff Sold Report";
				//return view("backend.reports.staff_sold_pdf" , $data);
				$pdf = PDF::loadView('backend.reports.staff_sold_pdf' , $data);
				return $pdf->download('staff_sold.pdf');
			}
			
	
			
			$headers = array(
				'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
				'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
				'Content-Disposition' => 'attachment; filename=abc.csv',
				'Expires' => '0',
				'Pragma' => 'public',
			);
			$name = "staff_sold";
			$filename = "staff_sold.csv";
		
			$handle = fopen($filename, 'w');
			if(count($sales) > 0) { 
				fputcsv(
				$handle, [
					"#","Name", "Amount"
				]
			);

				foreach($sales as $key=>$sale) {
					fputcsv(
							$handle, [
							  $key+1,
							  isset($sale->user->name)? $sale->user->name:"Unknown",
							  "$".$sale->total_amount,
							]
						);
				}
			fclose($handle);
			
			//return Response::download($filename, "staff_sold.csv", $headers);
			
			}
			$content = array(
				"subject" => "Daily Staff Sales Report ",
				"message" => "",
				"sales" => $sales,
				"file" => $filename,
			);
		Mail::to("arfan67@gmail.com")->send(new ReportsEmail($content));
	}
	
	
	public function DailySales() {
		
		    $query = DB::table("sales");
			$query->whereDay('sales.created_at', '=', date('d'));
			
			$sales = $query->select("*" , "sales.id as id")->leftJoin("sale_items as s" , "s.sale_id" , '=', "sales.id" )->orderBy('sales.created_at', 'DESC')->groupBy("s.sale_id")->get();
			
			$headers = array(
				'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
				'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
				'Content-Disposition' => 'attachment; filename=abc.csv',
				'Expires' => '0',
				'Pragma' => 'public',
			);
			$filename = "daily_sales.csv";
		
			$handle = fopen($filename, 'w');
			if(count($sales) > 0) { 
				fputcsv(
				$handle, [
					"#","Amount", "Discount","Total Amount"
				]
			);
			$total_amount = 0;
			$total_discount = 0;

				foreach($sales as $key=>$sale) {
					fputcsv(
							$handle, [
							  $key+1,
							  "$".$sale->discount,
							  "$".$sale->amount,
							]
						);
				}
				$total_amount += $sale->amount;
				$total_discount += $sale->discount; 
				
						fputcsv(
							$handle, [
							  "Total",
							  "$".$sale->discount,
							  "$".$sale->amount,
							]
						);
						
			fclose($handle);
			
			//return Response::download($filename, "$name.csv", $headers);
			
			}
			$content = array(
				"subject" => "Daily Sales",
				"message" => "Daily Sales",
				"sales" => $sales,
				"file" => $filename,
			);
		Mail::to("arfan67@gmail.com")->send(new ReportsEmail($content));
	}


	public function email_templates(Request $request)
	{
		$data['templates'] = DB::table('email_templates')->select('email_templates.*')->join('users', 'users.id', '=', 'email_templates.updated_by')->get();
		return view("backend.templates.home", $data);
	}

	public function edit_templates($id)
	{
		$data['template'] = Template::find($id);
		$data['newsletters'] = DB::table("newsletters")->get();
		$data['customers'] = User::where("role_id", 4)->get();


		return view("backend.templates.edit", $data);
	}


	public function storeTemplate(Request $request)
	{

		$message = $request->input("message");
		$subject = $request->input("subject");
		$sentto = $request->input("sentto");

		$content = array(
			'subject' => $subject,
			'message' => $message
		);
		if(in_array("all" , $sentto)) {
			$newsletters = DB::table("newsletters")->get();
			foreach ($newsletters as $row) {
				Mail::to($row->email)->send(new NewsletterMail($content));
			}
			$customers = User::where("role_id", 4)->get();
			foreach ($customers as $row) {
				Mail::to($row->email)->send(new NewsletterMail($content));
			}	
		} else { 
			foreach($sentto as $email) {
				Mail::to($email)->send(new NewsletterMail($content));
			}
		}

		

		$data = array(
			"title" => $request->input("title"),
			"status" => $request->input("status", 1),
			"short_code" => $request->input("short_code"),
			"subject" => $request->input("subject"),
			"message" => $request->input("message"),
		);

		if ($request->input('id')) {
			$data["updated_at"] = date("Y-m-d H:i:s");
			$data["updated_by"] = \Auth::user()->id;
			\Session::flash("success_message", "Emails Send Successfully");
			$previoud_data = Template::find($request->input('id'));
			$data_log['title'] = $previoud_data->title;
			$data_log['status'] = $previoud_data->status;
			$data_log['short_code'] = $previoud_data->short_code;
			$data_log['subject'] = $previoud_data->subject;
			$data_log['message'] = $previoud_data->message;
			$data_log['created_by'] = \Auth::user()->id;
			$data_log['updated_by'] = \Auth::user()->id;
			$data_log["created_at"] = date("Y-m-d H:i:s");
			$data_log["updated_at"] = date("Y-m-d H:i:s");
			// $i_id = LogTemplate::insertGetId($data_log);
			Template::where("id", $request->input("id"))->update($data);
			$insert_id = $request->input("id");
			// loginlogs($request, \Auth::user()->id, 'Update Email Templates ( ' . $previoud_data->subject . ' )');
		} else {
			$data["created_at"] = date("Y-m-d H:i:s");
			$data["updated_at"] = date("Y-m-d H:i:s");
			$data["created_by"] = \Auth::user()->id;
			$data["updated_by"] = \Auth::user()->id;
			\Session::flash("success_message", "Emails Send Successfully");
			$insert_id = Template::insertGetId($data);
		}


		return redirect("admin/email_templates");

	}


	public function testEmail($code){

        $tmplate = Template::where('short_code',$code)->first();

        if($tmplate){
            $message = $tmplate->message;
            // $message = booking_email($message,);
            $content = array(
                'subject' => $tmplate->subject,
                'message' => $message
            );

            Mail::to('arfan67@gmail.com')->send(new TestMail($content));
            echo 'Email Sent successfully';
            exit;
        }else{
            echo 'No template Found.';
            exit;
        }


    }

}
