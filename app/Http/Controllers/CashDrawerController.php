<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\CashDrawer as Drawer;
use App\Models\DrawerSession;
use App\Sale;

class CashDrawerController extends Controller
{
    /**
     * Quick helper for current org id.
     */
    private function orgId(): ?int
    {
        return auth()->user()?->organization_id;
    }

    /**
     * List drawers in the viewer's org.
     */
    public function index()
    {
        $drawers = Drawer::with(['assignedUser','currentSession','sessions'])
            ->orderBy('id','asc')
            ->get();

        return view('admin.drawers.index', compact('drawers'));
    }

    /**
     * Create a drawer in the viewer's org.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
        ]);

        Drawer::create([
            'name'            => $data['name'],
            'status'          => 'closed',
            'organization_id' => $this->orgId(),
        ]);

        return back()->with('success','Drawer created.');
    }

    /**
     * Update a drawer (name only). Model is org-scoped via global scope on the model.
     */
    public function update(Request $request, Drawer $drawer)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
        ]);

        $drawer->update(['name' => $data['name']]);

        return back()->with('success','Drawer updated.');
    }

    /**
     * Open a drawer session using `opening_amount` (mapped to DB `starting_amount`).
     */
    public function open(Request $request)
    {
        $orgId = $this->orgId();

        $data = $request->validate([
            'drawer_id' => [
                'required',
                Rule::exists('cash_drawers','id')->where(fn($q)=>$q->where('organization_id',$orgId)),
            ],
            'opening_amount' => 'required|numeric|min:0',
            'user_id' => [
                'required',
                Rule::exists('users','id')->where(fn($q)=>$q->where('organization_id',$orgId)),
            ],
        ]);

        // Ensure the drawer is in-org and not already open
        $drawer = Drawer::with('currentSession')->findOrFail($data['drawer_id']);
        if ($drawer->currentSession) {
            return back()->withErrors('This drawer already has an open session.');
        }

        // Optional: block if this user already has an open session (one open till per user)
        $userHasOpen = DrawerSession::whereNull('closed_at')
            ->where('organization_id', $orgId)
            ->where('user_id', $data['user_id'])
            ->exists();
        if ($userHasOpen) {
            return back()->withErrors('That user already has an open till.');
        }

DB::transaction(function () use ($drawer, $data) {
    \App\Models\DrawerSession::create([
        'cash_drawer_id'  => $drawer->id,
        'user_id'         => $data['user_id'],
        'organization_id' => $drawer->organization_id, // <- use drawer’s org, not a default 0
        'starting_amount' => $data['opening_amount'],
        'opened_at'       => now(),
    ]);

    $drawer->update(['status' => 'open']);
});



        return back()->with('success', 'Drawer opened with '.$data['opening_amount']);
    }

    /**
     * Close a drawer session; now ANY authenticated user can close it.
     */
    public function close(Request $request)
{
    $orgId = auth()->user()?->organization_id;

    $data = $request->validate([
        'drawer_id'      => ['required', \Illuminate\Validation\Rule::exists('cash_drawers','id')->where(fn($q)=>$q->where('organization_id',$orgId))],
        'closing_amount' => 'required|numeric|min:0',
    ]);

    // Load drawer in-org; eager load relation that already bypasses org scope
    $drawer  = \App\Models\CashDrawer::with('currentSession')->findOrFail($data['drawer_id']);

    // 1) try relation
    $session = $drawer->currentSession;

    // 2) robust fallback (bypass org scope so even legacy NULL-org rows are seen)
    if (!$session) {
        $session = \App\Models\DrawerSession::withoutGlobalScope('org')
            ->where('cash_drawer_id', $drawer->id)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();
    }

    if (!$session) {
        return back()->withErrors('No open session for this drawer.');
    }

    \DB::transaction(function () use ($drawer, $session, $data, $orgId) {
        $session->update([
            'closing_amount'  => (float)$data['closing_amount'],
            'closed_at'       => now(),
            // backfill in case legacy rows missed org id
            'organization_id' => $session->organization_id ?: $orgId,
        ]);

        $drawer->update(['status' => 'closed']);
    });

    return back()->with('success', 'Drawer closed.');
}

}
