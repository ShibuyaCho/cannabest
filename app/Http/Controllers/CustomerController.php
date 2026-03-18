<?php

namespace App\Http\Controllers;

use App\User;
use App\Inventory;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Set the role id that represents "customer".
     */
    private const CUSTOMER_ROLE_ID = 5;

    /**
     * Convenience: current viewer's org id (may be null).
     */
    private function viewerOrgId()
    {
        return Auth::user()->organization_id ?? null;
    }

    /**
     * Base query: customers in the same organization as the viewer.
     * This enforces org isolation for index/show/edit/update/destroy.
     */
    private function baseScopedQuery()
    {
        $orgId = $this->viewerOrgId();

        // Require exact org match. If viewer has null org, this will only
        // return users whose organization_id is null.
        return User::query()
            ->where('role_id', self::CUSTOMER_ROLE_ID)
            ->where('organization_id', $orgId);
    }
public function retailMarketplace(Request $request)
{
    $perPage = (int) $request->input('per_page', 24);

    $orgs = Organization::query()
        ->select([
            'id','name','type','status','license_number',
            'business_name','physical_address','county','county_tax','city_tax','state_tax'
        ])
        ->where('type', 'retail')
        ->where(function($q){
            // optional: only show active orgs; remove if you want all
            $q->whereNull('status')->orWhere('status', 'active');
        })
        ->when($request->filled('q'), function ($q) use ($request) {
            $term = '%'.$request->q.'%';
            $q->where(function ($w) use ($term) {
                $w->where('name','like',$term)
                  ->orWhere('business_name','like',$term)
                  ->orWhere('license_number','like',$term)
                  ->orWhere('physical_address','like',$term)
                  ->orWhere('county','like',$term);
            });
        })
        ->orderBy('name')
        ->paginate($perPage)
        ->withQueryString();

    return view('marketplace.retail.index', [
        'orgs'    => $orgs,
        'filters' => $request->only(['q','per_page']),
    ]);
}
public function retailOrganizationMenu(Organization $organization, Request $request)
{
    abort_unless($organization->type === 'retail', 404);

    $perPage = (int) $request->input('per_page', 24);

    $products = Inventory::query()
        ->select([
            'id','name',
            'original_price as price',
            'Label as label',
            'inventory_type','updated_at'
        ])
        ->where('inventory_type', 'inventories')
       
        ->where('organization_id', $organization->id)
        ->when($request->filled('q'), function ($q) use ($request) {
            $term = '%'.$request->q.'%';
            $q->where(function ($w) use ($term) {
                $w->where('name','like',$term)
                  
                  ->orWhere('Label','like',$term);
            });
        })
        ->orderByDesc('updated_at')
        ->paginate($perPage)
        ->withQueryString();

    return view('marketplace.retail.org-menu', [
        'org'     => $organization,
        'products'=> $products,
        'filters' => $request->only(['q','per_page']),
    ]);
}

