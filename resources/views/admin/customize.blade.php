@extends('layouts.customize_layout')

@section('content')
@php
    $customContent = \App\Models\CustomizableContent::getContentForOrganizationBrands(auth()->user()->organization_id);
    $primaryColor = $customContent['primary_color'] ?? '#007bff';
    $secondaryColor = $customContent['secondary_color'] ?? '#6c757d';
    $textColor = $customContent['text_color'] ?? '#333333';
    $backgroundColor = $customContent['background_color'] ?? '#ffffff';
    $cardBgColor = $customContent['product_card_bg'] ?? '#ffffff';
    $buttonColor = $customContent['button_color'] ?? '#007bff';
    $buttonTextColor = $customContent['button_text_color'] ?? '#ffffff';
    $fontFamily = $customContent['font_family'] ?? "'Arial', sans-serif";
    $baseFontSize = $customContent['base_font_size'] ?? '16px';
    $isPreview = true;
@endphp

<div class="customize-wrapper">
    <div id="customize-container">
        <div id="preview-container">
            <div id="custom-container" class="container-fluid custom-container" style="font-family: {{ $fontFamily }}; font-size: {{ $baseFontSize }}; color: {{ $textColor }}; background-color: {{ $backgroundColor }};">
                <!-- Back to Organizations Button -->
                <div id="back-button-container" class="row mb-3">
                    <div class="col-12">
                        <a id="back-to-organizations" href="#" class="btn btn-secondary back-button" style="background-color: {{ $secondaryColor }}; color: {{ $buttonTextColor }};">
                            <i class="fas fa-arrow-left"></i> Back to Organizations
                        </a>
                    </div>
                </div>

                <!-- Organization Header -->
                <div id="org-header" class="row mb-4 organization-header" style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }}); padding: 2rem; border-radius: 10px; color: #ffffff;">
                    <div id="org-logo" class="col-md-3 text-center">
                        <div id="org-placeholder-logo" class="placeholder-logo" style="width: 200px; height: 200px; background-color: #f0f0f0; color: #999; font-size: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">O</div>
                    </div>
                    <div id="org-info" class="col-md-9">
                        <h1 id="org-name" class="organization-name" style="font-size: 2.5rem; font-weight: bold;">Organization Name</h1>
                        <p id="org-description" class="organization-description" style="font-size: 1.1rem;">Organization description goes here.</p>
                    </div>
                </div>

                <!-- Featured Products Section -->
                <div id="featured-products" class="row mb-4">
                    <div class="col-12">
                        <h2 id="featured-products-title" class="section-title" style="color: {{ $primaryColor }}; font-size: 2rem;">Featured Products</h2>
                    </div>
                    <div id="featured-product-1" class="col-lg-3 col-md-4 mb-4">
                        <!-- Sample product card -->
                        <div class="custom-card" style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); background: linear-gradient(135deg, {{ $cardBgColor }}, #ffffff);">
                            <div style="height: 200px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #999;">Product Image</span>
                            </div>
                            <div style="padding: 1rem;">
                                <h3 class="custom-card-title" style="font-size: 1.3rem; color: {{ $primaryColor }};">Product 1</h3>
                                <p class="custom-card-text" style="font-size: 1rem;">Product description</p>
                                <p class="price-tier" style="font-weight: bold; color: {{ $secondaryColor }};">$99.99</p>
                                <button class="btn custom-button" style="background-color: {{ $buttonColor }}; color: {{ $buttonTextColor }};">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products by Brand Section -->
                <div id="brand-1" class="row mb-4">
                    <div class="col-12">
                        <h2 id="brand-title-1" class="section-title" style="color: {{ $primaryColor }}; font-size: 2rem;">Brand Name</h2>
                        <p id="brand-description-1">Brand description goes here.</p>
                    </div>
                    <div id="brand-product-1" class="col-lg-3 col-md-4 mb-4">
                        <!-- Sample product card (same structure as featured product) -->
                        <div class="custom-card" style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); background: linear-gradient(135deg, {{ $cardBgColor }}, #ffffff);">
                            <!-- ... (same content as featured product card) ... -->
                        </div>
                    </div>
                </div>

                <!-- Unbranded Products Section -->
                <div id="unbranded-products" class="row mb-4">
                    <div class="col-12">
                        <h2 id="unbranded-products-title" class="section-title" style="color: {{ $primaryColor }}; font-size: 2rem;">Other Products</h2>
                        <p id="unbranded-products-description">Products without a specific brand.</p>
                    </div>
                    <div id="unbranded-product-1" class="col-lg-3 col-md-4 mb-4">
                        <!-- Sample product card (same structure as featured product) -->
                        <div class="custom-card" style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); background: linear-gradient(135deg, {{ $cardBgColor }}, #ffffff);">
                            <!-- ... (same content as featured product card) ... -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="customize-panel">
            <h2>Customize</h2>
            <div id="selected-element-info"></div>
            <div id="customize-options"></div>
            <div class="form-group mt-3">
        <label for="logo-upload">Upload Logo</label>
        <input type="file" id="logo-upload" name="logo" accept="image/*" class="form-control-file">
    </div>
    
            <div class="form-group">
                <label for="custom-css-textarea">Custom CSS</label>
                <textarea id="custom-css-textarea" class="form-control" rows="4">{{ $customContent['custom_css'] ?? '' }}</textarea>
            </div>
            <button id="save-changes" class="btn btn-primary mt-3">Save Changes</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .customize-wrapper {
        display: flex;
        height: calc(100vh - 90px);
    }
    #customize-container {
        display: flex;
        width: 100%;
    }
    #preview-container {
        flex: 0 0 75%;
        padding: 20px;
        overflow-y: auto;
    }
    #customize-panel {
        flex: 0 0 25%;
        background-color: #f8f9fa;
        border-left: 1px solid #dee2e6;
        padding: 20px;
        overflow-y: auto;
    }
    .product-list, .brand-list {
        display: flex;
        flex-wrap: wrap;
    }
    .product-item, .brand-item {
        width: 45%;
        margin: 10px;
        padding: 10px;
        border: 1px solid #ddd;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const customizeOptions = $('#customize-options');
    const selectedElementInfo = $('#selected-element-info');
    let selectedElement = null;

    $('#preview-container').on('click', '*', function(e) {
        e.preventDefault();
        e.stopPropagation();
        selectedElement = this;
        updateSelectedElementInfo();
        populateCustomizationOptions();
    });

    function updateSelectedElementInfo() {
        selectedElementInfo.html(`
            <h3>Selected Element</h3>
            <p>Tag: ${selectedElement.tagName}</p>
            <p>ID: ${selectedElement.id}</p>
            <p>Classes: ${selectedElement.className}</p>
            <p>Text: ${selectedElement.textContent}</p>
        `);
    }
    function getCustomizations() {
        return {
            custom_styles: $('#custom-css-textarea').val() || '/* Add your custom CSS here */',
            primary_color: $('#primary-color-picker').val() || $('#org-header').css('background-color') || '#007bff',
            secondary_color: $('#secondary-color-picker').val() || $('#org-header').css('background-color') || '#6c757d',
            text_color: $('#text-color-picker').val() || $('#custom-container').css('color') || '#333333',
            background_color: $('#bg-color-picker').val() || $('#custom-container').css('background-color') || '#ffffff',
            product_card_bg: $('.custom-card').css('background-color') || '#ffffff',
            button_color: $('.custom-button').css('background-color') || '#007bff',
            button_text_color: $('.custom-button').css('color') || '#ffffff',
            font_family: $('#custom-container').css('font-family') || "'Arial', sans-serif",
            base_font_size: $('#custom-container').css('font-size') || '16px'
        };
    }

    function populateCustomizationOptions() {
        customizeOptions.empty();
        
        // Common options for all elements
        customizeOptions.append(`
            <div class="form-group">
                <label for="text-color-picker">Text Color</label>
                <input type="color" id="text-color-picker" value="${rgbToHex(getComputedStyle(selectedElement).color)}">
            </div>
            <div class="form-group">
                <label for="font-size">Font Size</label>
                <input type="number" id="font-size" value="${parseInt(getComputedStyle(selectedElement).fontSize)}" min="8" max="72"> px
            </div>
        `);

        // Background color for elements with background
        if (selectedElement.id === 'org-header' || selectedElement.classList.contains('custom-card') || selectedElement.id === 'custom-container') {
            customizeOptions.append(`
                <div class="form-group">
                    <label for="bg-color-picker">Background Color</label>
                    <input type="color" id="bg-color-picker" value="${rgbToHex(getComputedStyle(selectedElement).backgroundColor)}">
                </div>
            `);
        }

        // Gradient colors for organization header
        if (selectedElement.id === 'org-header') {
            const gradientColors = getComputedStyle(selectedElement).backgroundImage.match(/rgb\(.*?\)/g);
            customizeOptions.append(`
                <div class="form-group">
                    <label for="primary-color-picker">Primary Color (Gradient)</label>
                    <input type="color" id="primary-color-picker" value="${rgbToHex(gradientColors[0])}">
                </div>
                <div class="form-group">
                    <label for="secondary-color-picker">Secondary Color (Gradient)</label>
                    <input type="color" id="secondary-color-picker" value="${rgbToHex(gradientColors[1])}">
                </div>
            `);
        }

        // Button color for custom buttons
        if (selectedElement.classList.contains('custom-button')) {
            customizeOptions.append(`
                <div class="form-group">
                    <label for="button-color-picker">Button Color</label>
                    <input type="color" id="button-color-picker" value="${rgbToHex(getComputedStyle(selectedElement).backgroundColor)}">
                </div>
                <div class="form-group">
                    <label for="button-text-color-picker">Button Text Color</label>
                    <input type="color" id="button-text-color-picker" value="${rgbToHex(getComputedStyle(selectedElement).color)}">
                </div>
            `);
        }

        // Font family for the entire container
        if (selectedElement.id === 'custom-container') {
            customizeOptions.append(`
                <div class="form-group">
                    <label for="font-family">Font Family</label>
                    <select id="font-family">
                        <option value="Arial, sans-serif">Arial</option>
                        <option value="Helvetica, sans-serif">Helvetica</option>
                        <option value="Times New Roman, serif">Times New Roman</option>
                        <option value="Courier, monospace">Courier</option>
                    </select>
                </div>
            `);
        }

        addCustomizationEventListeners();
    }

    function addCustomizationEventListeners() {
        $('#text-color-picker').on('change', function() {
            $(selectedElement).css('color', this.value);
        });

        $('#bg-color-picker').on('change', function() {
            $(selectedElement).css('background-color', this.value);
        });

        $('#primary-color-picker, #secondary-color-picker').on('change', function() {
            const primaryColor = $('#primary-color-picker').val();
            const secondaryColor = $('#secondary-color-picker').val();
            $(selectedElement).css('background', `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`);
        });

        $('#font-size').on('change', function() {
            $(selectedElement).css('font-size', this.value + 'px');
        });

        $('#button-color-picker').on('change', function() {
            $(selectedElement).css('background-color', this.value);
        });

        $('#button-text-color-picker').on('change', function() {
            $(selectedElement).css('color', this.value);
        });

        $('#font-family').on('change', function() {
            $('#custom-container').css('font-family', this.value);
        });
    }

    // Handle logo upload
    $('#logo-upload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#org-placeholder-logo').html(`<img src="${e.target.result}" alt="Organization Logo" style="width: 100%; height: 100%; object-fit: contain;">`);
            }
            reader.readAsDataURL(file);
        }
    });
    $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
