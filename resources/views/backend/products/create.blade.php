@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $licenseNumber = app(\App\Http\Controllers\WholesaleProductController::class)->getLicenseNumber();
    $apiKey = auth()->user()->apiKey ?? null;
@endphp

@if(!$apiKey)
    <div class="container mt-3">
        <div class="alert alert-warning text-center">
            <small>
                You haven’t configured your Metrc API key yet.
                <a href="{{ url('settings/profile') }}">Click here to add it</a>.
            </small>
        </div>
    </div>
@endif

<style>
    .card-deck-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }
    .card {
        width: 35vw;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        overflow-y: auto;
        position: relative; /* Ensure the card is the positioning context for absolute elements */
    }
    .card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card-body { padding: 1.5rem 1rem; }
    .remove-product {
        position: absolute;
        top: 10px; /* Adjust as needed */
        right: 10px; /* Adjust as needed */
        z-index: 10;
    }
    .product-info { background: #fff; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
    .form-group { margin-bottom: 10px; }
    .pagination-controls {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    .pagination-controls button {
        margin: 0 5px;
    }
    .json-container {
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
        line-height: 1.5;
    }

    .modal-content {
        border-radius: 10px;
        overflow: hidden;
    }

    .modal-header {
        background-color: #5a9bd4; /* Softer blue */
        color: white;
    }

    .modal-footer {
        background-color: #f1f1f1;
    }

    .modal-title {
        font-weight: bold;
    }

    pre {
        margin: 0;
    }

    /* Custom JSON styling */
    .token.string {
        color: #d14;
    }
    .token.number {
        color: #099;
    }
    .token.boolean {
        color: #905;
    }
    .token.null {
        color: #c00;
    }
    .token.key {
        color: #1c00cf;
    }

    /* Custom styles for package information */
    .package-info-title {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }

    .package-info-value {
        font-size: 16px;
        color: #555;
        margin-bottom: 10px;
    }
</style>

<div class="form-group">
    <label for="searchLabel">Search by Package ID</label>
    <input type="text" id="searchLabel" class="form-control" placeholder="Enter Package ID to search">
</div>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-20">
            <h1 class="text-center mb-4">Add New Products</h1>

<div class="text-center mb-3">
  <button type="button" id="addNonMetrc" class="btn btn-secondary">
    Add Non-Metrc Products
  </button>
</div>
            @if($categories->isEmpty())
                <div class="alert alert-warning">
                    No categories found. Please add categories before creating products.
                </div>
            @else
                <form id="productForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div id="productContainer" class="card-deck-container">
                        {{-- cards injected via JS --}}
                    </div>

                    <div class="pagination-controls">
                        <button type="button" id="prevPage" class="btn btn-secondary">Previous</button>
                        <span id="pageInfo"></span>
                        <button type="button" id="nextPage" class="btn btn-secondary">Next</button>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success">Save All Products</button>
                    </div>
                </form>

                <template id="productTemplate">
                    <div class="col-md-6 col-lg-5 mb-4 position-relative">
                        <div class="card">
                            <div class="card-body">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 remove-product">Remove</button>
                                <div class="product-info mb-3">
                                    <h5 class="product-name-display"></h5>
                                    <p class="package-id-display mb-0"></p>
                                    <button type="button" class="btn btn-info btn-sm view-package-info">Package Information</button>
                                </div>
                                <input type="hidden" name="products[__INDEX__][metrc_package]" class="product-metrc-package">
                                <input type="hidden" name="products[__INDEX__][id]" class="product-id">
                                <input type="hidden" name="products[__INDEX__][organization_id]" class="product-organization-id">
                                       <div class="form-group">
                                    <label>Wholesale Product</label>
                                    <div class="input-group">
                                        <input type="text" name="products[__INDEX__][product_name]" class="form-control product-wholesale-name" readonly>
                                        <input type="hidden" name="products[__INDEX__][product_id]" class="product-wholesale-id">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary select-wholesale-product" data-toggle="modal" data-target="#wholesaleProductModal">Select</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Existing Product</label>
                                    <div class="input-group">
                                        <input type="text" name="products[__INDEX__][existing_product_name]" class="form-control product-existing-name" readonly>
                                        <input type="hidden" name="products[__INDEX__][existing_product_id]" class="product-existing-id">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary select-existing-product" data-toggle="modal" data-target="#existingProductModal">Select</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Display Name</label>
                                    <input type="text" name="products[__INDEX__][name]" class="form-control product-display-name" required>
                                    <input type="hidden" name="products[__INDEX__][modified_name]" class="product-modified-name">
                                </div>
                                <!-- Add the Green Leaf Special checkbox -->
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" class="green-leaf-special-checkbox"> Green Leaf Special
                                    </label>
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
                                    <label>Original Price</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][original_price]" class="form-control product-original-price" required>
                                </div>
                                <div class="form-group">
                                    <label>Original Cost</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][original_cost]" class="form-control product-original-cost" required>
                                </div>
                                <div class="form-group">
                                    <label>Weight</label>
                                    <input type="number" step="0.001" name="products[__INDEX__][weight]" class="form-control product-weight">
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
                                    <label>THC Content</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][THC]" class="form-control product-thc-content">
                                </div>
                                <div class="form-group">
                                    <label>CBD Content</label>
                                    <input type="number" step="0.01" name="products[__INDEX__][CBD]" class="form-control product-cbd-content">
                                </div>
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number" name="products[__INDEX__][quantity]" class="form-control product-quantity">
                                </div>
                                <div class="form-group">
                                    <label>Discount Tiers</label>
                                    <input type="text" name="products[__INDEX__][discount_tiers]" class="form-control product-discount-tiers">
                                </div>
                                <div class="form-group">
                                    <label>Image</label>
                                    <input type="file" name="products[__INDEX__][file]" class="form-control-file product-image">
                                    <img class="product-image-preview img-thumbnail mt-2" style="max-width:200px;display:none">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="products[__INDEX__][status]" class="form-control product-status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="out_of_stock">Out of Stock</option>
                                    </select>
                                </div>
                                <input type="hidden" name="products[__INDEX__][metrc_package]" class="product-metrc-package">
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-primary input-item-button">Input Item</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="modal fade" id="wholesaleProductModal" tabindex="-1" role="dialog" aria-labelledby="wholesaleProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="wholesaleProductModalLabel">Select Wholesale Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="wholesaleProductFilter">Filter by:</label>
                                    <select id="wholesaleProductFilter" class="form-control">
                                        <option value="organization">Organization</option>
                                        <option value="category">Category</option>
                                    </select>
                                </div>
                                <div id="wholesaleProductList">
                                    <!-- Wholesale products will be loaded here dynamically -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="existingProductModal" tabindex="-1" role="dialog" aria-labelledby="existingProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="existingProductModalLabel">Select Existing Product</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div id="existingProductList">
                                    <!-- Existing products will be loaded here dynamically -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="packageInfoModal" tabindex="-1" role="dialog" aria-labelledby="packageInfoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="packageInfoModalLabel">Package Information</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="json-container">
                                    <pre><code id="packageInfoContent" class="language-json" style="white-space: pre-wrap;"></code></pre>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0/themes/prism.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0/components/prism-json.min.js"></script>

