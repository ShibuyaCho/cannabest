<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\Models\Category;
use App\Models\MetrcPackage;
use App\Models\MetrcLabResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('printLabel');
    }

    /* -------------------------------------------------
     | Name normalization helpers (server-side safety)
     | ------------------------------------------------- */
    protected function normalizeBaseName(?string $s): string
    {
        $s = (string) $s;
        // strip trailing <img ...> and :gls:
        $s = preg_replace('/\s*<img[^>]+>\s*$/i', '', $s);
        $s = preg_replace('/\s*:gls:\s*$/i', '', $s);
        return trim($s);
    }

    protected function hasLeaf(string $name): bool
    {
        return (bool) preg_match('/\s*:gls:\s*$/i', $name);
    }

    protected function ensureLeafState(string $name, bool $wantLeaf): string
    {
        $base = $this->normalizeBaseName($name);
        return $wantLeaf ? rtrim($base).' :gls:' : $base;
    }

    /* -------------------------------------------------
     | Reservation helpers (no DB, no migrations)
     | ------------------------------------------------- */
    protected function resvKey(int $orgId, int $invId): string
    {
        return "inv:resv:{$orgId}:{$invId}";
    }
    protected function sessKey(int $orgId, string $sid): string
    {
        return "inv:resv:sess:{$orgId}:{$sid}";
    }
    protected function sumActive(array $map): float
    {
        $now = Carbon::now()->timestamp;
        $sum = 0.0;
        foreach ($map as $row) {
            if (!is_array($row)) continue;
            $qty = (float)($row['qty'] ?? 0);
            $exp = (int)($row['expires_at'] ?? 0);
            if ($qty > 0 && (!$exp || $exp >= $now)) $sum += $qty;
        }
        return $sum;
    }

    /* -------------------------------------------------
     | Tier → price helper
     | ------------------------------------------------- */
    private function priceFromTierName(?string $tierName, $tiers): ?float
    {
        if (!$tierName) return null;

        // Prefer org-configured tier pricing
        $tier = collect($tiers ?? [])->firstWhere('name', $tierName);
        $p = data_get($tier, 'pricing.0.price');
        if (is_numeric($p)) return (float) $p;

        // Fallback: extract a number from the tier name like "$4g" -> 4
        if (preg_match('/\d+(\.\d+)?/', $tierName, $m)) {
            return (float) $m[0];
        }
        return null;
    }

    /* -------------------------------------------------
     | Helpers: METRC attach for a small set of inventories
     | ------------------------------------------------- */
    protected function attachMetrcForPage($paginatorOrCollection, int $orgId): void
    {
        // Normalize to a Collection of Inventory models
        if ($paginatorOrCollection instanceof AbstractPaginator) {
            $rows = $paginatorOrCollection->getCollection();
        } elseif ($paginatorOrCollection instanceof Collection) {
            $rows = $paginatorOrCollection;
        } else {
            $rows = collect($paginatorOrCollection);
        }

        $labels = $rows->pluck('Label')
            ->filter()
            ->map(fn($l) => Str::upper(trim($l)))
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            foreach ($rows as $inv) {
                $map  = Cache::get($this->resvKey($orgId, $inv->id), []);
                $held = $this->sumActive($map);
                $inv->available_qty = max(0, (float)$inv->storeQty - $held);
            }
            return;
        }

        $packages = MetrcPackage::whereIn('Label', $labels->all())
            ->get()
            ->keyBy(fn($p) => Str::upper(trim($p->Label)));

        $pkgIds = $packages->pluck('Id')->filter()->unique()->values();
        $labs   = $pkgIds->isNotEmpty()
            ? MetrcLabResult::whereIn('PackageId', $pkgIds->all())->get()->groupBy('PackageId')
            : collect();

        foreach ($rows as $inv) {
            $labelKey = Str::upper(trim($inv->Label ?? ''));
            if ($labelKey && $packages->has($labelKey)) {
                $pkg      = $packages[$labelKey];
                $fullLabs = $labs->get($pkg->Id, collect());

                $inv->setRelation('metrc_package', $pkg);
                $inv->setRelation('metrc_full_labs', $fullLabs);

                $thc = null; $cbd = null;
                if ($fullLabs->isNotEmpty()) {
                    $thcRow = $fullLabs->first(function ($row) {
                        return isset($row->TestTypeName) && preg_match('/total\s*thc\s*%/i', $row->TestTypeName);
                    });
                    $cbdRow = $fullLabs->first(function ($row) {
                        return isset($row->TestTypeName) && preg_match('/total\s*cbd\s*%/i', $row->TestTypeName);
                    });
                    if ($thcRow && is_numeric($thcRow->TestResultLevel)) $thc = round($thcRow->TestResultLevel / 10, 2);
                    if ($cbdRow && is_numeric($cbdRow->TestResultLevel)) $cbd = round($cbdRow->TestResultLevel / 10, 2);
                }

                $inv->setRelation('metrc_summary', collect(['thc' => $thc, 'cbd' => $cbd]));
            }

            $map  = Cache::get($this->resvKey($orgId, $inv->id), []);
            $held = $this->sumActive($map);
            $inv->available_qty = max(0, (float)$inv->storeQty - $held);
        }
    }

    /* -------------------------------------------------
     | GRID: index (injects METRC + availability)
     | ------------------------------------------------- */
    public function index()
    {
        $orgId = auth()->user()->organization_id;

        $inventories = Inventory::query()
            ->with('categoryDetail')
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->paginate(36);

        $this->attachMetrcForPage($inventories, $orgId);

        return view('sales.index', [
            'inventories' => $inventories,
            'categories'  => Category::orderBy('name')->get(),
        ]);
    }

    /* -------------------------------------------------
     | AJAX search (returns availability + basic fields)
     | ------------------------------------------------- */
    public function search(Request $request)
    {
        $q     = trim((string)$request->get('q', ''));
        $orgId = auth()->user()->organization_id;

        $inventories = Inventory::query()
            ->with('categoryDetail')
            ->where('organization_id', $orgId)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($k) use ($q) {
                    $k->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%")
                      ->orWhere('Label', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        $data = $inventories->getCollection()->map(function ($inv) use ($orgId) {
            $map  = Cache::get($this->resvKey($orgId, $inv->id), []);
            $held = $this->sumActive($map);
            $available = max(0, (float)$inv->storeQty - $held);

            return [
                'id'            => $inv->id,
                'name'          => $inv->name,
                'sku'           => $inv->sku,
                'Label'         => $inv->Label,
                'storeQty'      => (float)$inv->storeQty,
                'available_qty' => (float)$available,
                'category_name' => optional($inv->categoryDetail)->name,
                'metrc_package' => $inv->metrc_package ? [
                    'Id'    => $inv->metrc_package->Id ?? null,
                    'Label' => $inv->metrc_package->Label ?? null,
                ] : null,
                'has_image'     => file_exists(public_path("uploads/inventories/{$inv->id}.jpg")),
            ];
        });

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $inventories->currentPage(),
                'last_page'    => $inventories->lastPage(),
            ],
        ]);
    }

    /* -------------------------------------------------
     | Lightweight availability poll (ids[] => qty)
     | ------------------------------------------------- */
    public function availability(Request $request)
    {
        $orgId = auth()->user()->organization_id;
        $ids   = array_values(array_filter(array_map('intval', (array)$request->input('ids', []))));
        if (!$ids) return response()->json(['data' => []]);

        $rows = Inventory::where('organization_id', $orgId)
            ->whereIn('id', $ids)
            ->get(['id', 'storeQty']);

        $out = [];
        foreach ($rows as $inv) {
            $map  = Cache::get($this->resvKey($orgId, $inv->id), []);
            $held = $this->sumActive($map);
            $out[$inv->id] = max(0, (float)$inv->storeQty - $held);
        }
        return response()->json(['data' => $out]);
    }
