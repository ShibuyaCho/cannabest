@extends('layouts.Wholesale')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .card {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: 0.3s;
        margin-bottom: 20px;
        background-color: #f8f9fa;  /* Light gray background */
        border: 1px solid #e9ecef;  /* Light border */
        width: 100%;  /* This ensures the card takes full width of its column */
    }
    .card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card-body {
        padding: 2.5rem 2rem 2rem;  /* Increased padding */
    }
    .remove-product {
        z-index: 10;
        right: 10px;
        top: 10px;
    }
    .product-info {
        background-color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 20px;
    }

    #productContainer {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
</style>
<div class="container-fluid">  <!-- Changed from container to container-fluid -->
    <div class="row justify-content-center">
        <div class="col-md-20">  <!-- Adjust this value as needed -->
            <h1 class="text-center mb-4">Add New Wholesale Products</h1>

            @if($categories->isEmpty())
                <div class="alert alert-warning">
                    No categories found. Please add categories before creating products.
                </div>
            @else
                @php
                $licenseNumber = app(\App\Http\Controllers\WholesaleProductController::class)->getLicenseNumber();
                @endphp
                
                @if($licenseNumber)
               
                @else
                    <div class="alert alert-warning">
                        No license number found. Please contact the administrator to set up your organization's license number.
                    </div>
                @endif

                <form id="productForm" action="{{ route('wholesale.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div id="productContainer" class="row justify-content-center">  <!-- Added justify-content-center here -->
                        <!-- Products will be dynamically added here -->
                    </div>
                    <div class="text-center">  <!-- Centering the submit button -->
                        <button type="submit" class="btn btn-success mt-3">Save All Products</button>
                    </div>
                </form>
                <template id="productTemplate">
                    <div class="col-md-6 col-lg-5 mb-4">
                        <div class="card">
                            <div class="card-body position-relative">
                                <button type="button" class="btn btn-danger btn-sm position-absolute remove-product">Remove</button>
                                <input type="hidden" name="products[__INDEX__][package_id]" class="package-id">
                                <input type="hidden" name="products[__INDEX__][extraName]" class="product-extra-name">
                                <div class="product-info">
                                    <h5 class="card-title product-name-display"></h5>
                                    <p class="card-text package-id-display"></p>
                                </div>
                                <div class="form-group">
                                    <label>Display Name</label>
                                    <input type="text" name="products[__INDEX__][display_name]" class="form-control product-display-name" required>
                                </div>
                                <div class="form-group" style="display: none;">
                                    <label>Product Name</label>
                                    <input type="hidden" name="products[__INDEX__][name]" class="product-name">
                                </div>
                                <div class="form-group">
                                    <label>SKU</label>
                                    <input type="text" name="products[__INDEX__][sku]" class="form-control product-sku" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="products[__INDEX__][description]" class="form-control product-description"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][price]" class="form-control product-price" required>
                                </div>
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number" name="products[__INDEX__][quantity]" class="form-control product-quantity" required step="0.001" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Weight (grams/FL OZ)</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][weight]" class="form-control product-weight" required>
                                </div>
                                <div class="form-group">
                                    <label>THC Content (%/MG)</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][UnitThcContent]" class="form-control product-thc-content">
                                </div>
                                <div class="form-group">
                                    <label>CBD Content (%/MG)</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][UnitCbdContent]" class="form-control product-cbd-content">
                                </div>
                                <div class="form-group">
                                    <label>Brand</label>
                                    <select name="products[__INDEX__][brand_id]" class="form-control product-brand">
                                        <option value="">Select Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="products[__INDEX__][category_id]" class="form-control product-category" required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Product Image</label>
                                    <input type="file" name="products[__INDEX__][image]" class="form-control-file product-image">
                                    <img src="" alt="Product Image Preview" class="img-thumbnail mt-2 product-image-preview" style="max-width: 200px; display: none;">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="products[__INDEX__][status]" class="form-control product-status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="out_of_stock">Out of Stock</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
               
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let licenseNumber = '{{ $licenseNumber }}';
    const username = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';
    const vendorKey = '{{ $apiKey }}';
    const authHeader = "Basic " + btoa(username + ":" + vendorKey);
    let existingPackageIds = new Set();

    async function getAllPackages() {
        try {
            const url = `https://api-or.metrc.com/packages/v2/active?licenseNumber=${encodeURIComponent(licenseNumber)}`;
            const response = await fetch(url, {
                headers: { "Authorization": authHeader }
            });
            if (!response.ok) throw new Error("Could not retrieve packages");
            const packagesData = await response.json();
            const packages = packagesData.Data || [];
            console.log("packages", packages);
            populateProductForms(packages);
        } catch (error) {
            console.error("Error retrieving packages:", error);
            alert("Error retrieving packages. Check console for details.");
        }
    }

    function populateProductForms(packages) {
        const container = $('#productContainer');
        container.empty();
        existingPackageIds.clear();
    
        let groupedPackages = {};
    
packages.forEach(pkg => {
    const name = pkg.Item.Name;
    if (!groupedPackages[name]) {
        groupedPackages[name] = {
            packages: [],
            totalQuantity: 0,
            description: pkg.Item.Description || '',
            price: pkg.Item.UnitPrice || '0.00',
            sku: pkg.Item.ItemNumber || '',
            image: pkg.Item.ImageUrl || ''
        };
    }
    groupedPackages[name].packages.push(pkg);
    groupedPackages[name].totalQuantity += pkg.Quantity || 0;
});
    
        Object.entries(groupedPackages).forEach(([name, group], index) => {
            const template = $('#productTemplate').html();
            const productHtml = template.replace(/__INDEX__/g, index);
            const $product = $(productHtml);
    
            $product.find('.product-name').val(name);
            $product.find('.product-display-name').val(name);
            $product.find('.product-description').val(group.description);
            $product.find('.product-price').val(group.price);
            $product.find('.product-quantity').val(group.totalQuantity);
            $product.find('.product-sku').val(group.sku);
// Add image preview if available
if (group.image) {
    $product.find('.product-image-preview').attr('src', group.image).show();
}
    
            let packageButtons = $('<div class="package-buttons mb-2">');
            packageButtons.append('<strong>Package IDs:</strong><br>');
            group.packages.forEach((pkg, pkgIndex) => {
                let button = $('<button type="button" class="btn btn-sm btn-outline-primary mr-1 mb-1 package-button">')
                    .text(`${pkg.Label} (${pkg.Quantity})`)
                    .data('package', pkg);
                packageButtons.append(button);
    existingPackageIds.add(pkg.Id);
    
                // Add hidden inputs for each package
                $product.append(`<input type="hidden" name="products[${index}][packages][${pkgIndex}][package_id]" value="${pkg.Label}">`);
                $product.append(`<input type="hidden" name="products[${index}][packages][${pkgIndex}][quantity]" value="${pkg.Quantity}">`);
                $product.append(`<input type="hidden" name="products[${index}][packages][${pkgIndex}][metrc_data]" value='${JSON.stringify(pkg)}'>`);
            });
    
            $product.find('.package-id-display').html(packageButtons);
            $product.find('.product-name-display').text(name);
    
            container.append($product);
        });
    
        centerCards();
    
        $('.remove-product').on('click', function() {
            const productCard = $(this).closest('.col-md-6');
            productCard.remove();
            centerCards();
        });

        $('.package-button').on('click', function() {
            const pkg = $(this).data('package');
            createSinglePackageCard(pkg);
        });
    }

    function createSinglePackageCard(pkg) {
        const index = $('.card').length;
        const template = $('#productTemplate').html();
        const productHtml = template.replace(/__INDEX__/g, index);
        const $product = $(productHtml);

        $product.find('.product-name').val(pkg.Item.Name);
        $product.find('.product-display-name').val(pkg.Item.Name);
        $product.find('.product-description').val(pkg.Item.Description || '');
        $product.find('.product-price').val(pkg.Item.UnitPrice || '0.00');
        $product.find('.product-quantity').val(pkg.Quantity || 0);
        $product.find('.product-sku').val(pkg.Item.ItemNumber || '');

        $product.find('.package-id-display').html(`<strong>Package ID:</strong> ${pkg.Label}`);

        $product.append(`<input type="hidden" name="products[${index}][metrc_data]" value='${JSON.stringify([pkg])}'>`);

        $product.find('.product-name-display').text(pkg.Item.Name);

        $('#productContainer').append($product);

        centerCards();

        $product.find('.remove-product').on('click', function() {
            $product.remove();
            centerCards();
        });
    }

    function centerCards() {
        // Implement your centering logic here
        // For example:
        const container = $('#productContainer');
        const cards = container.find('.col-md-6');
        const containerWidth = container.width();
        const cardWidth = cards.first().outerWidth(true);
        const cardsPerRow = Math.floor(containerWidth / cardWidth);
        const marginLeft = (containerWidth - (cardsPerRow * cardWidth)) / 2;
        container.css('margin-left', marginLeft);
    }

    // Initial load of packages
    if (licenseNumber) {
        getAllPackages();
    } else {
        alert('No license number found. Please contact the administrator.');
    }

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $('.card').each(function(index) {
            const $card = $(this);
            
            // Set product data
            formData.set(`products[${index}][name]`, $card.find('[name$="[display_name]"]').val() || '');
            formData.set(`products[${index}][display_name]`, $card.find('[name$="[display_name]"]').val() || '');
            formData.set(`products[${index}][price]`, $card.find('[name$="[price]"]').val() || '0');
            formData.set(`products[${index}][category_id]`, $card.find('[name$="[category_id]"]').val() || '');
            formData.set(`products[${index}][sku]`, $card.find('[name$="[sku]"]').val() || '');
            formData.set(`products[${index}][description]`, $card.find('[name$="[description]"]').val() || '');
            formData.set(`products[${index}][status]`, $card.find('[name$="[status]"]').val() || 'active');
            formData.set(`products[${index}][quantity]`, $card.find('[name$="[quantity]"]').val() || '0');

// Handle file input separately
const imageFile = $card.find('[name$="[image]"]')[0].files[0];
if (imageFile) {
    formData.set(`products[${index}][image]`, imageFile);
} else {
    // If no new image is uploaded, use the existing image URL
    const existingImageUrl = $card.find('.product-image-preview').attr('src');
    if (existingImageUrl) {
        formData.set(`products[${index}][image_url]`, existingImageUrl);
    }

            }

            // Package data is already set in hidden inputs, no need to modify it here
        });

        formData.append('license_number', licenseNumber);

        // Disable submit button to prevent multiple submissions
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true).text('Saving...');

        // AJAX call
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Products saved successfully:', response);
                
                // Update existingPackageIds with the saved package IDs
                if (response.savedPackageIds) {
                    existingPackageIds = new Set(response.savedPackageIds);
                }
            
                window.location.href = "{{ route('wholesale.products.index') }}?message=" + encodeURIComponent('Products saved successfully!');
            },
            error: function(xhr, status, error) {
                console.error('Error saving products:', xhr.responseJSON);
                let errorMessage = 'An error occurred while saving the products. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                alert(errorMessage);
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false).text('Save All Products');
            }
        });
    });
});
</script>
@endsection