<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    const licenseNumber = '{{ $licenseNumber }}';
    const username = '-wEp3jabxlsIYVhclDdbdRmMvazx557w7P5TEeYN2OeAOyWV';
    const vendorKey = '{{ $apiKey }}';
    const authHeader = "Basic " + btoa(username + ":" + vendorKey);
    const existingPackageIds = new Set(@json($existingPackageIds));
    let allProducts = [];
    let currentPage = 1;
    const itemsPerPage = 6;

    async function fetchMetrcData(url) {
        const response = await fetch(url, { headers: { "Authorization": authHeader } });
        if (!response.ok) throw new Error(`Error fetching ${url}`);
        const data = await response.json();
        return data.Data || [];
    }

    async function getAllPackages() {
        try {
            const [packages, transfers] = await Promise.all([
                fetchMetrcData(`https://api-or.metrc.com/packages/v2/active?licenseNumber=${encodeURIComponent(licenseNumber)}`),
                fetchMetrcData(`https://api-or.metrc.com/transfers/v2/incoming?licenseNumber=${encodeURIComponent(licenseNumber)}`)
            ]);

            const manifestMap = {};
            const transferMap = {};
            const unmatchedPackages = [];

            transfers.forEach(t => {
                const manifest = (t.ManifestNumber || '').trim();
                if (manifest) {
                    transferMap[manifest] = t;
                    manifestMap[manifest] = [];
                }
            });

            packages.forEach(pkg => {
                const manifest = (pkg.ReceivedFromManifestNumber || '').trim();
                if (manifest && manifestMap[manifest]) {
                    manifestMap[manifest].push(pkg);
                } else {
                    unmatchedPackages.push(pkg);
                }
            });

            renderManifestCards(manifestMap, transferMap, unmatchedPackages);
        } catch (error) {
            console.error("Error retrieving Metrc data:", error);
            alert("Error retrieving packages. Check console for details.");
        }
    }

    function renderManifestCards(manifestMap, transferMap, unmatchedPackages) {
        const container = $('#productContainer');
        container.empty();

        Object.entries(manifestMap).forEach(([manifestNumber, packages]) => {
            if (!packages.length) return;

            const manifestData = transferMap[manifestNumber];
            const card = $(`
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manifest #${manifestNumber}</h5>
                        <p>${packages.length} package(s) linked</p>
                        <p><strong>From:</strong> ${manifestData?.ShipperFacilityName || 'Unknown'}</p>
                        <button class="btn btn-primary view-manifest" data-manifest="${manifestNumber}">View Packages</button>
                    </div>
                </div>
            `);
            container.append(card);
        });

        if (unmatchedPackages.length > 0) {
            const reconCard = $(`
                <div class="card border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Metrc Reconciliation</h5>
                        <p>${unmatchedPackages.length} unlinked package(s) need manual input</p>
                        <button class="btn btn-warning view-reconciliation">Review Packages</button>
                    </div>
                </div>
            `);
            container.append(reconCard);

            $('.view-reconciliation').on('click', function () {
                populateProductForms(unmatchedPackages);
            });
        }

        $('.view-manifest').on('click', function () {
            const manifest = $(this).data('manifest');
            populateProductForms(manifestMap[manifest]);
        });
    }

    function populateProductForms(packages) {
        const container = $('#productContainer');
        container.empty();
        allProducts = [];

        container.append(`<div class="text-left mb-3"><button class="btn btn-secondary" id="backToManifests">← Back to Manifests</button></div>`);
        $('#backToManifests').on('click', getAllPackages);

        packages.forEach((pkg, index) => {
            if (pkg.IsTradeSample || existingPackageIds.has(pkg.Label)) return;

            const template = $('#productTemplate').html();
            const productHtml = template.replace(/__INDEX__/g, index);
            const $product = $(productHtml);

            findClosestCategoryName(pkg.Item.ProductCategoryName).done(function (closestCategory) {
                if (closestCategory) {
                    $product.find('.product-category').val(closestCategory.id);
                }
            });

            $product.find('.product-metrc-package').val(JSON.stringify(pkg));
            $product.find('.product-name').val(pkg.Item.Name);
            $product.find('.product-display-name').val(pkg.Item.Name);
            $product.find('.product-description').val(pkg.Item.Description || '');
            $product.find('.product-original-price').val(pkg.Item.UnitPrice || '0.00');
            $product.find('.product-original-cost').val(pkg.Item.UnitCost || '0.00');
            $product.find('.product-quantity').val(pkg.Quantity || 0);
            $product.find('.product-sku').val(pkg.Item.ItemNumber || '');
            $product.find('.product-weight').val(pkg.Item.UnitWeight || '0');
            $product.find('.product-thc-content').val(pkg.Item.UnitThcContent || '0');
            $product.find('.product-cbd-content').val(pkg.Item.UnitCbdContent || '0');
            $product.find('.product-discount-tiers').val(pkg.Item.DiscountTiers || '');

            if (pkg.Item.ImageUrl) {
                $product.find('.product-image-preview').attr('src', pkg.Item.ImageUrl).show();
            }

            $product.find('.package-id-display').html(`<strong>Package ID:</strong> ${pkg.Label}`);
            $product.find('.product-name-display').text(pkg.Item.Name);

            $product.append(`<input type="hidden" name="products[${index}][original_name]" value="${pkg.Item.Name}">`);
            $product.append(`<input type="hidden" name="products[${index}][Label]" value="${pkg.Label}">`);
            $product.append(`<input type="hidden" name="products[${index}][quantity]" value="${pkg.Quantity}">`);
            $product.append(`<input type="hidden" name="products[${index}][metrc_data]" value='${JSON.stringify(pkg)}'>`);

            allProducts.push($product);
        });

        renderPage(currentPage);
    }

    function renderPage(page) {
        const container = $('#productContainer');
        container.find('.product-template').remove();

        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageProducts = allProducts.slice(start, end);
        pageProducts.forEach($product => container.append($product));
        $('#pageInfo').text(`Page ${page} of ${Math.ceil(allProducts.length / itemsPerPage)}`);
        $('#prevPage').prop('disabled', page === 1);
        $('#nextPage').prop('disabled', page === Math.ceil(allProducts.length / itemsPerPage));
    }

    function findClosestCategoryName(categoryName) {
        return $.ajax({
            url: '{{ route("categories.closest") }}',
            type: 'POST',
            data: { category_name: categoryName },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
    }

    $('#prevPage').on('click', function () {
        if (currentPage > 1) {
            currentPage--;
            renderPage(currentPage);
        }
    });

    $('#nextPage').on('click', function () {
        const totalPages = Math.ceil(allProducts.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderPage(currentPage);
        }
    });

    $('#searchLabel').on('input', function () {
        const label = $(this).val().trim();
        if (label) {
            const filtered = allProducts.filter($p => $p.find('.package-id-display').text().includes(label));
            $('#productContainer').empty().append(filtered);
        } else {
            renderPage(currentPage);
        }
    });

    if (licenseNumber) {
        getAllPackages();
    } else {
        alert('No license number found. Please contact the administrator.');
    }
});
let nextIndex = () => {
  // count how many product‐cards already exist and use that as your start
  return $('#productContainer .card').length;
};

$('#addNonMetrc').on('click', function() {
  const start = nextIndex();
  for (let i = 0; i < 6; i++) {
    // clone the template and stamp in a fresh index
    const raw = $('#productTemplate').html();
    const html = raw.replace(/__INDEX__/g, start + i);
    const $card = $(html);

    // strip out any Metrc‐specific UI
    $card.find('.package-id-display, .view-package-info, input.product-metrc-package').remove();

    // append it
    $('#productContainer').append($card);
  }
});

</script>
@endsection
