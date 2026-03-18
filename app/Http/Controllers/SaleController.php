<?php
// app/Http/Controllers/SaleController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sale;
use App\SaleItem;
use App\Models\Customer;
use App\Models\MetrcTestResult;
use App\Models\MetrcPackage;
use App\Models\Organization;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Shift;
use App\Models\Category;
use App\Models\DrawerSession;
use App\Product;
use App\Models\CashDrawer;
use App\Inventory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Validator;
use DB;
use App\User;
use DateTime;
use Carbon\Carbon;
use App\Services\MetrcClient;

class SaleController extends Controller
{
    private const CUSTOMER_ROLE_ID = 5;

    /* ---------------------------------------------------------------------
       Tiny utilities
    --------------------------------------------------------------------- */

    // NEW: inventory query that works even if the model doesn't use SoftDeletes
    private function inventoryQuery()
    {
        $q = Inventory::query();
        try {
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(Inventory::class))) {
                return $q->withTrashed();
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $q;
    }

    // NEW: safe category name ('' when missing). Accepts Inventory|null.
    private function safeCategoryName($inventory): string
    {
        if (!$inventory) return '';
        try {
            // use relation if available; otherwise derive from category_id
            $name = $inventory->categoryDetail->name ?? null;
        } catch (\Throwable $e) {
            $name = null;
        }
        if (!$name && isset($inventory->category_id)) {
            $name = DB::table('categories')->where('id', $inventory->category_id)->value('name');
        }
        return strtolower(trim((string)($name ?? '')));
    }

    // NEW: is flower without assuming category exists
    private function isFlower($inventory): bool
    {
        if (!$inventory) return false;
        $cid = (int)($inventory->category_id ?? 0);
        if ($cid === 1) return true; // your schema's "1 = flower"
        $nm = $this->safeCategoryName($inventory);
        return $nm !== '' && str_contains($nm, 'flower');
    }

    // NEW: categories that are NOT reported to METRC (skip in METRC payloads)
    private function metrcSkipCats(): array
    {
        return ['accessories', 'apparel'];
    }

    // NEW: categories exempt from tax (hemp included)
    private function taxExemptCats(): array
    {
        return ['accessories','non-taxable','apparel','hemp'];
    }

    /* -------------------- Facility timezone -------------------- */
    private function facilityTz(): string
    {
        $raw = (string)(setting_by_key('store_timezone') ?? (config('app.timezone') ?: 'UTC'));
        $map = [
            'PT'=>'America/Los_Angeles','PST'=>'America/Los_Angeles','PDT'=>'America/Los_Angeles',
            'MT'=>'America/Denver','MST'=>'America/Denver','MDT'=>'America/Denver',
            'CT'=>'America/Chicago','CST'=>'America/Chicago','CDT'=>'America/Chicago',
            'ET'=>'America/New_York','EST'=>'America/New_York','EDT'=>'America/New_York',
            'AKST'=>'America/Anchorage','AKDT'=>'America/Anchorage',
            'HST'=>'Pacific/Honolulu','HDT'=>'Pacific/Honolulu',
            'Pacific Standard Time'=>'America/Los_Angeles',
            'Mountain Standard Time'=>'America/Denver',
            'Central Standard Time'=>'America/Chicago',
            'Eastern Standard Time'=>'America/New_York',
        ];
        if (isset($map[$raw])) $raw = $map[$raw];
        return in_array($raw, \DateTimeZone::listIdentifiers(), true) ? $raw : 'UTC';
    }

    /* -------------------- Pricing helpers (category-safe) -------------------- */

    private function priceIsLineTotal(SaleItem $it, ?string $categoryName = null): bool
    {
        $flagAttr = $it->getAttribute('price_is_line_total');
        if (!is_null($flagAttr)) return (bool)$flagAttr;

        $lineTotalAttr = $it->getAttribute('line_total');
        if (is_numeric($lineTotalAttr) && (float)$lineTotalAttr > 0) return true;

        $qty        = (float)$it->quantity;
        $fractional = abs($qty - floor($qty)) > 0.0001;

        // if no category is known, don't force flower logic
        $catLower   = strtolower((string)$categoryName);
        $flowerish  = $catLower ? str_contains($catLower, 'flower') : false;

        return $fractional || $flowerish;
    }

    private function lineTotalFor(SaleItem $it, ?string $categoryName = null): float
    {
        $lineTotalAttr = $it->getAttribute('line_total');
        if (is_numeric($lineTotalAttr) && (float)$lineTotalAttr > 0) return (float)$lineTotalAttr;

        $priceIsLine = $this->priceIsLineTotal($it, $categoryName);
        $price = (float)$it->price;
        $qty   = (float)$it->quantity;

        return $priceIsLine ? $price : ($price * $qty);
    }

    private function unitPriceFor(SaleItem $it, ?string $categoryName = null): float
    {
        $unitAttr = $it->getAttribute('unit_price');
        if (is_numeric($unitAttr) && (float)$unitAttr > 0) return (float)$unitAttr;

        $qty = max(1e-9, (float)$it->quantity);
        if ($this->priceIsLineTotal($it, $categoryName)) {
            $line = $this->lineTotalFor($it, $categoryName);
            return round($line / $qty, 2);
        }
        return (float)$it->price;
    }

    /* -------------------- INDEX / EOD (receipt-aligned) -------------------- */

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate   = $request->input('end_date',   now()->toDateString());
        $keyword   = $request->get('q', '');
        $orgId     = Auth::user()->organization_id;

