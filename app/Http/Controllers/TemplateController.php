<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Image;
use App\EmailTemplate as Template;
use App\LogTemplate;
use App\Service;
use DB;
use Mail;
use App\Mail\Template as TestMail;

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


   /* public function updateTemplate($view) {

        
      
        if($view == "confirmation_email") { 
            $temp = DB::table("email_templates")->where("id" , 1)->first();
            $data["data"] = $temp->message;
            $data["template"] = $temp;
        }
        // echo $data["path"]; exit;
      
        return view("admin.templates.update_template" , $data);
    }


    public function saveHtml(Request $request)
    {


        $file = $request->input('file');
        $id = $request->input('id');
        $real_file = base_path($file);

        if (!empty($request->input('message'))) {
            $newcontent = $this->stripslashes_deep($request->input('message'));
            if (is_writeable($real_file)) {
                $f = fopen($real_file, 'w+');
                fwrite($f, $newcontent);
                fclose($f);

                Template::where("id" , $id)->update(array("message" => $newcontent));

                \Session::flash('flash_message', 'File Changed Successfully');
            } else {
                \Session::flash('flash_message', 'File is not Writeable');
            }
        }
        return redirect("admin/email/confirmation_email");
    }*/

    public function index(Request $request)
    {
        loginlogs($request, \Auth::user()->id, 'Email Templates');
        //$data['templates'] = Template::get();
        $data['templates'] = DB::table('email_templates')->select('email_templates.*', 'users.firstname', 'users.lastname')->join('users', 'users.id', '=', 'email_templates.updated_by')->get();
        return view("backend.templates.home", $data);
    }
    public function templates_previous_versions(Request $request)
    {
        //$data['templates'] = Template::get();
        loginlogs($request, \Auth::user()->id, 'Email Templates Previous Versions');
        $data['templates'] = DB::table('email_templates_logs')->select('email_templates_logs.*', 'users.firstname', 'users.lastname')->join('users', 'users.id', '=', 'email_templates_logs.updated_by')->get();
        return view("backend.templates.log", $data);
    }




    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);

        return $value;
    }




    public function store(Request $request)
    {

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
            \Session::flash("success_message", "Email Template Updated Successfully");
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
            $i_id = LogTemplate::insertGetId($data_log);
            Template::where("id", $request->input("id"))->update($data);
            $insert_id = $request->input("id");
            loginlogs($request, \Auth::user()->id, 'Update Email Templates ( ' . $previoud_data->subject . ' )');
        } else {
            $data["created_at"] = date("Y-m-d H:i:s");
            $data["updated_at"] = date("Y-m-d H:i:s");
            $data["created_by"] = \Auth::user()->id;
            $data["updated_by"] = \Auth::user()->id;
            \Session::flash("success_message", "Email Template Added Successfully");
            $insert_id = Template::insertGetId($data);
        }


        return redirect("admin/email_templates");

    }


    public function getTemplate(Request $request)
    {
        $id = $request->input("id");
        $tmplate = Template::find($id);
        echo json_encode($tmplate);
    }

    public function deleteTemplate(Request $request)
    {
        $id = $request->input("id");

        Template::where("id", $id)->delete();
        \Session::flash("success_message", "Email Template Deleted Successfully");

    }

    public function savePreviewTemplate(Request $request)
    {

        $data = array(
            "title" => $request->input("title"),
            "status" => $request->input("status", 1),
            "short_code" => $request->input("short_code"),
            "subject" => $request->input("subject"),
            "message" => $request->input("message"),
        );
        $data["created_at"] = date("Y-m-d H:i:s");
        $data["updated_at"] = date("Y-m-d H:i:s");
        $data["created_by"] = \Auth::user()->id;
        $data["updated_by"] = \Auth::user()->id;

        $insert_id = Preview::insertGetId($data);

        $response['succes'] = true;
        $response['id'] = $insert_id;

        return $response;

    }

    public function previewTemplate($id)
    {
        $data['templates'] = Preview::find($id);

        return view("backend.templates.preview", $data);
    }


    // public function testEmail($code){

    //     $tmplate = Template::where('short_code',$code)->first();

    //     if($tmplate){
    //         $message = $tmplate->message;
    //         $message = booking_email($message,);
    //         $content = array(
    //             'subject' => $tmplate->subject,
    //             'message' => $message
    //         );

    //         Mail::to('usman@rich.pk')->send(new TestMail($content));
    //         echo 'Email Sent successfully';
    //         exit;
    //     }else{
    //         echo 'No template Found.';
    //         exit;
    //     }


    // }


    public function edit($id)
    {
        $data['template'] = Template::find($id);

        return view("backend.templates.edit", $data);
    }



}
