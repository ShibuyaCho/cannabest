<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $data = [
            'categories' => Category::paginate(15),
        ];
        return view('backend.category.index', $data);
    }

    public function get_all_categories()
    {
        $data = [
            'categories' => Category::paginate(1000),
        ];
        return json_encode($data);
    }
 public function findClosestCategory(Request $request)
    {
        $categoryName = $request->input('category_name');
        $closestCategory = findClosestCategory($categoryName);

        return response()->json($closestCategory);
    }
    public function create()
    {
        return view('backend.category.create');
    }
  public function store(Request $request)
    {
        // 1) Validate inputs
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'sales_limit_category' => 'nullable|string',
            'file'                 => 'nullable|image|max:2048',  // max 2MB, adjust as needed
            'taxable'              => 'sometimes|boolean',
        ]);

        // 2) Ensure we save false when unchecked
        $data['taxable'] = $request->has('taxable');

        // 3) Create the category (without image fields)
        $category = Category::create($data);

        // 4) Handle file upload if present
        if ($request->hasFile('file')) {
            $file      = $request->file('file');
            $ext       = $file->getClientOriginalExtension();
            $filename  = $category->id . '.' . $ext;

            $uploadDir = public_path('uploads/category');
            $thumbDir  = public_path('uploads/category/thumb');

            // make sure directories exist
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }
            if (! File::isDirectory($thumbDir)) {
                File::makeDirectory($thumbDir, 0755, true);
            }

            // move original
            $file->move($uploadDir, $filename);

            // for now just copy it as a “thumb” (you can integrate Intervention if you want real resizing)
            copy($uploadDir . '/' . $filename, $thumbDir . '/' . $filename);

            // 5) Save paths on the model
            $category->update([
                'image' => '/uploads/category/' . $filename,
                'thumb' => '/uploads/category/thumb/' . $filename,
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        // 1) Validate inputs
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'sales_limit_category' => 'nullable|string',
            'file'                 => 'nullable|image|max:2048',
            'taxable'              => 'sometimes|boolean',
        ]);

        // 2) Coerce checkbox
        $data['taxable'] = $request->has('taxable');

        // 3) Update non-file fields
      $category->update($data);

    // 2) Then handle file upload safely
    if ($request->hasFile('file')) {
        try {
            $file     = $request->file('file');
            $ext      = $file->getClientOriginalExtension();
            $filename = $category->id . '.' . $ext;

            // store original in storage/app/public/uploads/category
            $path = $file->storeAs('uploads/category', $filename, 'public');

            // (optional) create a thumbnail
            $thumbPath = "uploads/category/thumb/{$filename}";
            Storage::disk('public')->copy($path, $thumbPath);

            // update model with storage URLs
            $category->update([
                'image' => "/storage/{$path}",
                'thumb' => "/storage/{$thumbPath}",
            ]);

        } catch (\Throwable $e) {
            \Log::error("Category {$category->id} image upload failed: " . $e->getMessage());
            // do NOT re-throw — we still want the DB changes to stick
        }
    }

    return redirect()
        ->route('categories.index')
        ->with('success','Category updated.');
}


    public function store_ajax(Request $request)
    {
        $form = $request->all();

        // Check if a category with the same name already exists
        $category = Category::where('name', $form['name'])->first();

        if (!$category) {
            // If category does not exist, create a new one
            $category = Category::create($form);
        }

        return response()->json($category);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return view('backend.category.show', compact('category'));
    }

    public function edit($id)
    {
        // Retrieve the category by its ID
        $category = Category::findOrFail($id);
        
        // Optionally, if you need a list of all categories for the toolbar
        $categories = Category::all();
        
        // Pass the data to the view
        return view('backend.category.edit', compact('category', 'categories'));
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect('categories')
            ->with('message-success', 'Category deleted!');
    }

     public function getCategoryNames(Request $request)
    {
        $ids = $request->query('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No IDs provided'], 400);
        }

        $categories = Category::whereIn('id', $ids)->pluck('name', 'id');

        if ($categories->isEmpty()) {
            return response()->json(['error' => 'No matching categories found'], 404);
        }

        return response()->json($categories);
    }
}