public function updateSubtype(\Illuminate\Http\Request $request, \App\Inventory $inventory)
{
    $user = auth()->user();
    // Only allow updates within the same organization
    if ((int)$inventory->organization_id !== (int)$user->organization_id) {
        abort(403, 'Not allowed.');
    }

    $data = $request->validate([
        'subtype' => ['nullable','string','max:64'],
    ]);

    // Normalize & save
    $raw   = $data['subtype'] ?? '';
    $norm  = preg_replace('/\s+/', ' ', trim($raw ?? ''));
    $inventory->status_subtype = $norm !== '' ? $norm : null;
    $inventory->save();

    return response()->json([
        'ok'      => true,
        'subtype' => $inventory->status_subtype,
        'id'      => $inventory->id,
    ]);
}

public function listSubtypes(\Illuminate\Http\Request $request)
{
    $orgId = auth()->user()->organization_id;

    // Distinct, non-empty subtypes for this organization
    $subtypes = \App\Inventory::query()
        ->where('organization_id', $orgId)
        ->whereNotNull('status_subtype')
        ->where('status_subtype', '!=', '')
        ->distinct()
        ->orderBy('status_subtype')
        ->pluck('status_subtype')
        ->values();

    return response()->json(['subtypes' => $subtypes]);
}

    /* -------------------------------------------------
     | Reserve / Release / Release-all
     | ------------------------------------------------- */
    public function reserve(Request $request, Inventory $inventory)
    {
        $orgId = auth()->user()->organization_id;
        abort_unless($inventory->organization_id == $orgId, 403);

        $sid  = $request->session()->getId();
        $want = (float)$request->input('quantity', 1);
        if ($want <= 0) {
            return response()->json(['success' => false, 'message' => 'Quantity must be > 0'], 422);
        }

        $lock = Cache::lock("lock:inv:resv:{$orgId}:{$inventory->id}", 3);
        try {
            $lock->block(3);

            $map = Cache::get($this->resvKey($orgId, $inventory->id), []);
            $now = Carbon::now()->timestamp;
            foreach ($map as $k => $row) {
                if (isset($row['expires_at']) && $row['expires_at'] < $now) unset($map[$k]);
            }

            $heldTotal = $this->sumActive($map);
            $available = (float)$inventory->storeQty - $heldTotal;

            $grant = min($want, max(0, $available));
            if ($grant <= 0) {
                return response()->json(['success' => false, 'message' => 'Not enough available.'], 409);
            }

            $mine = (float)($map[$sid]['qty'] ?? 0);
            $map[$sid] = [
                'qty'        => $mine + $grant,
                'expires_at' => $now + 60 * 30,
            ];
            Cache::put($this->resvKey($orgId, $inventory->id), $map, 3600);

            $sessKey = $this->sessKey($orgId, $sid);
            $sessMap = Cache::get($sessKey, []);
            $sessMap[$inventory->id] = ($sessMap[$inventory->id] ?? 0) + $grant;
            Cache::put($sessKey, $sessMap, 3600);

            $newAvail = max(0, (float)$inventory->storeQty - $this->sumActive($map));
            return response()->json([
                'success'   => true,
                'granted'   => $grant,
                'available' => $newAvail,
                'reserved'  => (float)$map[$sid]['qty'],
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function release(Request $request, Inventory $inventory)
    {
        $orgId = auth()->user()->organization_id;
        abort_unless($inventory->organization_id == $orgId, 403);

        $sid  = $request->session()->getId();
        $give = (float)$request->input('quantity', 1);
        if ($give <= 0) {
            $map = Cache::get($this->resvKey($orgId, $inventory->id), []);
            $newAvail = max(0, (float)$inventory->storeQty - $this->sumActive($map));
            return response()->json(['success' => true, 'available' => $newAvail]);
        }

        $lock = Cache::lock("lock:inv:resv:{$orgId}:{$inventory->id}", 3);
        try {
            $lock->block(3);

            $map  = Cache::get($this->resvKey($orgId, $inventory->id), []);
            $mine = (float)($map[$sid]['qty'] ?? 0);
            $newMine = max(0.0, $mine - $give);
            if ($newMine == 0.0) unset($map[$sid]); else $map[$sid]['qty'] = $newMine;
            Cache::put($this->resvKey($orgId, $inventory->id), $map, 3600);

            $sessKey = $this->sessKey($orgId, $sid);
            $sessMap = Cache::get($sessKey, []);
            if (isset($sessMap[$inventory->id])) {
                $sessMap[$inventory->id] = max(0.0, (float)$sessMap[$inventory->id] - $give);
                if ($sessMap[$inventory->id] == 0.0) unset($sessMap[$inventory->id]);
                Cache::put($sessKey, $sessMap, 3600);
            }

            $newAvail = max(0, (float)$inventory->storeQty - $this->sumActive($map));
            return response()->json([
                'success'   => true,
                'available' => $newAvail,
                'reserved'  => (float)($map[$sid]['qty'] ?? 0),
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function releaseAll(Request $request)
    {
        $orgId = auth()->user()->organization_id;
        $sid   = $request->session()->getId();

        $sessKey = $this->sessKey($orgId, $sid);
        $sessMap = Cache::get($sessKey, []);

        foreach ($sessMap as $invId => $qty) {
            $lock = Cache::lock("lock:inv:resv:{$orgId}:{$invId}", 3);
            try {
                $lock->block(3);
                $map = Cache::get($this->resvKey($orgId, $invId), []);
                $mine = (float)($map[$sid]['qty'] ?? 0);
                $left = max(0.0, $mine - (float)$qty);
                if ($left == 0.0) unset($map[$sid]); else $map[$sid]['qty'] = $left;
                Cache::put($this->resvKey($orgId, $invId), $map, 3600);
            } finally {
                optional($lock)->release();
            }
        }

        Cache::forget($sessKey);
        return response()->json(['success' => true]);
    }

    /* -------------------------------------------------
     | Misc
     | ------------------------------------------------- */
    public function syncMetrc(Request $request)
    {
        $orgId = auth()->user()->organization_id;
        Artisan::queue('metrc:sync-inventory', ['org' => $orgId]);

        return response()->json([
            'status'  => 'queued',
            'message' => 'METRC sync queued.',
        ]);
    }

    public function printLabel(Inventory $inventory)
    {
        $orgId = auth()->user()->organization_id ?? null;
        if ($orgId) {
            abort_unless($inventory->organization_id == $orgId, 403);
        }
        return view('labels.print', compact('inventory'));
    }

    /* -------------------------------------------------
     | Edit / Update
     | ------------------------------------------------- */
    public function edit($id)
    {
        $orgId     = auth()->user()->organization_id;
        $inventory = Inventory::where('organization_id', $orgId)->findOrFail($id);

        // Save where to return after save
        session()->put('inventory_list_url', url()->previous());

        $categories = Category::orderBy('name')->get();
        return view('backend.inventories.edit', compact('inventory', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $orgId     = auth()->user()->organization_id;
        $inventory = Inventory::where('organization_id', $orgId)->findOrFail($id);

        // Tiers for validation
        $rawTiers  = optional(auth()->user()->organization)->discount_tiers;
        $tiers     = is_string($rawTiers) ? json_decode($rawTiers, true) : $rawTiers;
        $tierNames = collect($tiers ?? [])->pluck('name')->all();

        // Validate
        $validated = $request->validate([
            'name'                   => ['required','string','max:255'],
            'sku'                    => [
                'nullable','string','max:255',
                Rule::unique('inventories','sku')
                    ->where(fn($q) => $q->where('organization_id', $orgId))
                    ->ignore($inventory->id),
            ],
            'Label'                  => ['nullable','string','max:255'],
            'original_price'         => ['nullable','numeric'],
            'original_cost'          => ['nullable','numeric'],
            'weight'                 => ['nullable','numeric','min:0'],
            'category_id'            => ['required','exists:categories,id'],
            'selected_discount_tier' => ['nullable','string', Rule::in($tierNames)],
            'add_leaf'               => ['nullable','in:0,1'],
            'apply_name_to_group'    => ['nullable','in:0,1'],
            'return_url'             => ['nullable','string'],
            'file'                   => ['nullable','file','mimes:jpg,jpeg,png','max:8192'],
        ]);

        // Name + leaf normalization
        $wantLeaf = $request->boolean('add_leaf');
        $newName  = $this->ensureLeafState($validated['name'], $wantLeaf);

        $oldBase  = $this->normalizeBaseName($inventory->name);
        $newBase  = $this->normalizeBaseName($newName);
        $doProp   = $request->boolean('apply_name_to_group') && ($oldBase !== $newBase);

        // Flower category detection
        $category = Category::findOrFail($validated['category_id']);
        $isFlower = Str::lower($category->slug ?? $category->name) === 'flower'
                 || Str::contains(Str::lower($category->name ?? ''), 'flower');

        // Pricing rules
        if ($isFlower) {
            $price = $this->priceFromTierName($validated['selected_discount_tier'] ?? null, $tiers)
                  ?? (array_key_exists('original_price', $validated) ? $validated['original_price'] : $inventory->original_price);
            if ($price === null) {
                return back()->withErrors(['original_price' => 'Price is required for flower (or choose a valid tier).'])
                             ->withInput();
            }
            $inventory->original_price = (float) $price;
        } else {
            if (!array_key_exists('original_price', $validated) || $validated['original_price'] === null) {
                return back()->withErrors(['original_price' => 'Price is required for non-flower items.'])
                             ->withInput();
            }
            $inventory->original_price = (float) $validated['original_price'];
        }

        // Weight defaults (flower => 1, else keep/0)
        $newWeight = array_key_exists('weight', $validated)
            ? (float)$validated['weight']
            : $inventory->weight;

        if ($isFlower && ($newWeight === null || $newWeight <= 0)) {
            $newWeight = 1.0;
        }
        if ($newWeight === null) {
            $newWeight = 0.0;
        }

        // Assign other fields
        $inventory->name                   = $newName;
        $inventory->sku                    = $validated['sku']   ?? $inventory->sku;
        $inventory->Label                  = $validated['Label'] ?? $inventory->Label;
        $inventory->original_cost          = array_key_exists('original_cost', $validated) ? $validated['original_cost'] : $inventory->original_cost;
        $inventory->weight                 = $newWeight;
        $inventory->category_id            = $validated['category_id'];
        $inventory->selected_discount_tier = $validated['selected_discount_tier'] ?? null;

        // Optional image save
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            try {
                $destDir = public_path('uploads/inventories');
                if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
                $dest = $destDir . "/{$inventory->id}.jpg";
                $request->file('file')->move(dirname($dest), basename($dest));
            } catch (\Throwable $e) {
                Log::warning('Inventory image save failed', ['inv' => $inventory->id, 'error' => $e->getMessage()]);
            }
        }

        // Save main record
        $inventory->save();

        // Best-effort group propagation when base name changed
        if ($doProp) {
            try {
                // Exact base
                Inventory::query()
                    ->where('organization_id', $orgId)
                    ->where('id', '!=', $inventory->id)
                    ->where('name', $oldBase)
                    ->update(['name' => $newBase]);

                // Base + leaf
                Inventory::query()
                    ->where('organization_id', $orgId)
                    ->where('id', '!=', $inventory->id)
                    ->where('name', $oldBase.' :gls:')
                    ->update(['name' => $newBase.' :gls:']);

                // Base + trailing <img ...> (scan and fix safely)
                $imgCandidates = Inventory::query()
                    ->where('organization_id', $orgId)
                    ->where('id', '!=', $inventory->id)
                    ->where('name', 'like', $oldBase.'%<img%')
                    ->get(['id','name']);

                foreach ($imgCandidates as $item) {
                    $itemBase = $this->normalizeBaseName($item->name);
                    if ($itemBase !== $oldBase) continue;
                    $hadLeaf  = $this->hasLeaf($item->name);
                    $nextName = $newBase . ($hadLeaf ? ' :gls:' : '');
                    if ($nextName !== $item->name) {
                        $item->name = $nextName;
                        $item->save();
                    }
                }

                session()->flash('success', 'Inventory saved — group name synced.');
            } catch (\Throwable $e) {
                Log::warning('Inventory name propagation failed', [
                    'org' => $orgId, 'inv' => $inventory->id, 'error' => $e->getMessage()
                ]);
                session()->flash('success', 'Inventory saved (group sync skipped due to an error).');
            }
        } else {
            session()->flash('success', 'Inventory saved.');
        }

        // Redirect back to list or provided return URL
        $target = session('inventory_list_url') ?: $request->input('return_url') ?: route('inventories.index');
        return redirect($target);
    }

    /* -------------------------------------------------
     | Create / Store
     | ------------------------------------------------- */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        // Empty instance for form defaults if needed
        $inventory = new Inventory([
            'inventory_type' => 'inventories',
            'storeQty'       => 0,
        ]);

        return view('backend.inventories.create', compact('categories', 'inventory'));
    }

    public function store(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $rawTiers  = optional(auth()->user()->organization)->discount_tiers;
        $tiers     = is_string($rawTiers) ? json_decode($rawTiers, true) : $rawTiers;
        $tierNames = collect($tiers ?? [])->pluck('name')->all();

        $validated = $request->validate([
            'name'                   => ['required','string','max:255'],
            'sku'                    => [
                'nullable','string','max:255',
                Rule::unique('inventories', 'sku')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'Label'                  => ['nullable','string','max:255'],
            'original_price'         => ['nullable','numeric'],
            'original_cost'          => ['nullable','numeric'],
            'weight'                 => ['nullable','numeric','min:0'],
            'category_id'            => ['required','exists:categories,id'],
            'selected_discount_tier' => ['nullable','string', Rule::in($tierNames)],
            'storeQty'               => ['nullable','numeric','min:0'],
            'inventory_type'         => ['nullable','in:inventories,hold_inventories'],
            'file'                   => ['nullable','file','mimes:jpg,jpeg,png','max:8192'],
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $isFlower = Str::lower($category->slug ?? $category->name) === 'flower'
                 || Str::contains(Str::lower($category->name ?? ''), 'flower');

        // Derive price if flower; otherwise require manual price
        $price = null;
        if ($isFlower) {
            $price = $this->priceFromTierName($validated['selected_discount_tier'] ?? null, $tiers)
                  ?? (isset($validated['original_price']) ? (float)$validated['original_price'] : null);

            if ($price === null) {
                return back()->withErrors(['original_price' => 'Price is required for flower (or choose a valid tier).'])
                             ->withInput();
            }
        } else {
            if (!isset($validated['original_price'])) {
                return back()->withErrors(['original_price' => 'Price is required for non-flower items.'])
                             ->withInput();
            }
            $price = (float)$validated['original_price'];
        }

        // Weight defaults (flower => 1, else 0 if omitted)
        $weight = array_key_exists('weight', $validated) ? (float)$validated['weight'] : null;
        if ($isFlower && ($weight === null || $weight <= 0)) {
            $weight = 1.0;
        }
        if ($weight === null) {
            $weight = 0.0;
        }

        $inv = new Inventory();
        $inv->organization_id        = $orgId;
        $inv->name                   = $validated['name'];
        $inv->sku                    = $validated['sku']                    ?? null;
        $inv->Label                  = $validated['Label']                  ?? null;
        $inv->original_price         = $price; // ensure NOT NULL
        $inv->original_cost          = $validated['original_cost']          ?? null;
        $inv->weight                 = $weight; // ensure NOT NULL
        $inv->category_id            = $validated['category_id'];
        $inv->selected_discount_tier = $validated['selected_discount_tier'] ?? null;
        $inv->storeQty               = array_key_exists('storeQty', $validated) ? (float)$validated['storeQty'] : 0;
        $inv->inventory_type         = $validated['inventory_type'] ?? 'inventories';
        $inv->save();

        // optional image save
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            try {
                $destDir = public_path('uploads/inventories');
                if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
                $dest = $destDir . "/{$inv->id}.jpg";
                $request->file('file')->move(dirname($dest), basename($dest));
            } catch (\Throwable $e) {
                Log::warning('Inventory image save failed', ['inv' => $inv->id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventory created.',
                'inventory' => [
                    'id' => $inv->id,
                    'name' => $inv->name,
                    'sku' => $inv->sku,
                    'Label' => $inv->Label,
                    'storeQty' => $inv->storeQty,
                    'image_url' => $inv->image_url,
                    'original_price' => $inv->original_price,
                ],
            ]);
        }

        return redirect()->route('inventories.index')->with('success', 'Inventory created.');
    }

    /* -------------------------------------------------
     | Type update (inventories / hold_inventories)
     | ------------------------------------------------- */
    public function updateType($id, Request $request)
    {
        $request->validate([
            'inventory_type' => 'required|in:inventories,hold_inventories',
        ]);

        $orgId = auth()->user()->organization_id;
        $inv   = Inventory::where('organization_id', $orgId)->findOrFail($id);

        $inv->inventory_type = $request->inventory_type;
        $inv->save();

        return response()->json(['success' => true, 'inventory_type' => $inv->inventory_type]);
    }

    /* -------------------------------------------------
     | Destroy
     | ------------------------------------------------- */
    public function destroy($id)
    {
        $orgId = auth()->user()->organization_id;

        $inventory = Inventory::where('organization_id', $orgId)->findOrFail($id);
        $inventory->delete();

        return response()->json(['message' => 'Inventory #'.$id.' deleted.']);
    }
}
