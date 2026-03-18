<?php

namespace App\Http\Controllers;

use App\Product;
use App\Inventory;
use App\Models\Category;
use App\Models\MetrcPackage;
use App\Models\MetrcTestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // 1) Load your products & raw inventories
        $data['product']     = Product::orderBy('name','ASC')->get();
        $inventories         = Inventory::orderBy('id','DESC')->get();
        $data['categories']  = Category::select('name','id')->distinct()->get();

        // 2) Build METRC lookups
        $packages = MetrcPackage::all()
            ->keyBy(fn($p) => Str::upper(trim($p->Label)));
        $labs = MetrcTestResult::all()
            ->groupBy('PackageId');

        // 3) Attach the METRC data onto each inventory model
        $inventories->transform(function($inv) use($packages, $labs) {
            $labelKey = Str::upper(trim($inv->Label));
            if ($labelKey && isset($packages[$labelKey])) {
                $pkg      = $packages[$labelKey];
                $fullLabs = $labs->get($pkg->Id, collect());

                // set them as dynamic properties or relations
                $inv->metrc_package   = $pkg;
                $inv->metrc_full_labs = $fullLabs;
                $inv->metrc_summary   = [
                    'thc' => optional(
                        $fullLabs->firstWhere('TestTypeName','like','Total THC%')
                    )->TestResultLevel
                        ? round(
                            $fullLabs->firstWhere('TestTypeName','like','Total THC%')
                                     ->TestResultLevel / 10,
                            2
                          )
                        : null,
                    'cbd' => optional(
                        $fullLabs->firstWhere('TestTypeName','like','Total CBD%')
                    )->TestResultLevel
                        ? round(
                            $fullLabs->firstWhere('TestTypeName','like','Total CBD%')
                                     ->TestResultLevel / 10,
                            2
                          )
                        : null,
                ];
            }
            return $inv;
        });

        // 4) Pass along the enriched inventories
        $data['inventories'] = $inventories;

        return view('backend.inventories.index', $data);
    }
  public function getSkuAttribute($value)
    {
        if (preg_match('/[eE]/', $value)) {
            return $this->scientificToDecimal($value);
        }
        return $value;
    }

    /**
     * Convert a scientific-notation string into its plain decimal representation.
     */
    protected function scientificToDecimal(string $num): string
    {
        if (!preg_match('/^([-+]?[0-9]*\.?[0-9]+)[eE]([-+]?[0-9]+)$/', $num, $m)) {
            return $num;
        }

        list(, $mantissa, $exp) = $m;
        $exp        = (int) $exp;
        $parts      = explode('.', $mantissa);
        $intPart    = $parts[0];
        $decPart    = $parts[1] ?? '';
        $digits     = $intPart . $decPart;
        $decLen     = strlen($decPart);

        if ($exp >= 0) {
            // push zeros on the right
            return $digits . str_repeat('0', $exp - $decLen);
        } else {
            // insert decimal point to the left
            $pos = strlen($intPart) + $exp;
            if ($pos > 0) {
                return substr($digits, 0, $pos) . '.' . substr($digits, $pos);
            }
            return '0.' . str_repeat('0', abs($pos)) . $digits;
        }
    }


