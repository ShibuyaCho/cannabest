<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Brand::class, 'brand');
    }

    public function index()
    {
        $brands = Brand::where('organization_id', auth()->user()->organization_id)
                       ->orderBy('name')
                       ->paginate(12);
    
        return view('backend.brands.index', compact('brands'));
    }

    public function create()
    {
        $brands = Brand::all();
        return view('backend.brands.create', compact('brands'));
    }    

    public function store(Request $request)
    {
        try {
            $this->authorize('create', Brand::class);

            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'description' => 'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $brand = new Brand($validatedData);
        
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('public/brands');
                $image->move($destinationPath, $imageName);
                $brand->image = 'brands/' . $imageName;
            }

            $brand->user_id = Auth::id();
            $brand->organization_id = Auth::user()->organization_id;
            $brand->save();

            return redirect()->route('wholesale.brands.index')->with('success', 'Brand created successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->withInput()->with('error', 'You are not authorized to create a brand.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Brand creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create brand. Please try again.');
        }
    }

    public function edit(Brand $brand)
    {
            $this->authorize('update', $brand);
            return view('backend.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        try {
            $this->authorize('update', $brand);

            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'description' => 'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $brand->fill($validatedData);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('public/brands');
                $image->move($destinationPath, $imageName);
            
                // Delete old image if exists
                if ($brand->image) {
                    $oldImagePath = public_path($brand->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            
                $brand->image = 'brands/' . $imageName;
            }

            $brand->save();

            return redirect()->route('wholesale.brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', 'You are not authorized to update this brand.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update brand. ' . $e->getMessage());
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            $this->authorize('delete', $brand);

            $brand->delete();

            return redirect()->route('wholesale.brands.index')->with('success', 'Brand deleted successfully.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', 'You are not authorized to delete this brand.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete brand. ' . $e->getMessage());
        }
    }
}
