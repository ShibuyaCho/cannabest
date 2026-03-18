<?php

namespace App\Http\Controllers;

use App\Models\CustomizableContent;
use App\Models\Brand;
use App\Models\WholesaleProduct;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomizableContentController extends Controller
{
    public function edit()
    {
        $organizationId = auth()->user()->organization_id;
        $customContent = CustomizableContent::getContentForOrganizationBrands($organizationId);

        $brand = Brand::where('organization_id', $organizationId)->first();
        
        $products = WholesaleProduct::where('organization_id', $organizationId)
            ->take(6)
            ->get();

        $organization = Organization::find($organizationId);
        $previewUrl = route('wholesale.customer.organization-brands', ['organization' => $organizationId, 'preview' => 'true']);

        return view('admin.customize', compact('customContent', 'brand', 'products', 'organization', 'previewUrl'));
    }

    public function update(Request $request, $pageName)
    {
        $organizationId = $request->input('organization_id');
        $content = $request->input('content');

        // Update or create CustomizableContent for the organization page
        CustomizableContent::updateOrCreate(
            [
                'page_name' => $pageName,
                'organization_id' => $organizationId
            ],
            [
                'content' => json_encode($content) // Ensure content is stored as JSON
            ]
        );

        // Handle image upload
        if ($request->hasFile('image')) {
            $organization = Organization::findOrFail($organizationId);
            $imagePath = $request->file('image')->store('organization_images', 'public');
            $organization->update(['image' => $imagePath]);
        }

        return response()->json(['message' => 'Content and image updated successfully']);
    }
}