        $baseQuery = Sale::with(['items.inventory.categoryDetail', 'items.product', 'customer'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereHas('user', fn($q) => $q->where('organization_id', $orgId));

        if ($keyword) {
            $baseQuery->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $keyword . '%'));
            });
        }

        $sales = (clone $baseQuery)->orderBy('created_at', 'DESC')->paginate(999)
                 ->appends(compact('keyword','startDate','endDate'));

        $completed = (clone $baseQuery)->where('status', 1)->get();

        $grossPost = 0.0; $preTaxSum = 0.0; $taxTotal = 0.0;
        $expectedCash = 0.0; $cardTotal = 0.0; $txns = 0;

        $discountSum=0.0; $taxStateSum=0.0; $taxCountySum=0.0; $taxCitySum=0.0;

        foreach ($completed as $sale) {
            $compiled = $this->compileReceiptData($sale);
            $due  = (float)$compiled['total_due'];
            $tax  = (float)$compiled['tax'];
            $pre  = (float)$compiled['pre_tax'];
            $paid = (float)$compiled['paid'];
            $chg  = (float)$compiled['change'];

            if ($due <= 0 && $paid <= 0) continue;

            $txns++;
            $grossPost += $due;
            $preTaxSum += $pre;
            $taxTotal  += $tax;

            if (strtolower((string)$sale->payment_type) === 'cash') {
                $expectedCash += max(0.0, $paid - $chg);
            } else {
                $cardTotal += $paid; // no ceiling — match receipt
            }

            $taxStateSum  += (float)($sale->state_tax  ?? 0);
            $taxCountySum += (float)($sale->county_tax ?? 0);
            $taxCitySum   += (float)($sale->city_tax   ?? 0);

            $d = (float)($sale->order_discount_value ?? 0);
            if ($d <= 0 && (float)($sale->discount ?? 0) > 0) $d = (float)$sale->discount;
            $discountSum += max(0,$d);
        }

        $voids = (clone $baseQuery)->where('status',0)->get();
        $voidCount  = $voids->count();
        $voidAmount = round($voids->sum(fn($s)=> (float)($s->amount ?? 0)), 2);

        $eod = [
            'cash'           => round($expectedCash, 2),
            'debit'          => round($cardTotal, 2),
            'totalSales'     => round($grossPost, 2),
            'transactions'   => $txns,
            'tax'            => round($taxTotal, 2),
            'preTax'         => round($preTaxSum, 2),

            'discounts'      => round($discountSum, 2),
            'tax_state'      => round($taxStateSum, 2),
            'tax_county'     => round($taxCountySum, 2),
            'tax_city'       => round($taxCitySum, 2),
            'void_count'     => $voidCount,
            'void_amount'    => $voidAmount,
        ];

        return view('backend.sales.index', [
            'sales'          => $sales,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'eod'            => $eod,
            'license_number' => optional(Auth::user()->organization)->license_number ?? '',
            'apiKey'         => Auth::user()->apiKey ?? null,
        ]);
    }

    /* -------------------- Misc inventory/holds -------------------- */

    public function showHoldOrder($id)
    {
        $order = DB::table('hold_order')->where('id',$id)->first();
        if (!$order) return response()->json(['message'=>'Not found'],404);
        $order->cart = json_decode($order->cart, true);
        return response()->json($order);
    }

    public function create(Request $request)
    {
        $categories = Category::all();

        $baseQuery = Inventory::with(['categoryDetail'])
            ->where('storeQty','>',0)->where('inventory_type','inventories')
            ->orderByDesc('id');

        $allInv = $baseQuery->get();

        $labelKeys = $allInv->pluck('Label')->filter()
            ->map(fn($l)=> Str::upper(trim($l)))->unique()->values();

        $packages = MetrcPackage::whereIn(\DB::raw('UPPER(TRIM(Label))'), $labelKeys)
            ->get()->keyBy(fn($p)=> Str::upper(trim($p->Label)));

        $labs = MetrcTestResult::whereIn('PackageId',$packages->pluck('Id'))
            ->get()->groupBy('PackageId');

        $allInv->transform(function ($inv) use ($packages,$labs) {
            $key = Str::upper(trim($inv->Label));
            if ($key && isset($packages[$key])) {
                $pkg = $packages[$key];
                $inv->metrc_package   = $pkg;
                $inv->metrc_full_labs = $labs->get($pkg->Id, collect());
            } else {
                $inv->metrc_package   = null;
                $inv->metrc_full_labs = collect();
            }
            return $inv;
        });

        $perPage = 1000;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $slice = $allInv->forPage($currentPage, $perPage);
        $inventories = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice, $allInv->count(), $perPage, $currentPage,
            ['path'=>$request->url(), 'query'=>$request->query()]
        );

        $organization  = Auth::user()->organization;
        $rawTiers      = $organization->discount_tiers ?? [];
        $discountTiers = is_array($rawTiers) ? $rawTiers : (json_decode($rawTiers, true) ?: []);

        $shiftData = Shift::where('cashier_id', Auth::id())
            ->where('is_complete', false)->latest('shift_start_time')->first();

        $drawers        = CashDrawer::where('status','active')->get();
        $currentSession = DrawerSession::where('user_id', Auth::id())->whereNull('closed_at')->first();

        return view('backend.sales.create', [
            'categories'     => $categories,
            'inventories'    => $inventories,
            'discountTiers'  => $discountTiers,
            'shiftData'      => $shiftData,
            'drawers'        => $drawers,
            'currentSession' => $currentSession,
            'licenseNumber'  => optional($organization)->license_number,
            'apiKey'         => Auth::user()->apiKey,
        ]);
    }

    public function recalcPrice(Request $request)
    {
        $this->validate($request, [
            'selectedTier' => 'required|string',
            'quantity'     => 'required|numeric',
        ]);

        $selectedTier = $request->input('selectedTier');
        $quantity     = (float)$request->input('quantity');
        $basePrice    = (float)$request->input('basePrice');

        $discountTiersSetting = \App\Setting::where('key','discount_tiers')->first();
        $discountTiers = [];
        if ($discountTiersSetting) {
            $raw = $discountTiersSetting->value ?? '';
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $discountTiers = $decoded;
            } elseif (is_array($raw)) {
                $discountTiers = $raw;
            }
        }

        $effectivePrice = $basePrice;
        foreach ($discountTiers as $tier) {
            if (($tier['name'] ?? null) === $selectedTier && is_array($tier['pricing'] ?? null)) {
                $best = null;
                foreach ($tier['pricing'] as $pricing) {
                    $minQ = isset($pricing['min_quantity']) ? (float)$pricing['min_quantity'] : 0;
                    if ($quantity >= $minQ) {
                        if (!$best || $minQ > (float)$best['min_quantity']) $best = $pricing;
                    }
                }
                if ($best) {
                    if (!empty($best['price_per_gram'])) {
                        $effectivePrice = (float)$best['price_per_gram'];
                    } else {
                        $effectivePrice = ((float)($best['min_quantity'] ?? 0) > 0)
                            ? ((float)$best['price'] / (float)$best['min_quantity'])
                            : $basePrice;
                    }
                }
            }
        }

        return response()->json(['effectivePrice'=>$effectivePrice]);
    }

    /* -------------------- Complete Sale -------------------- */

   public function completeSale(Request $request)
{
    // --- Normalize inbound body so validation sees the right keys ---
    // Accept any of:
    //  1) Raw JSON:        { cart: [...], payment_type: "...", total_amount: ... }
    //  2) Form with JSON:  payload="{...}"
    //  3) Form with array: payload[...] inputs
    $data = [];

    // Try raw JSON first
    if ($request->isJson()) {
        $data = $request->json()->all() ?: [];
    }

    // If still empty, try `payload` field from form posts
    if (empty($data)) {
        $payload = $request->input('payload');

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } elseif (is_array($payload)) {
            // e.g. payload[cart][0][product_id] style form inputs
            $data = $payload;
        }

        // Last resort: accept root form inputs (cart=..., payment_type=..., etc.)
        if (empty($data)) {
            $data = $request->all();
        }
    }

    // Some clients might nest AGAIN as data['payload'] = '{...}'
    if (isset($data['payload']) && is_string($data['payload'])) {
        $maybe = json_decode($data['payload'], true);
        if (is_array($maybe)) $data = $maybe;
    }

    // Make validator see the normalized shape
    $request->replace($data);

    // --- Validate (now supports split) ---
    $request->validate([
        'cart'                         => 'required|array|min:1',
        'cart.*.product_id'            => 'required|integer',
        'cart.*.quantity'              => 'required|numeric|min:0.0001',
        'cart.*.price'                 => 'required|numeric|min:0',
        'cart.*.price_is_line_total'   => 'nullable|boolean',
        'cart.*.inline_discount_type'  => 'nullable|in:fixed,percent',
        'cart.*.inline_discount_value' => 'nullable|numeric|min:0',

        'payment_type'   => 'required|in:cash,card,split',
        'total_amount'   => 'required|numeric|min:0',

        'orderDiscountType'   => 'nullable|in:fixed,percent',
        'orderDiscountValue'  => 'nullable|numeric|min:0',
        'orderDiscountReason' => 'nullable|string|max:255',

        'customerType'    => 'nullable|in:consumer,patient,caregiver',
        'customerEmail'   => 'nullable|email',
        'customerName'    => 'nullable|string|max:255',
        'medNumber'       => 'nullable|string|max:255',
        'issuedDate'      => 'nullable|date',
        'expirationDate'  => 'nullable|date',
        'caregiverNumber' => 'nullable|string|max:255',

        'drawer_session_id' => 'nullable|integer',
        'order_type'        => 'nullable|in:pos,order',

        // Required when relevant; split needs both
        'cashReceived' => 'required_if:payment_type,cash,split|numeric|min:0',
        'cardTotal'    => 'required_if:payment_type,card,split|numeric|min:0',
        'cardLast4'    => 'required_if:payment_type,card,split|digits:4',
    ]);

    // From here on, use the normalized $data
    $data = $request->all();

    return \DB::transaction(function () use ($data) {
        $user = auth()->user();
        $org  = optional($user)->organization;

        $stateRate  = (float)(($org->state_tax  ?? 0) / 100);
        $countyRate = (float)(($org->county_tax ?? 0) / 100);
        $cityRate   = (float)(($org->city_tax   ?? 0) / 100);

        $customerType      = strtolower($data['customerType'] ?? 'consumer');
        $isTaxableCustomer = ($customerType === 'consumer');

        $orderDiscTypeIn  = $data['orderDiscountType'] ?? null;
        $orderDiscValueIn = (float)($data['orderDiscountValue'] ?? 0);
        $orderDiscReason  = trim((string)($data['orderDiscountReason'] ?? ''));

        $ids = collect($data['cart'])->pluck('product_id')->all();
        $inventories = $this->inventoryQuery()
            ->with('categoryDetail')
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $sale = new \App\Sale();
        $sale->user_id               = $user->id;
        $sale->customer_id           = null;
        $sale->status                = 1;
        $sale->type                  = $data['order_type'] ?? 'pos';
        $sale->payment_type          = $data['payment_type']; // 'cash' | 'card' | 'split'
        $sale->customer_type         = $customerType;
        $sale->drawer_session_id     = $data['drawer_session_id'] ?? null;
        $sale->order_discount_type   = null;
        $sale->order_discount_value  = 0.00;
        $sale->order_discount_reason = $orderDiscReason ?: null;
        $sale->name                  = $data['customerName'] ?? null;
        $sale->med_number            = $data['medNumber'] ?? null;
        $sale->caregiver_number      = $data['caregiverNumber'] ?? null;
        $sale->ismetricsend          = 0;
        $sale->save();

        if (!empty($data['customerEmail'])) {
            $email = strtolower(trim($data['customerEmail']));
            $existing = \App\User::where('email', $email)->first();
            if ($existing) {
                $sale->customer_id = $existing->id;
            } else {
                $nameGuess = trim((string)($data['customerName'] ?? ''));
                if ($nameGuess === '') $nameGuess = ucfirst(strtok($email, '@'));
                $cust = new \App\User();
                $cust->name            = $nameGuess;
                $cust->email           = $email;
                $cust->password        = \Hash::make('password');
                $cust->role_id         = self::CUSTOMER_ROLE_ID;
                $cust->organization_id = $user->organization_id;
                $cust->save();
                $sale->customer_id = $cust->id;
            }
            $sale->save();
        }

        static $hasCols = null;
        if ($hasCols === null) {
            $hasCols = [
                'price_is_line_total'  => \Schema::hasColumn('sale_items', 'price_is_line_total'),
                'unit_price'           => \Schema::hasColumn('sale_items', 'unit_price'),
                'line_total'           => \Schema::hasColumn('sale_items', 'line_total'),
                'inline_discount_type' => \Schema::hasColumn('sale_items', 'inline_discount_type'),
                'inline_discount_value'=> \Schema::hasColumn('sale_items', 'inline_discount_value'),
                'last4'                => \Schema::hasColumn('sales', 'card_last4'),
            ];
        }

        $grossSubtotal=0.0; $inlineTotal=0.0; $netSubtotal=0.0; $taxableNet=0.0;

        foreach ($data['cart'] as $line) {
            $inv = $inventories->get((int)$line['product_id']); // may be null

            $qty = (float)($line['quantity'] ?? 0);
            if ($qty <= 0) return response()->json(['success'=>false,'message'=>'Quantity must be greater than zero.'], 422);

            $sentPrice  = (float)($line['price'] ?? 0);
            $priceIsLine= !empty($line['price_is_line_total']);
            $unitIn     = isset($line['unit_price']) && is_numeric($line['unit_price']) ? (float)$line['unit_price'] : null;
            $lineIn     = isset($line['line_total']) && is_numeric($line['line_total']) ? (float)$line['line_total'] : null;

            $unit      = $priceIsLine ? ($unitIn ?? ($qty > 0 ? round(($lineIn ?? $sentPrice)/$qty, 2) : 0.00))
                                      : ($unitIn ?? $sentPrice);
            $lineGross = $priceIsLine ? ($lineIn ?? $sentPrice) : round($unit * $qty, 2);

            $lt = $line['inline_discount_type']  ?? null;
            $lv = (float)($line['inline_discount_value'] ?? 0);
            $inline = 0.0;
            if ($lt && $lv > 0) $inline = ($lt==='percent') ? round($lineGross*($lv/100), 2) : min($lv, $lineGross);
            $lineNet = max(0.0, $lineGross - $inline);

            $grossSubtotal += $lineGross;
            $inlineTotal   += $inline;
            $netSubtotal   += $lineNet;

            $catName  = $this->safeCategoryName($inv);
            $isExempt = in_array($catName, $this->taxExemptCats(), true);
            if ($isTaxableCustomer && !$isExempt) $taxableNet += $lineNet;

            $si = new \App\SaleItem();
            $si->sale_id    = $sale->id;
            $si->product_id = (int)($line['product_id']);
            $si->quantity   = $qty;
            $si->price      = $priceIsLine ? $lineGross : $unit;

            if ($hasCols['price_is_line_total'])  $si->price_is_line_total  = $priceIsLine;
            if ($hasCols['unit_price'])           $si->unit_price           = $unit;
            if ($hasCols['line_total'])           $si->line_total           = $lineGross;
            if ($hasCols['inline_discount_type']) $si->inline_discount_type = $lt ?: null;
            if ($hasCols['inline_discount_value'])$si->inline_discount_value= $lv ?: 0;
            $si->save();

            if ($inv) {
                $inv->storeQty = max(0, ($inv->storeQty ?? 0) - $qty);
                $inv->save();
            }
        }

        $orderLevelDiscount = 0.0;
        if ($orderDiscValueIn > 0 && $netSubtotal > 0) {
            $orderLevelDiscount = ($orderDiscTypeIn === 'percent')
                ? round($netSubtotal * ($orderDiscValueIn / 100), 2)
                : min($orderDiscValueIn, $netSubtotal);
        }

        $taxablePortion         = $netSubtotal > 0 ? ($taxableNet / $netSubtotal) : 0.0;
        $discountOnTaxableShare = round($orderLevelDiscount * $taxablePortion, 2);
        $taxableAfterDiscount   = max(0.0, $taxableNet - $discountOnTaxableShare);

        $stateTax  = $isTaxableCustomer ? round($taxableAfterDiscount * $stateRate,  2) : 0.0;
        $countyTax = $isTaxableCustomer ? round($taxableAfterDiscount * $countyRate, 2) : 0.0;
        $cityTax   = $isTaxableCustomer ? round($taxableAfterDiscount * $cityRate,   2) : 0.0;

        $preTaxAfterOrder = max(0.0, $netSubtotal - $orderLevelDiscount);

        $targetTotal = (float)$data['total_amount'];
        $autoDiscount = 0.0; $autoReason = null;

        if ($targetTotal > 0) {
            $totalRate = $stateRate + $countyRate + $cityRate;
            $targetPreTax = $isTaxableCustomer
                ? ($totalRate > 0 ? round($targetTotal / (1 + $totalRate), 2) : $targetTotal)
                : $targetTotal;

            $diff = round($preTaxAfterOrder - $targetPreTax, 2);
            if ($diff > 0.01) { $autoDiscount = min($diff, $preTaxAfterOrder); $autoReason = 'Auto-reconcile to cart total'; }

            if ($autoDiscount > 0) {
                $addOnTaxable = round($autoDiscount * ($taxablePortion ?: 0.0), 2);
                $taxableAfterDiscount = max(0.0, $taxableAfterDiscount - $addOnTaxable);

                $stateTax  = $isTaxableCustomer ? round($taxableAfterDiscount * $stateRate,  2) : 0.0;
                $countyTax = $isTaxableCustomer ? round($taxableAfterDiscount * $countyRate, 2) : 0.0;
                $cityTax   = $isTaxableCustomer ? round($taxableAfterDiscount * $cityRate,   2) : 0.0;
            }
        }

        $finalOrderDiscount = round($orderLevelDiscount + $autoDiscount, 2);
        $finalPreTax        = max(0.0, $netSubtotal - $finalOrderDiscount);
        $finalTotal         = round($finalPreTax + $stateTax + $countyTax + $cityTax, 2);
        if (!empty($targetTotal) && $targetTotal > 0) $finalTotal = round($targetTotal, 2);

        // Sum paid, including split
        $paymentType = $data['payment_type'];
        if ($paymentType === 'cash') {
            $paid = (float)($data['cashReceived'] ?? 0);
        } elseif ($paymentType === 'card') {
            $paid = (float)($data['cardTotal'] ?? 0);
        } else { // split
            $paid = (float)($data['cashReceived'] ?? 0) + (float)($data['cardTotal'] ?? 0);
        }
        $change = max(0, round($paid - $finalTotal, 2));

        $sale->subtotal             = round($grossSubtotal, 2);
        $sale->discount             = round($inlineTotal + $finalOrderDiscount, 2);
        $sale->order_discount_type  = $finalOrderDiscount > 0 ? 'fixed' : ($orderDiscTypeIn ?: null);
        $sale->order_discount_value = $finalOrderDiscount;
        $sale->order_discount_reason= $autoReason ?: ($sale->order_discount_reason ?: null);

        $sale->state_tax  = $stateTax;
        $sale->county_tax = $countyTax;
        $sale->city_tax   = $cityTax;

        $sale->amount      = $finalTotal;
        $sale->total_given = round($paid, 2);
        $sale->change      = $change;

        if (($paymentType === 'card' || $paymentType === 'split')
            && !empty($data['cardLast4'])
            && \Schema::hasColumn('sales', 'card_last4')) {
            $sale->card_last4 = $data['cardLast4'];
        }

        $sale->payment_success = 1;
        $sale->save();

        return response()->json(['success'=>true,'sale_id'=>$sale->id]);
    });
}


    public function cancel($id)
    {
        return DB::transaction(function () use ($id) {
            $sale = \App\Sale::with(['items.inventory'])->lockForUpdate()->findOrFail($id);
            if ((int)$sale->status !== 1) {
                return redirect()->back()->with('info', "Sale #{$sale->id} was already canceled.");
            }

            $sale->status = 0;
            $sale->save();

            foreach ($sale->items as $item) {
                $inv = $item->inventory;
                if (!$inv) { \Log::warning("SaleItem {$item->id} missing inventory on cancel {$sale->id}"); continue; }
                $inv->storeQty = max(0, (float)($inv->storeQty ?? 0)) + (float)$item->quantity;
                $inv->save();
            }

            return redirect()->back()->with('success', "Sale #{$sale->id} has been canceled and inventory released.");
        });
    }

    public function printFlowerLabel($id)
    {
        $item = SaleItem::with(['inventory.categoryDetail','sale','inventory.metrc_package.tests'])->findOrFail($id);

        $inventory = $item->inventory;
        $package   = $inventory?->metrc_package;
        $labs      = $package?->tests ?? collect();
        $latestLab = $labs->first();

        $rawPayload = $package->payload ?? null;
        $payload    = is_string($rawPayload) ? json_decode($rawPayload, true) : (is_array($rawPayload) ? $rawPayload : []);

        $batchNumber = data_get($payload, 'Item.BatchNumber', $inventory->Label ?? '');
        $packDate    = data_get($payload, 'Item.PackagedDate');
        $harvested   = $packDate ? Carbon::parse($packDate)->format('m/d/Y') : '—';

        $labDateTested = optional($latestLab)->DateTested;
        $pkgRecorded   = data_get($payload, 'LabTestingRecordedDate');
        $rawTestDate   = $labDateTested ?: $pkgRecorded;
        $testDate      = $rawTestDate ? Carbon::parse($rawTestDate)->format('m/d/Y') : '—';

        $testedByName    = optional($latestLab)->LabFacilityName          ?: '—';
        $testedByLicense = optional($latestLab)->LabFacilityLicenseNumber ?: '—';

        $thcEntry = $labs->first(fn($l)=> Str::contains(Str::lower($l->TestTypeName), 'thc'));
        $cbdEntry = $labs->first(fn($l)=> Str::contains(Str::lower($l->TestTypeName), 'cbd'));

        $thcPct = is_numeric(optional($thcEntry)->TestResultLevel)
                    ? round($thcEntry->TestResultLevel/10, 2)
                    : round((float)($inventory->THC ?? 0), 2);

        $cbdPct = is_numeric(optional($cbdEntry)->TestResultLevel)
                    ? round($cbdEntry->TestResultLevel/10, 2)
                    : round((float)($inventory->CBD ?? 0), 2);

        return view('backend.sales.labels.flower', compact(
            'item','inventory','package','latestLab'
        ) + [
            'sale'            => $item->sale,
            'organization'    => auth()->user()->organization,
            'batchNumber'     => $batchNumber,
            'harvested'       => $harvested,
            'testedByName'    => $testedByName,
            'testedByLicense' => $testedByLicense,
            'testDate'        => $testDate,
            'thcPct'          => $thcPct,
            'cbdPct'          => $cbdPct,
        ]);
    }

    /* -------------------- Holds -------------------- */

    public function holdOrder(Request $request)
    {
        $request->validate([
          'cart'          => 'required|array|min:1',
          'customer_name' => 'nullable|string',
          'table_id'      => 'nullable|integer',
        ]);

        $cart = $request->input('cart');
        $total = collect($cart)->sum(function($i){
            $price = (float)($i['price'] ?? 0);
            $qty   = (float)($i['quantity'] ?? 0);
            $isLine= !empty($i['price_is_line_total']);
            return $isLine ? $price : ($price * $qty);
        });

        $payload = [
            'customer_name'   => $request->input('customer_name'),
            'table_id'        => $request->input('table_id'),
            'cart'            => json_encode($cart),
            'total_amount'    => $total,
            'comment'         => $request->input('orderDiscountReason', ''),
            'user_id'         => Auth::id(),
            'status'          => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        if ($id = $request->input('id')) {
            DB::table('hold_order')->where('id',$id)->where('status',0)->update($payload);
            return response()->json(['success'=>true,'message'=>'Hold order updated']);
        }

        DB::table('hold_order')->insert($payload);
        return response()->json(['success'=>true,'message'=>'Order held']);
    }

    public function viewHoldOrder(Request $request)
    {
        $id = $request->input("id");
        $order = DB::table("hold_order")->where("id",$id)->first();
        echo $order->cart;
    }

    public function holdOrders(Request $request)
    {
        $orders = DB::table('hold_order')->where('status',0)->get()
            ->map(function($order){
                $user = User::find($order->user_id);
                $order->input_by      = $user ? $user->name : '—';
                $order->customer_name = $order->customer_name ?? '—';
                $order->held_at       = $order->created_at;
                return $order;
            });

        return response()->json($orders);
    }

    public function removeHoldOrder($id)
    {
        DB::table('hold_order')->where('id',$id)->delete();
        return response()->json(['success'=>true]);
    }

    public function updateSaleData(Request $request)
    {
        $updatedRows = Sale::where('ismetricsend', false)->update(['ismetricsend'=>true]);
        return response()->json(['updated'=>$updatedRows,'status'=>'success']);
    }

    public function changeOrderStatus(Request $request)
    {
        $id = $request->input("id");
        $status = $request->input("status");
        $deliveryTime = $request->input("deliveryTime");
        DB::table("sales")->where("id",$id)->update(["status"=>$status,"delivery_time"=>$deliveryTime]);
        return $status;
    }

    /* -------------------- METRC: proxy & sale payload (no tax fields) -------------------- */

    public function proxyReceipts(Request $request)
    {
        $raw     = $request->getContent();
        $payload = json_decode($raw, true) ?: [];

        if (!function_exists('array_is_list')) {
            function array_is_list(array $arr): bool {
                if ($arr === []) return true;
                return array_keys($arr) === range(0, count($arr) - 1);
            }
        }

        $receipts = [];
        if (is_array($payload) && array_is_list($payload)) {
            $receipts = $payload;
        } elseif (is_array($payload) && isset($payload['Receipts']) && is_array($payload['Receipts'])) {
            $receipts = $payload['Receipts'];
        }

        if (empty($receipts)) {
            return response()->json(['error'=>true,'message'=>'No JSON receipt array received.'],400);
        }

        $org     = auth()->user()->organization;
        $license = $request->query('licenseNumber') ?: optional($org)->license_number;
        $userKey = auth()->user()->apiKey;

        if (!$license || !$userKey) {
            return response()->json(['error'=>true,'message'=>'Missing license or API key.'],400);
        }

        $client = new MetrcClient();

        $unique = [];
        foreach ($receipts as $r) {
            $ext = trim((string)($r['ExternalReceiptNumber'] ?? ''));
            if ($ext === '') $unique[] = $r; else $unique[$ext] = $r;
        }
        if (!array_is_list($unique)) $unique = array_values($unique);

        $isDuplicateErr = function ($status, $body): bool {
            if ($status === 409) return true;
            if ($status === 400 || $status === 422) {
                return stripos($body, 'duplicate key') !== false
                    || stripos($body, 'IX_SalesReceipt_FacilityId_ExternalReceiptNumber') !== false
                    || stripos($body, 'already exists') !== false
                    || stripos($body, 'ExternalReceiptNumber') !== false;
            }
            return false;
        };
        $bumpSuffix = function (string $ext): string {
            if (preg_match('/^(.*?)-R(\d{1,3})$/i', $ext, $m)) {
                $stem = $m[1]; $n = (int)$m[2] + 1; return "{$stem}-R{$n}";
            }
            return "{$ext}-R1";
        };

        $results = ['created'=>0,'renumbered'=>[],'errors'=>[],'details'=>[]];

        try {
            foreach ($unique as $rec) {
                $originalExt = trim((string)($rec['ExternalReceiptNumber'] ?? ''));
                $attempts = 0; $maxTries = 10; $last = null;

                while ($attempts < $maxTries) {
                    $resp = $client->createReceipts($license, [$rec], $userKey);
                    $last = $resp;

                    if ($resp->successful()) {
                        $results['created']++;
                        $results['details'][] = [
                            'external' => $rec['ExternalReceiptNumber'] ?? null,
                            'status'   => $resp->status(),
                            'result'   => $resp->json(),
                        ];
                        break;
                    }

                    $status = $resp->status();
                    $body   = (string)$resp->body();
                    if ($isDuplicateErr($status, $body)) {
                        $old = trim((string)($rec['ExternalReceiptNumber'] ?? ''));
                        $new = $bumpSuffix($old ?: ($originalExt ?: 'INV'));
                        $rec['ExternalReceiptNumber'] = $new;
                        $results['renumbered'][$originalExt ?: $old] = $new;
                        $attempts++;
                        continue;
                    }

                    return response($body, $status)->header('Content-Type','application/json');
                }

                if ($attempts >= $maxTries) {
                    $results['errors'][] = [
                        'external' => $originalExt ?: null,
                        'error'    => 'Could not find a free ExternalReceiptNumber after multiple attempts.',
                        'last'     => $last ? $last->body() : null,
                    ];
                }
            }

            return response()->json($results, 200);

        } catch (\Throwable $e) {
            \Log::error('proxyReceipts optimistic-post error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json(['error'=>true,'message'=>'Failed to submit receipts to METRC.','reason'=>$e->getMessage()],500);
        }
    }

    public function metricsaleData(Request $request)
    {
        try {
            $start = $request->query('start_date');
            $end   = $request->query('end_date');
            if (!$start || !$end) return response()->json(['error'=>'Missing date range'],400);

            $org = auth()->user()->organization;
            if (!$org) return response()->json(['error'=>'Missing organization settings'],500);

            $tz = $this->facilityTz();

            $sales = \App\Sale::with(['items.inventory.categoryDetail'])
                ->where('status',1)
                ->where('ismetricsend',0)
                ->whereDate('created_at','>=',$start)
                ->whereDate('created_at','<=',$end)
                ->get();

            $payload = [];

            foreach ($sales as $sale) {
                $custType = strtolower($sale->customer_type ?? 'consumer');

                $lines = [];
                $grossSubtotal = 0.0;

                foreach ($sale->items as $it) {
                    // inventory may be null; bypass category lookups if so
                    $inv   = $it->inventory ?: $this->inventoryQuery()->find($it->product_id);
                    $cat   = $this->safeCategoryName($inv);

                    // NOT reported to METRC: accessories/apparel
                    if (in_array($cat, $this->metrcSkipCats(), true)) {
                        continue;
                    }

                    $lineGross = $this->lineTotalFor($it, $cat ?: null);

                    $qty = (float)$it->quantity; if ($qty <= 0) $qty = 1;

                    $lines[] = [
                        'inv'  => $inv,      // may be null
                        'cat'  => $cat,      // '' when unknown
                        'qty'  => max(0.000001, $qty),
                        'line' => (float)$lineGross,
                    ];
                    $grossSubtotal += (float)$lineGross;
                }

                if (empty($lines) || $grossSubtotal <= 0) continue;

                // Pre-tax = sale.amount - saved taxes; fallback to amount if not saved
                $savedTaxSum = (float)($sale->state_tax  ?? 0) + (float)($sale->county_tax ?? 0) + (float)($sale->city_tax ?? 0);
                $totalDue    = (float)($sale->amount ?? 0);
                $preTaxTotal = round(max(0, $totalDue - $savedTaxSum), 2);
                if ($preTaxTotal <= 0 && $totalDue > 0) $preTaxTotal = $totalDue;

                $transactions = [];
                $allocSoFar = 0.0;
                $lineCount = count($lines);

                foreach ($lines as $idx => $L) {
                    $isLast = ($idx === $lineCount - 1);
                    $share  = $grossSubtotal > 0 ? ($L['line'] / $grossSubtotal) : (1.0 / $lineCount);

                    $linePreTax = $isLast ? round($preTaxTotal - $allocSoFar, 2)
                                           : round($preTaxTotal * $share, 2);
                    $allocSoFar += $linePreTax;

                    $isFlower = $L['inv'] ? $this->isFlower($L['inv']) : false;
                    $uom      = $isFlower ? 'Grams' : 'Each';
                    $qty      = max(0.000001, (float)$L['qty']);
                    $unitPrice= round($linePreTax / $qty, 2);

                    $pkgLabel = trim((string)($L['inv']->Label ?? ''));
                    if ($pkgLabel === '') continue; // no package => not a METRC transaction

                    $transactions[] = [
                        'PackageLabel'  => $pkgLabel,
                        'Quantity'      => $qty,
                        'UnitOfMeasure' => $uom,
                        'Price'         => $unitPrice,
                        'TotalPrice'    => $linePreTax,
                    ];
                }

                if (!empty($transactions)) {
                    $salesDateStr = \Carbon\Carbon::parse($sale->created_at)->timezone($tz)->format('Y-m-d\TH:i:s');

                    $entry = [
                        'SalesDateTime'         => $salesDateStr,
                        'ExternalReceiptNumber' => $sale->invoice_no,
                        'SalesCustomerType'     => ucfirst($custType),
                        'Transactions'          => $transactions,
                    ];

                    if ($custType === 'patient' && !empty($sale->med_number)) {
                        $entry['PatientLicenseNumber'] = $sale->med_number;
                    } elseif ($custType === 'caregiver' && !empty($sale->caregiver_number)) {
                        $entry['CaregiverLicenseNumber'] = $sale->caregiver_number;
                    }

                    $payload[] = $entry;
                }
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'error'     => 'Failed to generate sale data',
                'exception' => $e->getMessage(),
                'line'      => $e->getLine(),
                'file'      => $e->getFile(),
            ], 500);
        }
    }

    /* -------------------- Misc -------------------- */

    public function getHoldOrder($id)
    {
        $order = DB::table('hold_order')->where('id',$id)->where('status',0)->first();
        if (!$order) return response()->json(['error'=>'Not found'],404);
        $order->cart = json_decode($order->cart, true);
        return response()->json($order);
    }

    private function _itemLabel($it): ?string {
        if (!empty($it->package_label)) return (string)$it->package_label;
        if (!empty($it->metrc_package)) {
            $raw = is_string($it->metrc_package) ? @json_decode($it->metrc_package, true) : (array)$it->metrc_package;
            if (is_array($raw) && !empty($raw['Label'])) return (string)$raw['Label'];
        }
        return null;
    }

    private function _resolveInvByLabel(?string $label) {
        if (!$label) return null;
        try { return $this->inventoryQuery()->where('Label', $label)->latest('id')->first(); }
        catch (\Throwable $e) { return null; }
    }

    private function compileReceiptData(\App\Sale $sale): array
    {
        $sale->loadMissing(['items.inventory','customer']);

        $toC   = fn($v) => (int) round((float)$v * 100);
        $fromC = fn($c) => $c / 100;

        $rowGrossC = function($it) use($toC){
            foreach (['line_total','line_total_gross','total_line','price_total','amount_line'] as $k) {
                if (isset($it->$k) && is_numeric($it->$k) && (float)$it->$k > 0) return $toC($it->$k);
            }
            $price=(float)($it->price??0); $qty=(float)($it->quantity??0);
            $flag=!empty($it->price_is_line_total)||!empty($it->is_line_total);
            $frac=abs($qty-floor($qty))>0.0001;
            if($flag||$frac) return $toC($price);
            $unit = (isset($it->unit_price) && is_numeric($it->unit_price)) ? (float)$it->unit_price : $price;
            return $toC($unit * max(0,$qty));
        };
        $rowNetC = function(int $grossC, $it) use($toC){
            $type=null; $val=0.0;
            foreach(['inline_discount_type','line_discount_type','discount_type'] as $k){ if(!empty($it->$k)){ $type=strtolower((string)$it->$k); break; } }
            foreach(['inline_discount_value','line_discount_value','discount_value'] as $k){ if(isset($it->$k) && is_numeric($it->$k)){ $val=(float)$it->$k; break; } }
            if ($type==='percent') return max(0, $grossC - (int)round($grossC*$val/100));
            if ($type==='amount' || $type==='fixed') return max(0, $grossC - $toC($val));
            return $grossC;
        };

        // flower tier helpers (only used when inventory exists & we can read tiers)
        $rawTiers      = optional(auth()->user()->organization)->discount_tiers ?? setting_by_key('discount_tiers');
        $discountTiers = is_string($rawTiers) ? (json_decode($rawTiers, true) ?: []) : ($rawTiers ?: []);
        $lower = fn($s)=> strtolower(trim((string)$s));

        $tierLookup = [];
        foreach ((array)$discountTiers as $key => $t) {
            if (!is_array($t)) continue;
            $pricing = $t['pricing'] ?? [];
            if ($pricing && !is_array(reset($pricing))) $pricing = array_values($pricing);
            $pricing = array_values(array_filter(array_map(function($p){
                if(!$p) return null;
                foreach (['price','amount','value'] as $k)
                    if (isset($p[$k]) && is_numeric($p[$k])) return array_merge($p,['price'=>(float)$p[$k]]);
                return null;
            }, (array)$pricing)));
            if (!$pricing) continue;
            $obj = array_merge($t,['pricing'=>$pricing]);
            $tierLookup[$lower($key)] = $obj;
            if (!empty($t['name'])) $tierLookup[$lower($t['name'])] = $obj;
            if (isset($t['id']))    $tierLookup[$lower($t['id'])]    = $obj;
        }

        $nominalFrom = function(array $p): ?float {
            $OZ2G = 28.349523125;
            foreach (['min_quantity','min_qty','grams','quantity','qty','size_g','size_grams'] as $f)
                if (isset($p[$f]) && is_numeric($p[$f]) && (float)$p[$f] > 0) return (float)$p[$f];
            foreach (['size_oz','oz','ounces','size_ounces'] as $f)
                if (isset($p[$f]) && is_numeric($p[$f]) && (float)$p[$f] > 0) return (float)$p[$f] * $OZ2G;
            $n = strtolower((string)($p['name'] ?? ''));
            if ($n) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*g\b/', $n, $m)) return (float)$m[1];
                if (preg_match('/(\d+(?:\.\d+)?)\s*(?:oz|ounces?)\b/', $n, $m)) return (float)$m[1] * $OZ2G;
                if (preg_match('/\b(1\/8|1\/4|1\/2|2\/1)\b/', $n, $m)) return ['1/8'=>3.5,'1/4'=>7,'1/2'=>14,'2/1'=>56][$m[1]] ?? null;
                if (str_contains($n,'eighth'))  return 3.5;
                if (str_contains($n,'quarter')) return 7.0;
                if (preg_match('/\bhalf\b/', $n))   return 14.0;
                if (preg_match('/\b(ounce| oz)\b/', $n)) return 28.0;
            }
            return null;
        };

        $bandsFor = function($inv) use($tierLookup,$lower,$nominalFrom){
            if (!$inv) return [];
            $sel = null; foreach (['selected_discount_tier','selected_discount_tier_name','selected_discount_tier_key'] as $k){ if(!empty($inv->$k)){ $sel=$lower($inv->$k); break; } }
            if (!$sel) return [];
            $tier = $tierLookup[$sel] ?? null;
            if (!$tier || empty($tier['pricing']) || !is_array($tier['pricing'])) return [];
            $byNom = [];
            foreach ($tier['pricing'] as $p) {
                if (!is_array($p) || !isset($p['price'])) continue;
                $nom = $nominalFrom($p); $flat=(float)$p['price'];
                if ($nom && $flat>0) { $k=(string)$nom; if(!isset($byNom[$k]) || $flat<$byNom[$k]['flat']) $byNom[$k]=['nominal'=>$nom,'flat'=>$flat,'rate'=>$flat/$nom]; }
            }
            $bands = array_values($byNom); usort($bands, fn($a,$b)=> $a['nominal'] <=> $b['nominal']); return $bands;
        };

        $priceFlower = function(array $bands, float $grams){
            $GRACE=0.20; $EPS=1e-9; $q=max(0.0,(float)$grams); if(!$bands || $q<=0) return ['total'=>0.0,'rate'=>0.0,'billable'=>0.0];
            $anchors=array_column($bands,'nominal'); $rates=array_column($bands,'rate');
            $idx=0; for($i=0;$i<count($anchors)-1;$i++){ if($q < $anchors[$i+1]-$EPS){$idx=$i;break;} $idx=$i+1; }
            $anchor=(float)$anchors[$idx]; $rate=(float)$rates[$idx]; $bill=$q;
            if ($q>$anchor+$EPS && $q<=$anchor+$GRACE+$EPS) $bill=$anchor;
            return ['total'=>$rate*$bill,'rate'=>$rate,'billable'=>$bill];
        };

        $rows = [];
        $subtotalGrossC=0; $afterLineC=0;

        foreach ($sale->items as $it) {
            $label = $this->_itemLabel($it);
            $inv   = $it->inventory ?: $this->inventoryQuery()->find($it->product_id) ?: $this->_resolveInvByLabel($label);
            $qty   = (float)($it->quantity ?? 0);
            $isFl  = $this->isFlower($inv);

            $savedGrossC = null; $savedUnit = null;
            foreach (['line_total','line_total_gross','total_line','price_total','amount_line'] as $k) {
                if (isset($it->$k) && is_numeric($it->$k) && (float)$it->$k > 0) { $savedGrossC = $toC($it->$k); break; }
            }
            if (isset($it->unit_price) && is_numeric($it->unit_price)) $savedUnit = (float)$it->unit_price;

            if ($savedGrossC !== null) {
                $grossC = (int)$savedGrossC;
                $netC   = $rowNetC($grossC, $it);
                $unit   = $savedUnit !== null ? $savedUnit : ($qty>0 ? $fromC($netC)/$qty : $fromC($netC));
            }
            elseif ($isFl) {
                $bands = $bandsFor($inv);
                if ($bands) {
                    $pf    = $priceFlower($bands, $qty);
                    $grossC= $toC($pf['total']);
                    $netC  = $rowNetC($grossC, $it);
                    $unit  = $pf['rate'];
                } else {
                    $grossC= $toC((float)($it->price ?? 0));
                    $netC  = $rowNetC($grossC, $it);
                    $unit  = $qty>0 ? $fromC($netC)/$qty : $fromC($netC);
                }
            } else {
                $grossC = $rowGrossC($it);
                $netC   = $rowNetC($grossC, $it);
                $unit  = isset($it->unit_price) && is_numeric($it->unit_price) ? (float)$it->unit_price : ($qty>0 ? $fromC($netC)/$qty : $fromC($netC));
            }

            $subtotalGrossC += $grossC;
            $afterLineC     += $netC;

            $pkgLabel = $inv->Label ?? $label ?? '';
            $productName = $inv->name ?? optional(\App\Product::find($it->product_id))->name ?? '—';

            $rows[] = [
                'item_id'     => (int)$it->id,      // <--- ensure item id included for editing Pkg
                'name'        => $productName,
                'pkg'         => $pkgLabel,
                'qty'         => $qty,
                'gross'       => $fromC($grossC),
                'net'         => $fromC($netC),
                'unit'        => $unit,
                'isFlower'    => $isFl,
                'hadLineDisc' => ($netC < $grossC),
            ];
        }

        $inlineDiscC = max(0, $subtotalGrossC - $afterLineC);
        $orderDiscC  = $toC((float)($sale->order_discount_value ?? 0));
        if ($orderDiscC === 0 && (float)$sale->discount > 0) $orderDiscC = $toC((float)$sale->discount);

        $stateSaved  = is_numeric($sale->state_tax)  ? round((float)$sale->state_tax,  2) : null;
        $countySaved = is_numeric($sale->county_tax) ? round((float)$sale->county_tax, 2) : null;
        $citySaved   = is_numeric($sale->city_tax)   ? round((float)$sale->city_tax,   2) : null;

        $ov = [
            'pre_tax'    => $sale->getAttribute('override_pre_tax'),
            'tax_state'  => $sale->getAttribute('override_tax_state'),
            'tax_county' => $sale->getAttribute('override_tax_county'),
            'tax_city'   => $sale->getAttribute('override_tax_city'),
            'total_due'  => $sale->getAttribute('override_total_due'),
            'paid'       => $sale->getAttribute('override_paid'),
            'change'     => $sale->getAttribute('override_change'),
        ];
        foreach ($ov as $k=>$v) { $ov[$k] = is_numeric($v) ? round((float)$v,2) : null; }

        $state = $ov['tax_state']  ?? ($stateSaved  ?? 0.00);
        $county= $ov['tax_county'] ?? ($countySaved ?? 0.00);
        $city  = $ov['tax_city']   ?? ($citySaved   ?? 0.00);
        $tax   = round($state + $county + $city, 2);

        $due   = $ov['total_due'];
        if ($due === null && is_numeric($sale->amount)) {
            $due = round((float)$sale->amount, 2);
        }

        if ($ov['pre_tax'] !== null) {
            $pre = $ov['pre_tax'];
            if ($due === null) $due = round($pre + $tax, 2);
        } elseif ($due !== null) {
            $pre = round(max(0, $due - $tax), 2);
        } else {
            $pre = round($fromC($afterLineC), 2);
            $due = round($pre + $tax, 2);
        }

        $paid   = $ov['paid']   ?? (is_numeric($sale->total_given) ? round((float)$sale->total_given, 2) : $due);
        $change = $ov['change'] ?? (is_numeric($sale->change)      ? round((float)$sale->change, 2)      : max(0, round($paid - $due, 2)));

        return [
            'rows'                  => $rows,
            'subtotal_pre_discount' => round($fromC($subtotalGrossC), 2),
            'discount_inline'       => round($fromC($inlineDiscC), 2),
            'discount_order'        => round($fromC($orderDiscC), 2),

            'pre_tax'               => $pre,
            'tax'                   => $tax,
            'tax_state'             => $state,
            'tax_county'            => $county,
            'tax_city'              => $city,

            'total_due'             => $due,
            'paid'                  => $paid,
            'change'                => $change,

            'customer_type'         => $sale->customer_type ?? 'consumer',
        ];
    }

    public function receipt($id)
    {
        $sale = Sale::with(['items.inventory.categoryDetail','customer'])->findOrFail($id);
        $compiled = $this->compileReceiptData($sale);
        return view('backend.sales.receipt', compact('sale','compiled'));
    }

    public function receiptNumbers(\App\Sale $sale)
    {
        $compiled = $this->compileReceiptData($sale);
        return response()->json(array_merge(['sale_id' => $sale->id], Arr::except($compiled, ['rows'])));
    }

    public function saveReceiptOverrides(Request $request, \App\Sale $sale)
    {
        $base = $this->compileReceiptData($sale);

        $rawOv = $request->input('overrides', []);
        $cleanNum = function ($v) {
            if ($v === null) return null;
            if (is_string($v)) $v = preg_replace('/[^\d\.\-]/', '', trim($v));
            return is_numeric($v) ? round((float)$v, 2) : null;
        };

        $uiMap = [
            'subtotal'   => 'subtotal_pre_discount',
            'disc-line'  => 'discount_inline',
            'disc-order' => 'discount_order',
            'pretax'     => 'pre_tax',
            'tax'        => 'tax',
            'tax-state'  => 'tax_state',
            'tax-county' => 'tax_county',
            'tax-city'   => 'tax_city',
            'due'        => 'total_due',
            'paid'       => 'paid',
            'change'     => 'change',
        ];

        $payload = [];
        foreach (['subtotal_pre_discount','discount_inline','discount_order','pre_tax','tax_state','tax_county','tax_city','tax','total_due','paid','change','reason','delta'] as $k) {
            $v = $request->input($k);
            if ($k === 'reason') { if ($v !== null) $payload[$k] = (string)$v; }
            else if ($v !== null) $payload[$k] = $cleanNum($v);
        }
        if (is_array($rawOv) && !empty($rawOv)) {
            foreach ($uiMap as $ui => $dst) {
                if (array_key_exists($ui, $rawOv)) $payload[$dst] = $cleanNum($rawOv[$ui]);
            }
            if (isset($rawOv['discounts']) && !isset($payload['discount_inline']) && !isset($payload['discount_order'])) {
                $dt = $cleanNum($rawOv['discounts']); if ($dt !== null) $payload['discount_order'] = $dt;
            }
        }

        if (isset($payload['delta']) && $payload['delta'] !== null && (!isset($payload['pre_tax']) || $payload['pre_tax'] === null)) {
            $payload['pre_tax'] = round($base['pre_tax'] + $payload['delta'], 2);
        }

        $haveBuckets = (isset($payload['tax_state']) && $payload['tax_state'] !== null)
                    || (isset($payload['tax_county']) && $payload['tax_county'] !== null)
                    || (isset($payload['tax_city']) && $payload['tax_city'] !== null);
        $needTaxFromPre = false;

        $prevTax = (float)$base['tax'];
        $prevPre = (float)$base['pre_tax'];
        $sShare = $prevTax > 0 ? ($base['tax_state']  / $prevTax) : 0.0;
        $cShare = $prevTax > 0 ? ($base['tax_county'] / $prevTax) : 0.0;
        $yShare = $prevTax > 0 ? max(0.0, 1.0 - $sShare - $cShare) : 0.0;

        if (!$haveBuckets) {
            if (isset($payload['tax']) && $payload['tax'] !== null) {
                $t = $payload['tax'];
                $payload['tax_state']  = round($t * $sShare, 2);
                $payload['tax_county'] = round($t * $cShare, 2);
                $payload['tax_city']   = round($t - $payload['tax_state'] - $payload['tax_county'], 2);
            } else {
                $needTaxFromPre = true;
            }
        }

        if ($needTaxFromPre && isset($payload['pre_tax']) && $payload['pre_tax'] !== null) {
            $eff = ($prevPre > 0) ? ($prevTax / $prevPre) : 0.0;
            $t   = round($payload['pre_tax'] * $eff, 2);
            $payload['tax_state']  = round($t * $sShare, 2);
            $payload['tax_county'] = round($t * $cShare, 2);
            $payload['tax_city']   = round($t - $payload['tax_state'] - $payload['tax_county'], 2);
        }

        if (!isset($payload['total_due']) || $payload['total_due'] === null) {
            $pre = $payload['pre_tax'] ?? $base['pre_tax'];
            if (isset($payload['tax_state']) || isset($payload['tax_county']) || isset($payload['tax_city'])) {
                $taxSum = (float)($payload['tax_state'] ?? 0) + (float)($payload['tax_county'] ?? 0) + (float)($payload['tax_city'] ?? 0);
                $payload['total_due'] = round($pre + $taxSum, 2);
            } elseif (isset($payload['tax']) && $payload['tax'] !== null) {
                $payload['total_due'] = round($pre + $payload['tax'], 2);
            } else {
                $payload['total_due'] = round($pre + $base['tax'], 2);
            }
        }

        $updates = [];
        $map = [
            'override_subtotal_pre_discount' => 'subtotal_pre_discount',
            'override_discount_inline'       => 'discount_inline',
            'override_discount_order'        => 'discount_order',
            'override_pre_tax'               => 'pre_tax',
            'override_tax_state'             => 'tax_state',
            'override_tax_county'            => 'tax_county',
            'override_tax_city'              => 'tax_city',
            'override_total_due'             => 'total_due',
            'override_paid'                  => 'paid',
            'override_change'                => 'change',
            'override_reason'                => 'reason',
        ];

        foreach ($map as $col => $key) {
            if (\Schema::hasColumn('sales', $col) && isset($payload[$key]) && $payload[$key] !== null) {
                $updates[$col] = $payload[$key];
            }
        }

        if (isset($payload['tax_state']))  $updates['state_tax']   = $payload['tax_state'];
        if (isset($payload['tax_county'])) $updates['county_tax']  = $payload['tax_county'];
        if (isset($payload['tax_city']))   $updates['city_tax']    = $payload['tax_city'];
        if (isset($payload['total_due']))  $updates['amount']      = $payload['total_due'];
        if (isset($payload['paid']))       $updates['total_given'] = $payload['paid'];
        if (isset($payload['change']))     $updates['change']      = $payload['change'];

        if (isset($payload['pre_tax']) && !isset($updates['amount'])) {
            $t = 0.00;
            if (isset($updates['state_tax']) || isset($updates['county_tax']) || isset($updates['city_tax'])) {
                $t = (float)($updates['state_tax'] ?? $sale->state_tax)
                   + (float)($updates['county_tax'] ?? $sale->county_tax)
                   + (float)($updates['city_tax']   ?? $sale->city_tax);
            } else {
                $t = $base['tax'];
            }
            $updates['amount'] = round($payload['pre_tax'] + $t, 2);
        }

        if (empty($updates)) {
            return response()->json(['ok'=>false,'message'=>'No changes to save.'], 422);
        }

        DB::table('sales')->where('id', $sale->id)->update($updates + ['updated_at'=>now()]);

        $fresh = $this->compileReceiptData($sale->fresh());
        return response()->json(['ok'=>true,'saved'=>array_keys($updates),'numbers'=>Arr::except($fresh, ['rows'])]);
    }

    /*** NEW: Save edited per-line Pkg labels (metrc_package->Label OR package_label if present) ***/
    public function saveReceiptPkgLabels(Request $request, \App\Sale $sale)
    {
        $data = $request->validate([
            'pkg_updates' => ['required','array','min:1'],
            'pkg_updates.*.sale_item_id' => ['required','integer'],
            'pkg_updates.*.label'        => ['nullable','string','max:64'],
        ]);

        $hasPkgCol  = \Schema::hasColumn('sale_items','package_label');
        $hasJsonCol = \Schema::hasColumn('sale_items','metrc_package');

        $updated = 0;

        foreach ($data['pkg_updates'] as $row) {
            $sid   = (int)$row['sale_item_id'];
            $label = trim((string)$row['label']);

            $item = \DB::table('sale_items')->where('id',$sid)->where('sale_id',$sale->id)->first();
            if (!$item) continue;

            if ($hasPkgCol) {
                \DB::table('sale_items')->where('id',$sid)->update([
                    'package_label' => $label !== '' ? $label : null,
                    'updated_at'    => now(),
                ]);
                $updated++;
            } elseif ($hasJsonCol) {
                $j = [];
                if (!empty($item->metrc_package)) {
                    $tmp = json_decode($item->metrc_package, true);
                    if (is_array($tmp)) $j = $tmp;
                }
                if ($label !== '') $j['Label'] = $label; else unset($j['Label']);

                \DB::table('sale_items')->where('id',$sid)->update([
                    'metrc_package' => !empty($j) ? json_encode($j) : null,
                    'updated_at'    => now(),
                ]);
                $updated++;
            }
        }

        return response()->json(['ok'=>true, 'updated'=>$updated]);
    }
}
