<?php

// app/Http/Controllers/SliderController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::all();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle file upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads/slider'), $imageName);
        }

        // Create a new slider entry
        Slider::create([
            'title' => $request->input('title'),
            'image' => $imageName,
        ]);

        return redirect()->back()->with('success', 'Slider image uploaded successfully!');
    }
}
