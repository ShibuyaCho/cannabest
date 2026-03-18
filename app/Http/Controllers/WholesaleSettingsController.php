<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WholesaleSetting;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

class WholesaleSettingsController extends Controller
{
    
   
public function edit()
{
    $user = Auth::user();
    $organization = $user->organization;

    $organizationSettings = $organization ? $organization->only(['name', 'address', 'phone', 'email']) : [];
    $userSettings = $user->only(['name', 'email', 'phone']);

    // Fetch wholesale settings for the current organization
    $wholesaleSettings = WholesaleSetting::where('organization_id', $user->organization_id)
        ->get()
        ->keyBy('key');

    // Add API key to user settings
    $userSettings['Api Key'] = $user->apiKey;

    return view('backend.wholesale_settings.general.edit', compact(
        'organizationSettings',
        'userSettings',
        'wholesaleSettings'
    ));
}
    public function update(Request $request)
    {
        $organizationSettings = $request->input('organization_settings', []);
        $userSettings = $request->input('user_settings', []);
        $apiKey = $request->input('Api Key');

        // Update organization settings
        foreach ($organizationSettings as $key => $value) {
            WholesaleSetting::updateOrCreate(
                ['key' => $key, 'organization_id' => Auth::user()->organization_id],
                ['value' => $value]
            );
        }

        // Update user settings
        foreach ($userSettings as $key => $value) {
            WholesaleSetting::updateOrCreate(
                ['key' => $key, 'organization_id' => Auth::user()->organization_id],
                ['value' => $value]
            );
        }

        // Update API key
        $user = Auth::user();
        if ($apiKey !== $user->apiKey) {
            $user->apiKey = $apiKey;
            $user->save();
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    } 
}