public function retailProduct(Inventory $inventory)
{
    abort_unless($inventory->inventory_type === 'inventories' && $inventory->storeQty > 0, 404);
    return view('marketplace.retail.show', ['item' => $inventory]);
}
    /**
     * INDEX: Only customers with matching organization_id AND role_id.
     * Supports q= search across name/email/phone.
     */
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');

        $customers = $this->baseScopedQuery()
            ->when($q, function ($qb) use ($q) {
                $like = "%{$q}%";
                $qb->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('backend.customers.index', [
            'customers' => $customers,
            'q'         => $q,
        ]);
    }

    public function create()
    {
        return view('backend.customers.create');
    }

    /**
     * STORE: Email+password required; everything else optional.
     * New customer is assigned to the creator's organization_id.
     */
 public function store(Request $request)
{
    $orgId = Auth::user()->organization_id;   // <= viewer's org

    $validated = $request->validate([
        'email'    => ['required','email','max:255','unique:users,email'],
        'password' => ['required','string','min:6','confirmed'],
        'name'     => ['nullable','string','max:255'],
        'phone'    => ['nullable','string','max:20'],
        'address'  => ['nullable','string','max:255'],
        'city'     => ['nullable','string','max:100'],
        'state'    => ['nullable','string','max:100'],
        'zip'      => ['nullable','string','max:20'],
    ]);

    $user = new \App\User();
    $user->email           = $validated['email'];
    $user->password        = \Hash::make($validated['password']);
    $user->name            = $validated['name'] ?? null;
    $user->phone           = $validated['phone'] ?? null;
    $user->address         = $validated['address'] ?? null;
    $user->city            = $validated['city'] ?? null;
    $user->state           = $validated['state'] ?? null;
    $user->zip             = $validated['zip'] ?? null;
    $user->role_id         = self::CUSTOMER_ROLE_ID;  // don’t take role from the form
    $user->organization_id = $orgId;                  // <= bind here
    $user->save();

    return redirect()->route('customers.index')->with('message-success', 'Customer created successfully.');
}


    private function generateUniqueApiKey(): string
    {
        do {
            $apiKey = Str::random(60);
        } while (User::where('apiKey', $apiKey)->exists());

        return $apiKey;
    }

    public function show($id)
    {
        $customer = $this->baseScopedQuery()->findOrFail($id);
        return view('backend.customers.show', compact('customer'));
    }

    public function edit($id)
    {
        $customer = $this->baseScopedQuery()->findOrFail($id);
        return view('backend.customers.edit', compact('customer'));
    }

    /**
     * UPDATE: Email required; password optional. Scoped to viewer's org.
     */
    public function update(Request $request, $id)
    {
        $customer = $this->baseScopedQuery()->findOrFail($id);

        $validated = $request->validate([
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($customer->id)],
            'password' => ['nullable','string','min:6','confirmed'],
            'name'     => ['nullable','string','max:255'],
            'phone'    => ['nullable','string','max:20'],
            'address'  => ['nullable','string','max:255'],
            'city'     => ['nullable','string','max:100'],
            'state'    => ['nullable','string','max:100'],
            'zip'      => ['nullable','string','max:20'],
        ]);

        $customer->email   = $validated['email'];
        $customer->name    = $validated['name']    ?? null;
        $customer->phone   = $validated['phone']   ?? null;
        $customer->address = $validated['address'] ?? null;
        $customer->city    = $validated['city']    ?? null;
        $customer->state   = $validated['state']   ?? null;
        $customer->zip     = $validated['zip']     ?? null;

        if (!empty($validated['password'])) {
            $customer->password = Hash::make($validated['password']);
        }

        $customer->save();

        return redirect()->route('customers.index')
            ->with('message-success', 'Customer updated!');
    }

    /**
     * DESTROY: Scoped to viewer's org.
     */
    public function destroy($id)
    {
        $customer = $this->baseScopedQuery()->findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('message-success', 'Customer deleted!');
    }

    /**
     * POS phone lookup: scoped to viewer's org.
     */
    public function findcustomer(Request $request)
    {
        $phone = $request->input('phone');

        $record = $this->baseScopedQuery()
            ->where('phone', $phone)
            ->first();

        return response()->json($record);
    }

    /**
     * Self-service signup (public). NOTE: no org binding here by design.
     * If you want public signups to attach to an org, pass an org param and set organization_id.
     */
    public function storeRetailCustomer(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','confirmed'],
            'name'     => ['nullable','string','max:255'],
        ]);

        $customer = User::create([
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name'     => $validated['name'] ?? null,
            'role_id'  => self::CUSTOMER_ROLE_ID,
            // 'organization_id' => <attach here if desired for public flow>,
        ]);

        Auth::login($customer);

        return redirect('/retail-menu')
            ->with('message-success', 'Retail customer account created successfully.');
    }
    
}
