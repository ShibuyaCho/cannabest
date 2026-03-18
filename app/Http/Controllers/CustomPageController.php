<?php


namespace App\Http\Controllers;

use App\Models\CustomPage;
use Illuminate\Http\Request;

class CustomPageController extends Controller
{
    public function index()
    {
        $pages = CustomPage::all();
        return view('custom_pages.index', compact('pages'));
    }

    public function create()
    {
        return view('custom_pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|json',
        ]);

        CustomPage::create([
            'title' => $request->title,
            'content' => $request->content,
            'organization_id' => auth()->user()->organization_id,
        ]);

        return redirect()->route('custom_pages.index')->with('success', 'Page created successfully.');
    }

    public function show(CustomPage $customPage)
    {
        return view('custom_pages.show', compact('customPage'));
    }

    // Add methods for edit, update, and delete as needed
}