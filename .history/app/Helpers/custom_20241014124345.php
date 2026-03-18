<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use App\User;
use App\Homepage;
use App\Category;
use App\Slider;
use App\Setting;
use App\Product;

/*
 *  Get Modules
 */

function get_product($id) { 
   $type = Product::where("id" , $id)->first();
   return $type;
}
function get_booking_type($id) { 
   $type =  DB::table("booking_types")->where("id" , $id)->first();
   return $type;
}

function get_alerts() {
    return Product::whereRaw("quantity <= min_qty")->orWhereRaw("warehouse <= store_min")->orderBy("name", "ASC")->count();
}
 
function setting_by_key($key) 
{ 
    $res = Setting::where("key", $key)->first();
    if(empty($res)) {
        return false;
    }
    return $res->value;
}
 
function homepage_by_key($key) 
{ 
    $res = Homepage::where("key", $key)->first();
    if(empty($res)) {
        return false;
    }
    return $res->value;
}


function get_booking_types()
{
    $res = DB::table("booking_types")->get();
    if (empty($res)) {
        return false;
    }
    return $res;
}
 
function getSlider() 
{ 
    $sliders = Slider::get();
    return $sliders;
}
 
function getCategory($id) 
{ 
    $category = Category::find($id);
    return $category;
}
function time_elapsed_string($datetime, $full = false) 
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function listFolderFiles($dir)
{
    $editable_extensions = array('php', 'txt', 'text', 'js', 'css', 'html', 'htm', 'xml', 'inc', 'include');
    $ffs = scandir($dir);
    echo '<ol>';
    foreach($ffs as $ff){
        if($ff != '.' && $ff != '..') {
            if(!is_dir($dir.'/'.$ff)) {
                if(preg_match('/\.([^.]+)$/', $ff, $matches)) {
                    $ext = strtolower($matches[1]);
                    if (in_array($ext, $editable_extensions) ) {
                        echo "<li><a href='?file=" . $dir .'/'. urlencode($ff) . "'>".$ff;
                    }
                }
            } else  {
                echo "<li>" . $ff . "</li>";
            }
            if(is_dir($dir.'/'.$ff)) { listFolderFiles($dir.'/'.$ff);
            }
            echo '</a></li>';
        }
    }
    echo '</ol>';
}

function orientate($image, $orientation)
{
    switch ($orientation) {

        // 888888
        // 88    
        // 8888  
        // 88    
        // 88    
    case 1:
        return $image;

        // 888888
        //     88
        //   8888
        //     88
        //     88
    case 2:
        return $image->flip('h');


        //     88
        //     88
        //   8888
        //     88
        // 888888
    case 3:
        return $image->rotate(180);
        
        // 88    
        // 88    
        // 8888  
        // 88
        // 888888
    case 4:
        return $image->rotate(180)->flip('h');

        // 8888888888
        // 88  88    
        // 88        
    case 5:
        return $image->rotate(-90)->flip('h');

        // 88        
        // 88  88    
        // 8888888888
    case 6:
        return $image->rotate(-90);

        //         88
        //     88  88
        // 8888888888
    case 7:
        return $image->rotate(-90)->flip('v');

        // 8888888888
        //     88  88
        //         88
    case 8:
        return $image->rotate(90);

    default:
        return $image;
    }
}


function change_number($value, $number_format = 0)
{
    if ($value < 0) {
        echo  '<span style="color:red"> (' . number_format($value * -1, $number_format) . ')</span>';
    } else {
        if ($value != "") {
            return number_format($value, $number_format);
        }
        return $value;
    }
}


function role_permission($permission_id) { 
    $role_id  = Auth::user()->role_id;
    print_r(Auth::user());
    print_r($role_id);
    exit();
        $check = DB::table("permission_role")->where("role_id" , $role_id)->where("permission_id" , $permission_id)->exists();
    if($check) return true;
    return false;
}