$('#save-changes').on('click', function() {
    const customizations = getCustomizations();
    const formData = new FormData();

    formData.append('organization_id', '{{ auth()->user()->organization_id }}');
    formData.append('page_name', 'organization-brands');

    for (let key in customizations) {
        formData.append(`content[${key}]`, customizations[key]);
    }

    const imageFile = $('#logo-upload')[0].files[0];
    if (imageFile) {
        // Basic file type validation
        if (!['image/jpeg', 'image/png', 'image/gif'].includes(imageFile.type)) {
            alert('Please upload a valid image file (JPEG, PNG, or GIF)');
            return;
        }
        formData.append('image', imageFile);
    }

    // Show loading indicator
    $('#save-changes').prop('disabled', true).text('Saving...');

    $.ajax({
        url: '{{ route("wholesale.admin.customize.update", ["pageName" => "organization-brands"]) }}',
        method: 'PUT',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            alert('Customizations and image saved successfully!');
            // Update UI to reflect changes if necessary
        },
        error: function(xhr, status, error) {
            console.error('Error details:', xhr.responseText);
            alert('Error saving customizations and image. Please try again.');
            // You might want to send this error to your server for logging
        },
        complete: function() {
            // Re-enable the save button
            $('#save-changes').prop('disabled', false).text('Save Changes');
        }
    });
});



    // Utility function to convert RGB to HEX
    function rgbToHex(color) {
        if (/^#[0-9A-F]{6}$/i.test(color)) return color;

        if (color.startsWith('rgba')) {
            const rgba = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);
            if (rgba) {
                if (rgba[4] && parseFloat(rgba[4]) === 0) {
                    return '#FFFFFF';
                }
                return `#${((1 << 24) + (parseInt(rgba[1]) << 16) + (parseInt(rgba[2]) << 8) + parseInt(rgba[3])).toString(16).slice(1)}`;
            }
        } else {
            const rgb = color.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (rgb) {
                return `#${((1 << 24) + (parseInt(rgb[1]) << 16) + (parseInt(rgb[2]) << 8) + parseInt(rgb[3])).toString(16).slice(1)}`;
            }
        }

        return '#000000';
    }
});
</script>
@endpush