public function edit($id)
{
    $orgId = auth()->user()->organization_id;

    // Eager-load both organization and category
    $inventory = Inventory::with(['organization', 'category'])
                          ->where('organization_id', $orgId)
                          ->findOrFail($id);

    $categories = Category::all();

    return view('backend.inventories.edit', compact('inventory', 'categories'));
}

    /**
     * Queue a background job to refresh METRC data.
     */
    public function syncMetrc(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        Artisan::queue('metrc:sync-inventory', ['org' => $orgId]);

        return response()->json([
            'status'  => 'queued',
            'message' => 'METRC sync queued; it should finish in a few minutes.',
        ]);
    }

    /**
     * AJAX search endpoint for the inventory grid.
     */
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $orgId = auth()->user()->organization_id;

        $inventories = Inventory::with('categoryDetail')
            ->where('organization_id', $orgId)
            ->where(fn($w) =>
                $w->where('name',   'like', "%{$q}%")
                  ->orWhere('sku',  'like', "%{$q}%")
                  ->orWhere('Label','like', "%{$q}%")
            )
            ->orderBy('name')
            ->paginate(20);

        $data = $inventories->map(fn($inv) => [
            'id'            => $inv->id,
            'name'          => $inv->name,
            'sku'           => $inv->sku,
            'Label'         => $inv->Label,
            'storeQty'      => $inv->storeQty,
            'category_name' => optional($inv->categoryDetail)->name,
            'metrc_package' => $inv->metrc_package ?? null,
            'has_image'     => file_exists(public_path("uploads/inventories/{$inv->id}.jpg")),
        ]);

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $inventories->currentPage(),
                'last_page'    => $inventories->lastPage(),
            ],
        ]);
    }
    public function updateType(Request $request)
    {
        $inventory = Inventory::findOrFail($request->inventory_id);
        $inventory->inventory_type = $request->inventory_type;
        $inventory->save();
    
        // You can return any additional data if necessary.
        return response()->json(['success' => true, 'message' => 'Inventory type updated successfully.']);
    }
    public function inventories(Request $request)
    {
        $keyword = $request->get('q', '');
        if(!empty($request->get('start_date')))  $start_date = date("Y-m-d 00:00:00", strtotime($request->get('start_date')));
        if (!empty($request->get('end_date'))) $end_date = date("Y-m-d 23:59:59", strtotime($request->get('end_date')));
        $data["q"] = $keyword;

        $products = Product::where("name","like" ,  "%$keyword%")->get();
        $ids = array();
        $sids = array();
        foreach($products as $product) { 
            $ids[] = $product->id;
        }
        // $suppliers = Supplier::where("name","like" ,  "%$keyword%")->get();
        // foreach ($suppliers as $product) {
        //     $sids[] = $product->id;
        // }
        $query = Inventory::query();
        if(!empty($keyword)) {
           $query->whereIn("product_id" , $ids);
        } 

        $data["start_date"] = "";
        if(!empty($start_date)) { 
             $query->where("created_at" , ">=" , $start_date);
            $data["start_date"] = date("d-m-Y", strtotime($start_date));
        }
        $data["end_date"] = "";
        if(!empty($end_date)) { 
             $query->where("created_at" , "<=" , $end_date);
            $data["end_date"] = date("d-m-Y", strtotime($end_date));
        }
        $inventories = $query->orderBy("id", "DESC")->paginate(25);

        foreach($inventories as $inventory) { 
            $inventory->supplier = Supplier::where("id", $inventory->supplier_id)->first();
            $inventory->product = Product::where("id", $inventory->product_id)->first();
        }
        $data["inventories"] = $inventories;
        return view('backend.inventories.inventory', $data);
    }

    public function updateQuantity(Request $request) { 
        $products = $request->input("product_id");
        $types = $request->input("type");
        $quantity = $request->input("quantity");
        $suppliers = $request->input("supplier_id");
        $comments = $request->input("comments");
        foreach($products as $k=>$product) { 
            $addsub = $types[$k];
            $pro = Product::find($product); 
            if(!empty($product) and !empty($quantity[$k])) {
                if ($addsub == "add") {
                    $data = array(
                        "quantity" => $pro->quantity + $quantity[$k],
                        "warehouse" => $pro->warehouse - $quantity[$k]
                    );

                    $warehouse = $pro->warehouse - $quantity[$k];
                    
                } else {
                    $data = array(
                        "quantity" => $pro->quantity - $quantity[$k],
                        // "warehouse" => $pro->warehouse - $quantity[$k]
                    );

                    $warehouse = $pro->warehouse;
                }

               
                Product::where("id", $product)->update($data);

                $inventory = array(
                    // "supplier_id" => $suppliers[$k],
                    "quantity" => $quantity[$k],
                    "product_id" => $product,
                    "track_type" => $types[$k],
                    "comments" => $comments[$k],
                    "storeroom" => $warehouse,
                    "created_at" => date("Y-m-d H:i:s")
                );
                if($quantity[$k] > 0) {
                    DB::table("inventories")->insert($inventory);
                }
               
            }   
           
        }

        return redirect("update_inventory");
    }



    public function wherehouseInventory(Request $request)
    {
        $data['products'] = Product::orderBy("name" , "ASC")->get();
        $data['suppliers'] = Supplier::orderBy("name" , "ASC")->get();
        return view('backend.inventories.wherehouse', $data);
    }


    public function updateWhereHouseQuantity(Request $request) { 
        $products = $request->input("product_id");
        $types = $request->input("type");
        $quantity = $request->input("quantity");
        $suppliers = $request->input("supplier_id");
        $comments = $request->input("comments");
        foreach($products as $k=>$product) { 
            $addsub = $types[$k];
            $pro = Product::find($product); 
            if(!empty($product) and !empty($quantity[$k])) {
                if ($addsub == "add") {
                    $data = array(
                        "warehouse" => $pro->warehouse + $quantity[$k]
                    );

                    $warehouse = $pro->warehouse - $quantity[$k];
                    
                } else {
                    $data = array(
                         "warehouse" => $pro->warehouse - $quantity[$k]
                    );

                    $warehouse = $pro->warehouse;
                }

               
                Product::where("id", $product)->update($data);

                $inventory = array(
                    // "supplier_id" => $suppliers[$k],
                    "quantity" => $quantity[$k],
                    "product_id" => $product,
                    "type" => "wherehouse",
                    "track_type" => $types[$k],
                    "comments" => $comments[$k],
                    "storeroom" => $warehouse,
                    "created_at" => date("Y-m-d H:i:s")
                );
                if($quantity[$k] > 0) {
                    DB::table("inventories")->insert($inventory);
                }
               
            }   
           
        }

        return redirect("update_werehouse_inventory");
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    private function searchParams($request)
    {
        return [
            'date_range' => $request->get('date_range', null),
            'product'    => $request->get('product', null),
        ];
    }
    
}
