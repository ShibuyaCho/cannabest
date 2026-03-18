<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashDrawer;
use App\Models\User;
use App\Sale;
use App\Models\DrawerSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DrawerController extends Controller
{
    public function open(Request $request)
{
    $data = $request->validate([
      'drawer_id'      => 'required|exists:cash_drawers,id',
      'user_id'        => 'required|exists:users,id',
      'opening_amount' => 'required|numeric|min:0',
    ]);

    // close any existing session for that user or drawer
    DrawerSession::whereNull('closed_at')
        ->where(function($q) use ($data) {
            $q->where('user_id', $data['user_id'])
              ->orWhere('cash_drawer_id', $data['drawer_id']);
        })
        ->update(['closed_at' => now()]);

    // create new session with the chosen user
    DrawerSession::create([
      'cash_drawer_id'  => $data['drawer_id'],
      'user_id'         => $data['user_id'],
      'starting_amount' => $data['opening_amount'],
      'opened_at'       => now(),
    ]);

    return back()->with('success', 'Drawer opened for user.');
}

public function close(Request $request)
{
    $data = $request->validate([
        'drawer_id'      => 'required|exists:cash_drawers,id',
        'closing_amount' => 'required|numeric|min:0',
    ]);

    $orgId = Auth::user()?->organization_id;

    // Try normal (org-scoped) first
    $session = DrawerSession::where('cash_drawer_id', $data['drawer_id'])
        ->whereNull('closed_at')
        ->latest('opened_at')
        ->first();

    // Fallback: bypass org scope so legacy NULL-org rows are visible
    if (!$session) {
        $session = DrawerSession::withoutGlobalScope('org')
            ->where('cash_drawer_id', $data['drawer_id'])
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();
    }

    if (!$session) {
        return back()->withErrors('No open drawer session found.');
    }

    \DB::transaction(function () use ($session, $data, $orgId) {
        $session->update([
            'closing_amount'  => (float) $data['closing_amount'],
            'closed_at'       => now(),
            // backfill org if missing on legacy rows
            'organization_id' => $session->organization_id ?: $orgId,
        ]);

        // keep your existing status convention
        $session->drawer()->update(['status' => 'active']);
        // If you prefer explicit closed state, use: ['status' => 'closed']
    });

    return back()->with('success', 'Drawer closed successfully.');
}

    public function current()
    {
        $session = DrawerSession::with('drawer')
            ->where('user_id', Auth::id())
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        return back()->json($session);
    }
public function select(Request $request)
{
    $data = $request->validate([
        'drawer_id' => ['required','exists:cash_drawers,id'],
    ]);

    $userId   = Auth::id();
    $drawerId = $data['drawer_id'];

    // 1) Close any existing session for this user
    DrawerSession::where('user_id', $userId)
                 ->whereNull('closed_at')
                 ->update(['closed_at' => now()]);

    // 2) Create a new session (default starting_amount = 0)
    DrawerSession::create([
      'cash_drawer_id'  => $drawerId,
      'user_id'         => $userId,
      'starting_amount' => 0,
      'opened_at'       => now(),
    ]);

    // 3) Mark drawer as open
    CashDrawer::find($drawerId)->update(['status' => 'open']);

    // 4) Persist in session & return
    session(['drawer_id' => $drawerId]);

    return response()->json([
      'message'    => 'Drawer selected and session started',
      'drawer_id'  => $drawerId,
      'user_id'    => $userId,
    ]);
}
 public function index()
{
    // 1) Load all drawers + their assigned users
 $drawers = CashDrawer::with([
        'assignedUser',
        'currentSession',
        'sessions' => function($q) {
            $q->take(10);
        },
    ])->get();

    // 2) Load selectable users in this org
    $orgId = Auth::user()->organization_id;
    $users = User::where('role_id', 2)
                 ->where('organization_id', $orgId)
                 ->get();

    // 3) Find the current open session for this user
    $session = DrawerSession::where('user_id', Auth::id())
                    ->whereNull('closed_at')
                    ->latest('opened_at')
                    ->first();

    // 4) Build your salesTotals
    $salesTotals = [
        'cash' => 0,
        'card' => 0,
        'tax'  => 0,
    ];

    if ($session) {
        $range = [$session->opened_at, now()];

        // cash & card totals
        $salesTotals['cash'] = Sale::where('user_id', Auth::id())
            ->whereBetween('created_at', $range)
            ->where('payment_type', 'cash')
            ->sum('amount');

        $salesTotals['card'] = Sale::where('user_id', Auth::id())
            ->whereBetween('created_at', $range)
            ->where('payment_type', 'card')
            ->sum('amount');

        // **tax**: sum each column then add
        $state  = Sale::where('user_id', Auth::id())
                      ->whereBetween('created_at', $range)
                      ->sum('state_tax');

        $county = Sale::where('user_id', Auth::id())
                      ->whereBetween('created_at', $range)
                      ->sum('county_tax');

        $city   = Sale::where('user_id', Auth::id())
                      ->whereBetween('created_at', $range)
                      ->sum('city_tax');

        $salesTotals['tax'] = $state + $county + $city;
    }

    // 5) Return the view with everything
    return view('cash_drawers.index', compact('drawers','users','salesTotals'));
}

 
        
       
public function update(Request $request, $id)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $drawer = CashDrawer::findOrFail($id);
    $drawer->name = $data['name'];
    $drawer->save();

    return back()->with('success', 'Drawer name updated.');
}
    public function store(Request $request)
{

    $request->validate([
        'name' => 'required|string|max:255|unique:cash_drawers,name'
    ]);

    $drawer = CashDrawer::create([
        'name'   => $request->name,
        'status' => 'active'
    ]);

    return back()->json([
        'id'   => $drawer->id,
        'name' => $drawer->name
    ]);
}
}
