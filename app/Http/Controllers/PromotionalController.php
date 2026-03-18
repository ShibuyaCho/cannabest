<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PromotionalController extends Controller
{
    private $receivingEmail = 'cannabestpos@gmail.com'; // Set your receiving email here

    public function show()
    {
        return view('promotional');
    }

    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'organization_info' => 'nullable|string|max:1000', // Add validation for the new field
        ]);

        // Prepare the email content
        $emailContent = "New sign-up:\n";
        $emailContent .= "Name: {$request->name}\n";
        $emailContent .= "Email: {$request->email}\n";
        $emailContent .= "Organization Info: {$request->organization_info}\n"; // Include the new field

        // Send email
        Mail::raw($emailContent, function ($message) {
            $message->to($this->receivingEmail)
                    ->subject('New Promotional Sign-up');
        });

        return back()->with('success', 'Thank you for signing up! We will be in touch soon.');
    }
}