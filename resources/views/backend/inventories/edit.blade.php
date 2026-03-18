@extends('layouts.app')

@section('content')
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@php use Illuminate\Support\Str; @endphp

<div class="wrapper wrapper-content animated fadeInRight" style="min-height:100vh;">
  <div class="container-fluid px-5">

    {{-- Flash + validation feedback --}}
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <strong>Couldn’t save:</strong>
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="row justify-content-center align-items-center" style="min-height:100vh;">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="custom-edit-card">
          <h5 class="text-center mb-4">Edit Inventory</h5>

          @php
            // Fetch discount tiers from the current user's organization
            $org = auth()->user()->organization;
            $rawTiers = $org->discount_tiers ?? null;
            $tiers = [];
            if (!empty($rawTiers)) {
                $tiers = is_string($rawTiers)
                         ? json_decode($rawTiers, true) ?? []
                         : (is_array($rawTiers) ? $rawTiers : []);
            }

            // Determine selected category and if it's flower
            $selectedCatId = old('category_id', $inventory->category_id);
            $selectedCategory = $categories->firstWhere('id', $selectedCatId);
            $isFlower = optional($selectedCategory)->slug === 'flower';

            // Checkbox old() handling with 0/1
            $hasLeaf = Str::endsWith(old('name', $inventory->name), ':gls:');
            $addLeafWas = old('add_leaf', $hasLeaf ? '1' : '0');
            $applyGroupWas = old('apply_name_to_group', '0');
          @endphp

          <form id="inventoryForm" action="{{ route('inventories.update', $inventory->id) }}" method="POST" enctype="multipart/form-data">
            {{-- prefer list url saved in controller; fall back to previous --}}
            <input type="hidden" name="return_url" value="{{ session('inventory_list_url') ?? url()->previous() }}">
            @csrf @method('PUT')

            {{-- Image --}}
            <div class="text-center mb-4">
              @php $img = public_path("uploads/inventories/{$inventory->id}.jpg"); @endphp
              <label for="fileInput" class="mb-2 d-block">
                <img src="{{ file_exists($img) ? asset("uploads/inventories/{$inventory->id}.jpg") : asset('herbs/noimage.jpg') }}"
                     class="rounded-circle" style="width:160px;height:160px;">
              </label>
              <input type="file" id="fileInput" name="file" class="form-control-file mx-auto" style="width:auto;">
            </div>

            {{-- Name --}}
            <div class="form-group">
              <label for="name" class="font-weight-bold">Inventory Name</label>
              <input type="text" id="name" name="name" class="form-control form-control-lg"
                     value="{{ old('name', preg_replace('/\s*<img[^>]+>\s*$/i', ' :gls:', $inventory->name)) }}" required>
            </div>

            {{-- Add Leaf (hidden 0 + checkbox value 1) --}}
            <input type="hidden" name="add_leaf" value="0">
            <div class="form-group form-check">
              <input type="checkbox" id="addLeaf" name="add_leaf" class="form-check-input" value="1"
                     {{ $addLeafWas == '1' ? 'checked' : '' }}>
              <label for="addLeaf" class="form-check-label">Append Green Leaf Emoji</label>
            </div>

            {{-- Apply name to group (hidden 0 + checkbox value 1) --}}
            <input type="hidden" name="apply_name_to_group" value="0">
            <div class="form-group form-check">
              <input type="checkbox" id="apply_name_to_group" name="apply_name_to_group" class="form-check-input" value="1"
                     {{ $applyGroupWas == '1' ? 'checked' : '' }}>
              <label for="apply_name_to_group" class="form-check-label">
                Apply this name to all items with the same base name (this organization)
              </label>
              <small class="form-text text-muted">
                “Base name” ignores a trailing <code>:gls:</code> or a trailing image tag.
              </small>
            </div>

            {{-- Category --}}
            <div class="form-group">
              <label for="category_id" class="font-weight-bold">Category</label>
              <select id="category_id" name="category_id" class="form-control form-control-lg" required>
                <option value="">-- Select Category --</option>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ $selectedCatId == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Price or Tier --}}
            <div class="form-group" id="tier_section" style="display: {{ $isFlower ? 'block' : 'none' }};">
              <label for="selected_discount_tier" class="font-weight-bold">Discount Tier</label>
              <select id="selected_discount_tier" name="selected_discount_tier" class="form-control form-control-lg">
                <option value="">-- None --</option>
                @foreach($tiers as $tier)
                  <option value="{{ $tier['name'] }}" {{ old('selected_discount_tier', $inventory->selected_discount_tier) === $tier['name'] ? 'selected' : '' }}>
                    {{ $tier['name'] }} ({{ data_get($tier, 'pricing.0.price', '–') }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group" id="price_section" style="display: {{ $isFlower ? 'none' : 'block' }};">
              <label for="original_price" class="font-weight-bold">Price</label>
              <input type="number" step="0.01" id="original_price" name="original_price" class="form-control form-control-lg"
                     value="{{ old('original_price', $inventory->original_price) }}">
            </div>

            {{-- Cost --}}
            <div class="form-group">
              <label for="original_cost" class="font-weight-bold">Cost</label>
              <input type="number" step="0.01" id="original_cost" name="original_cost" class="form-control form-control-lg"
                     value="{{ old('original_cost', $inventory->original_cost) }}">
            </div>

            {{-- Package ID and SKU --}}
            <div class="form-group">
              <label for="Label" class="font-weight-bold">Package ID</label>
              <input type="text" id="Label" name="Label" class="form-control form-control-lg"
                     value="{{ old('Label', $inventory->Label) }}">
            </div>

            <div class="form-group">
              <label for="sku" class="font-weight-bold">SKU / Barcode</label>
              <div class="input-group">
                <input type="text" id="sku" name="sku"
                       class="form-control form-control-lg sku-auto"
                       value="{{ old('sku', $inventory->sku) }}"
                       oninput="resizeSkuInput(this)">
                <div class="input-group-append">
                  <button type="button" id="generateSku" class="btn btn-outline-secondary">Generate</button>
                </div>
              </div>
            </div>

            {{-- Actions --}}
            <div class="form-group text-right">
              <a
                href="#"
                class="btn btn-secondary btn-lg mr-2"
                onclick="event.preventDefault(); window.history.go(-1);"
              >
                Cancel
              </a>
              <button type="submit" class="btn btn-primary btn-lg">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
  $(function() {
    const LEAF = ':gls:';
    const leafRegex = /\s*:gls:\s*$/i;
    const trailingImgRegex = /\s*<img[^>]+>\s*$/i;

    function cleanName(val) {
      return (val || '')
        .replace(trailingImgRegex, '')
        .replace(leafRegex, '')
        .trim();
    }

    function appendLeaf(val) {
      const base = cleanName(val);
      return base ? (base + ' ' + LEAF) : LEAF;
    }

    function toggleSections() {
      const isFlower = $('#category_id').find('option:selected').text().trim().toLowerCase() === 'flower';
      $('#tier_section').toggle(isFlower);
      $('#price_section').toggle(!isFlower);
    }

    // Append/remove :gls: when checkbox changes
    $('#addLeaf').on('change', function() {
      let current = $('#name').val();
      if (this.checked) {
        if (!leafRegex.test(current)) {
          $('#name').val(appendLeaf(current));
        }
      } else {
        $('#name').val(cleanName(current));
      }
    });

    // Ensure consistency before submit
    $('#inventoryForm').on('submit', function() {
      let current = $('#name').val();
      if ($('#addLeaf').is(':checked')) {
        if (!leafRegex.test(current)) {
          $('#name').val(appendLeaf(current));
        }
      } else {
        $('#name').val(cleanName(current));
      }
    });

    // On initial load
    $('#category_id').on('change', toggleSections);
    toggleSections();
  });

  function resizeSkuInput(el) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const style = window.getComputedStyle(el);
    ctx.font = style.fontSize + " " + style.fontFamily;
    const text = el.value || el.placeholder || '';
    const width = ctx.measureText(text).width + 30; // padding
    el.style.width = width + 'px';
  }

  document.addEventListener('DOMContentLoaded', function() {
    const skuInput = document.getElementById('sku');
    if (skuInput) resizeSkuInput(skuInput);

    skuInput?.addEventListener('input', function() {
      resizeSkuInput(this);
    });

    // Generate random 9-char SKU
    $('#generateSku').on('click', function() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let sku = '';
      for (let i = 0; i < 9; i++) {
        sku += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      $('#sku').val(sku);
      resizeSkuInput(document.getElementById('sku'));
    });
  });
</script>
@endpush
