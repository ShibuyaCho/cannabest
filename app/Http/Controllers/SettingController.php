<?php

namespace App\Http\Controllers;

use App\Setting;
use App\Homepage;
use App\Models\Category;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Input;
use Image;

class SettingController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
     public function edit()
{
    $settings = Setting::all();
    
    // Retrieve the discount_tiers setting from the database.
    $discountTiersSetting = $settings->where('key', 'discount_tiers')->first();
    $discountTiers = [];
    
    if ($discountTiersSetting) {
        // Decode the JSON value if it is not already an array.
        $discountTiers = is_array($discountTiersSetting->value)
            ? $discountTiersSetting->value
            : (json_decode($discountTiersSetting->value, true) ?: []);
    }

    return view('backend.settings.general.edit', [
        'settings' => $settings,
        'discountTiers' => $discountTiers,
    ]);
}
     
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // Exclude keys that aren’t settings.
    $data = $request->except(['_method', '_token', 'logo']);
    

    // Process discount tiers to compute price per gram for each pricing option.
    if (isset($data['discount_tiers']) && is_array($data['discount_tiers'])) {
        foreach ($data['discount_tiers'] as $tierIndex => &$tier) {
            if (isset($tier['pricing']) && is_array($tier['pricing'])) {
                foreach ($tier['pricing'] as $pricingIndex => &$pricing) {
                    $minQuantity = isset($pricing['min_quantity']) ? floatval($pricing['min_quantity']) : 0;
                    $price = isset($pricing['price']) ? floatval($pricing['price']) : 0;
                    // Calculate the price per gram (avoid division by zero)
                    $pricing['price_per_gram'] = ($minQuantity > 0) ? round($price / $minQuantity, 2) : 0;
                    
                }
            }
        }
    }

    // Loop through each key to update or create the setting.
    foreach ($data as $key => $value) {
        $setting = Setting::firstOrNew(['key' => $key]);
        // If the value is an array, assign it directly (Laravel will cast it to JSON if your model has the appropriate cast)
        // Otherwise, escape the string.
        $setting->value = is_array($value) ? $value : e($value);
        $setting->save();
    }
    
    // Process logo file upload if provided.
    if ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $file_name = "logo.png";
        $destination = public_path("public/uploads");
        $file->move($destination, $file_name);
    }
    
    return redirect('settings/general')
        ->with('message-success', 'Settings updated successfully!');
        }
        
       
    
    public function generalSettings()
    {
        // Get all settings as an associative array (key => value)
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Decode discount tiers from JSON to array, or default to an empty array.
        $discountTiers = [];
        if (isset($settings['discount_tiers'])) {
            if (is_array($settings['discount_tiers'])) {
                $discountTiers = $settings['discount_tiers'];
            } else {
                $discountTiers = json_decode($settings['discount_tiers'], true) ?: [];
            }
        }

        return view('settings.general', compact('settings', 'discountTiers'));
    }
    
    public function homePage() 
    {
        $data = [
            'homepage' => Homepage::where("type", "!=", "")->get(),
            'categories' => Category::all(),
        ];
        return view('backend.settings.homepage', $data);
    }    
    
    public function homePageUpdate(Request $request)
    {
        $form = $request->except('_method', '_token' );
        $form = collect($form);

        $form->each(function ($value, $key) {
            $setting = Homepage::where(['key' => $key])->first();
            if ($setting->key == "category") { 
                $value = implode(",", $value);
            }
            $setting->value = $value;
            $setting->save();
        });
		
        return redirect('settings/homepage')
            ->with('message-success', 'Homepage updated!');
    }
    
    public function MenuManagement(Request $request)
    {
         $inactive_pages = DB::table("menus")->where("active", 0)->orderBy('order_by', "ASC")->get();
         $activemenus = DB::table("menus")->where("parent_id", 0)->where("active", 1)->orderBy('order_by', "ASC")->get();
         foreach ($activemenus as $menu) { 
             $menu->child = DB::table("menus")->where("parent_id", $menu->menu_id)->orderBy('order_by', "ASC")->get();
         }

         return view('backend.settings.menu_manage', [
             "pages" => $inactive_pages,
             "menus" => $activemenus,
             "title" => "Menus"
         ]);
    }

    function saveList(Request $request) 
    {
        $list = $request->all();
        $p_o = 1;
        foreach ($request->all() as $key => $item) {
            foreach ($item as $l) {
                $menu_id = $l['id'];
                $parent_record = DB::table("menus")->where("menu_id", $menu_id)->first();
                if (!empty($parent_record)) { 
                    $data = [
                        "active" => 1,
                        "parent_id" => 0,
                        "order_by" => $p_o
                    ];
                    DB::table("menus")->where("menu_id", $menu_id)->update($data);
                } 
                if (!empty($l['children'])) {
                    $c_o = 1;
                    foreach ($l['children'] as $child) { 
                        $data = [
                            "active" => 1,
                            "parent_id" => $menu_id,
                            "order_by" => $c_o
                        ];
                    
                        $child_record = DB::table("menus")->where("menu_id", $menu_id)->first();
                        if (!empty($child_record)) { 
                            DB::table("menus")->where("menu_id", $child['id'])->update($data);
                        } 
                        $c_o++;
                    } 
                }
                $p_o++;
            }
        }
    }

    function saveRemoved(Request $request) 
    {
        $list = $request->all();
        $p_o = 1;
        foreach ($request->all() as $key => $item) {
            foreach ($item as $l) {
                $menu_id = $l['id'];
                $parent_record = DB::table("menus")->where("menu_id", $menu_id)->first();
                if (!empty($parent_record)) { 
                    $data = [
                        "active" => 0,
                        "parent_id" => 0,
                        "order_by" => $p_o
                    ];
                    DB::table("menus")->where("menu_id", $menu_id)->update($data);
                } 
                if (!empty($l['children'])) {
                    $c_o = 1;
                    foreach ($l['children'] as $child) { 
                        $data = [
                            "active" => 0,
                            "parent_id" => $menu_id,
                            "order_by" => $c_o
                        ];
                    
                        $child_record = DB::table("menus")->where("menu_id", $menu_id)->first();
                        if (!empty($child_record)) { 
                            DB::table("menus")->where("menu_id", $child['id'])->update($data);
                        } 
                        $c_o++;
                    } 
                }
                $p_o++;
            }
        }
    }
}
