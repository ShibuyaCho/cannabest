<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    // Show the form to edit the current user's org
    public function edit()
    {
        $org = Auth::user()->organization;
        return view('organizations.edit', compact('org'));
    }

    // Handle the POST/PUT from that form
    public function update(Request $request)
    {
        $org = Auth::user()->organization;

        $data = $request->validate([
            'phone'                       => 'nullable|string',
            'physical_address'            => 'nullable|string',
            'county_tax'                  => 'nullable|integer|min:0|max:100',
            'city_tax'                    => 'nullable|integer|min:0|max:100',
            'state_tax'                   => 'nullable|integer|min:0|max:100',
            'currency'                    => 'nullable|string|max:5',
            'footer_text'                 => 'nullable|string',
            'sunday'                      => 'nullable|string',
            'monday'                      => 'nullable|string',
            'tuesday'                     => 'nullable|string',
            'wednesday'                   => 'nullable|string',
            'thursday'                    => 'nullable|string',
            'friday'                      => 'nullable|string',
            'saturday'                    => 'nullable|string',
            'discount_tiers'              => 'nullable|array',
            'discount_tiers.*.pricing'    => 'nullable|array',
            'sms_alert_phone_numbers'     => 'nullable|array',
            'sms_alert_customer_creation' => 'boolean',
           
        ]);

        // If you collected phone numbers as newline-separated text:
        if (is_string($request->input('sms_alert_phone_numbers'))) {
            $data['sms_alert_phone_numbers'] = array_filter(
                array_map('trim', explode("\n", $request->input('sms_alert_phone_numbers')))
            );
        }
$data['discount_tiers'] = $request->input('discount_tiers', []);

        $org->update($data);

        return redirect()
            ->route('organizations.edit')
            ->with('success','Organization settings saved.');
    }
    
    
}
