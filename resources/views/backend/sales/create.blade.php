{{-- resources/views/sales/create.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Str;

    // ---------- SETTINGS ----------
    $rawTiers = optional(auth()->user()->organization)->discount_tiers ?? setting_by_key('discount_tiers');
    $discountTiers = is_string($rawTiers) ? (json_decode($rawTiers, true) ?: []) : ($rawTiers ?: []);
    $currency   = setting_by_key('currency')   ?? '$';
    $countyTax  = (float)(setting_by_key('county_tax') ?? 0);
    $cityTax    = (float)(setting_by_key('CityTax')    ?? 0);
    $stateTax   = (float)(setting_by_key('StateTax')   ?? 0);
    $taxPercent = $countyTax + $cityTax + $stateTax;

    // business info for receipt header
    $bizName  = setting_by_key('business_name') ?: (optional(auth()->user()->organization)->name ?? '');
    $bizPhone = setting_by_key('company_phone') ?: '';
    $bizAddr  = trim(implode(' ', array_filter([
        setting_by_key('company_address') ?: '',
        setting_by_key('company_city') ?: '',
        setting_by_key('company_state') ?: '',
        setting_by_key('company_zip') ?: '',
    ])));
    $packagerLicense = optional(auth()->user()->branch)->license
                    ?? optional(optional(auth()->user())->organization)->license
                    ?? setting_by_key('company_license')
                    ?? '';

    $priceOf = function($raw){
        if (is_numeric($raw)) return (float)$raw;
        if (is_string($raw)) {
            $d = json_decode($raw, true);
            if (json_last_error()===JSON_ERROR_NONE) {
                return is_array($d)?(float)reset($d):(float)$d;
            }
            return (float)$raw;
        }
        if (is_array($raw)) return (float)reset($raw);
        return 0.0;
    };

    // Normalize main + (optional) hold inventories (supports paginator or plain array/collection)
    $mainRaw = ($inventories ?? []);
    $mainItems = ($mainRaw instanceof \Illuminate\Pagination\AbstractPaginator)
        ? $mainRaw->items()
        : $mainRaw;

    $holdRaw = isset($holdInventories) ? $holdInventories : (isset($hold_inventories) ? $hold_inventories : []);
    $holdItems = ($holdRaw instanceof \Illuminate\Pagination\AbstractPaginator)
        ? $holdRaw->items()
        : $holdRaw;

    $allItems = collect($mainItems)->concat($holdItems ?? []);

    // Build a safe, light-weight inventory seed for client search (includes hold items)
    $invSeed = $allItems->map(function($i) use ($priceOf, $discountTiers){
        if (is_array($i)) { $inventory = (object)$i; }
        elseif (is_object($i)) { $inventory = $i; }
        else { return null; }

        $cat     = data_get($inventory, 'categoryDetail');
        $catName = (string) data_get($cat, 'name', '');
        $isTaxExempt = in_array(Str::lower($catName), ['accessories','apparel','hemp']);

        $selKey   = data_get($inventory, 'selected_discount_tier');
        $tierName = null;
        if ($selKey) {
            if (isset($discountTiers[$selKey]['name'])) {
                $tierName = $discountTiers[$selKey]['name'];
            } else {
                $match = collect($discountTiers)->first(fn($t)=>data_get($t,'name')===$selKey);
                $tierName = $match['name'] ?? null;
            }
        }

        $labsCol = data_get($inventory, 'metrc_full_labs');
        $labs = $labsCol instanceof \Illuminate\Support\Collection ? $labsCol->values()->all()
              : (is_array($labsCol) ? array_values($labsCol) : []);

        return [
            'id'        => data_get($inventory, 'id'),
            'name'      => (string) data_get($inventory, 'name', ''),
            'sku'       => (string) data_get($inventory, 'sku', ''), // kept for barcode scans (not displayed)
            'label'     => (string) data_get($inventory, 'Label', ''),
            'image_url' => (string) data_get($inventory, 'image_url', ''),
            'price'     => (float) $priceOf(data_get($inventory, 'original_price')),
            'available' => (float) data_get($inventory, 'storeQty', 0),
            'inventory_type' => (string) data_get($inventory, 'inventory_type', 'inventories'), // flag holds
            'category_id'    => data_get($cat, 'id'),
            'category_name'  => $catName,
            'limit_category' => data_get($cat, 'sales_limit_category'),
            'limit_value'    => (float) data_get($cat, 'sales_limit', 0),
            'tax_exempt'     => $isTaxExempt ? 1 : 0,
            'selected_discount_tier'      => $selKey,
            'selected_discount_tier_name' => $tierName,
            'metrc'     => data_get($inventory, 'metrc_package'),
            'labs'      => $labs,
        ];
    })
    ->filter()
    // make unique by id + label + inventory_type to avoid dupes across lists
    ->unique(function($x){ return ($x['id'] ?? '').'|'.($x['label'] ?? '').'|'.($x['inventory_type'] ?? ''); })
    ->values();
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="{{ asset('assets/css/plugins/toastr/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}">
<link rel="stylesheet" href="{{ asset('assets/numpad/jquery.numpad.css') }}">

<style>
:root{ --content-max: 1280px; }
.wrapper-content { display:flex; justify-content:center; }
.cart-shell { width:100%; max-width:var(--content-max); }

/* === POS two-column layout (Inventory left, Cart right) === */
.pos-grid{
  display:grid;
  grid-template-columns: 440px 1fr;
  gap:16px;
  align-items:start;
}
@media (max-width: 1100px){
  .pos-grid{ grid-template-columns: 1fr; }
}

/* Top search (left column) */
.top-search-container { position:sticky; top:0; z-index:20; background:#fff; padding-top:8px; }
.search-bar { width:100%; padding:.75rem 2.5rem .75rem 1rem; font-size:1.2rem; border:1px solid #ddd; border-radius:12px; box-shadow:0 2px 4px rgba(0,0,0,.05); }
.clear-btn { position:absolute; right:.5rem; width:2.6rem; height:2.6rem; top:.25rem; display:inline-flex; align-items:center; justify-content:center; background:rgba(243,115,115,.36); border:1px solid #ddd; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,.1); color:#666; font-size:1.2rem; opacity:.9; }
.clear-btn:hover{opacity:1}

/* Search results */
.results-wrap { margin-top:8px; }
.result-card.inventory-card { display:flex; align-items:flex-start; gap:12px; padding:10px 12px; margin-bottom:10px; background:#fff; border:1px solid #e6e6e6; border-radius:14px; width:100%; }
.result-img { flex:0 0 64px; width:64px; height:64px; border-radius:10px; overflow:hidden; background:#f6f8fb; display:flex; align-items:center; justify-content:center; }
.result-img img{width:100%;height:100%;object-fit:cover}
.result-body{ flex:1; min-width:0; }
.result-name{ font-weight:800; font-size:1.05rem; margin:0 0 4px; white-space:normal; overflow-wrap:anywhere; }
.pkg-short{ margin-left:6px; font-weight:700; color:#6b7280; }
.badge-hold{ background:#fff4e5; color:#a46307; border:1px solid #ffd8a8; font-weight:700; padding:2px 6px; border-radius:6px; }
.badge-oos{ background:#ffe5e5; color:#a60c0c; border:1px solid #ffc9c9; font-weight:700; padding:2px 6px; border-radius:6px; }

.result-meta{ display:flex; gap:10px; flex-wrap:wrap; font-size:.95rem; color:#555; }
.result-meta .field{ white-space:nowrap; }
.result-actions{ display:flex; align-items:center; gap:6px; }
.badge-live{ background:#eef7ff; color:#0b72e7; border:1px solid #cfe6ff; font-weight:700; padding:2px 6px; border-radius:6px; }

/* Left/right panels */
.inventory-column .ibox,
.cart-column .ibox { border:1px solid #e6ecf4; border-radius:12px; overflow:hidden; }
.inventory-column .ibox-title,
.cart-column .ibox-title { position:sticky; top:0; z-index:15; background:#fff; border-bottom:1px solid #eaecef; padding:10px 12px; }
.inventory-column .ibox-content { padding:8px 12px; }
.cart-column .ibox-content { padding:8px 12px; }

/* Make content scrollable within viewport */
@media (min-width: 768px) {
  .inventory-column .ibox-content { max-height: calc(100vh - 170px); overflow:auto; }
  .cart-column .ibox-content { max-height: calc(100vh - 230px); overflow:auto; }
}

/* Cart */
.cart-column{ width:100%; }
.cart-table-wrap table{ width:100%; table-layout:fixed; }
#CartHTML tr{ border-bottom:1px solid #f0f0f0; }
#CartHTML td{ vertical-align:middle!important; padding:6px 4px!important; font-size:1rem!important; line-height:1.35; }
#CartHTML td:first-child{ width:52%; white-space:normal; overflow-wrap:anywhere; word-break:break-word; }
#CartHTML .qty-cell{ width:20%; }
#CartHTML .price-per{ width:12%; text-align:right; }
#CartHTML .line-after{ width:12%; text-align:right; }
#CartHTML td:last-child{ width:76px; text-align:right; white-space:nowrap; }
.cart-qty{ width:100%!important; padding:4px 6px!important; }
.cart-qty-flower{ text-align:right; }
.unit-readout{ display:block; font-size:2em; margin-top:2px; white-space:nowrap; }

/* Footer */
.panel-footer.green-bg{ background:#222a38!important; color:#fff!important; border-radius:0 0 12px 12px; border-top:3px solid #35be5c; padding:12px 14px!important; }
.panel-footer.green-bg .btn{ display:block; width:100%; }
.panel-footer.green-bg .btn + .btn{ margin-top:8px; }

/* Limits/progress */
#salesProgress .mb-2{margin-bottom:4px!important} #salesProgress small{display:block;margin-bottom:2px}

/* Discount reason list */
.reason-row{ display:flex; gap:6px; margin-bottom:6px; }
.reason-row input{ flex:1; }

/* Accessibility helpers */
.visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}

/* --- Cross-Bootstrap modal fallbacks (BS3/4/5) --- */
.modal { display:none; }
.modal.show, .modal.in { display:block !important; opacity:1 !important; }
.modal.fade { opacity:0; }
.modal.fade.show, .modal.fade.in { opacity:1 !important; }
.modal-backdrop { position:fixed; inset:0; z-index:1050; }
.modal-backdrop.fade { opacity:0; }
.modal-backdrop.fade.show, .modal-backdrop.in { opacity:.5 !important; }

/* Change Due modal */
.change-amount { font-weight: 900; font-size: 3rem; line-height: 1.1; text-align:center; letter-spacing:.02em; }

@media print { body, html { margin:0; padding:0; } }
</style>


<div class="wrapper wrapper-content animated fadeInRight" id="mainContent">
  <div class="cart-shell">
    <div class="pos-grid">
      <!-- Removed aria-hidden to avoid focus being blocked by AT -->
      <input type="text" id="barcodeInput" name="barcodeInput" tabindex="-1" autocomplete="off" style="position:absolute;opacity:0;width:0;height:0;border:none;"/>

      {{-- LEFT: INVENTORY SEARCH --}}
      <div class="inventory-column">
        <div class="ibox">
          <div class="ibox-title">
            <h5 style="display:inline-block;">Inventory</h5>
          </div>
          <div class="ibox-content">
            <div class="top-search-container">
              <div class="position-relative">
                <label for="inventorySearch" class="visually-hidden">Search inventory</label>
                <input type="text" id="inventorySearch" name="inventorySearch" class="search-bar" placeholder="Search inventory…" autocomplete="off" aria-describedby="searchHint">
                <button type="button" id="clearInventorySearch" class="clear-btn" aria-label="Clear search"><i class="fa fa-times" aria-hidden="true"></i></button>
              </div>
              <div class="results-wrap" id="searchResults" role="list" aria-live="polite"></div>
              <div id="searchHint" style="color:#7b7b7b;font-size:.95rem;padding:6px 2px;">Type to find items. No full list is shown by default.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT: CART --}}
      <div class="cart-column">
        <div class="ibox">

          <div class="ibox-title">
            <h5 style="display:inline-block;"><span id="TableNo"></span></h5>
            <button id="holdOrders" class="btn btn-block position-relative" type="button" aria-haspopup="dialog" aria-controls="holdOrdersModal">
              @lang('View Hold Orders')
              <span id="holdCountBadge" class="badge bg-danger" style="position:absolute;right:12px;top:6px;display:none;">0</span>
            </button>
          </div>

          <div class="ibox-content" id="car_items">
            {{-- (search lives in the left column) --}}

            <div id="limitWarning" style="display:none;margin:10px 0;padding:10px;border:2px solid #c82333;border-radius:9px;background:#fff3f5;color:#c82333;font-weight:bold;font-size:1.05em;"></div>
            <div id="salesProgress" class="mb-2" style="display:none;"></div>

            <div class="cart-table-wrap">
              <table width="100%" style="border-spacing:5px;border-collapse:separate;">
                <button id="ClearCart" class="btn btn-danger btn-xs pull-right" type="button" aria-label="Clear cart">&times;</button>
                <tbody id="CartHTML"></tbody>
              </table>
            </div>

            <table width="100%" style="border-spacing:5px;border-collapse:separate;">
              <tbody>
                <tr>
                  <td><h4>@lang('pos.sub_total')</h4></td>
                  <td class="text-right"><h4 id="p_subtotal">{{ $currency }}0.00</h4></td>
                </tr>
                <tr>
                  <td style="vertical-align:middle;"><h4>@lang('pos.discount')</h4></td>
                  <td class="text-right">
                    <div class="d-flex justify-content-end align-items-center" style="gap:8px;">
                      <button id="openOrderDiscountModal" class="btn btn-sm btn-primary" type="button" aria-haspopup="dialog" aria-controls="orderDiscountModal">
                        <i class="fa fa-tag" aria-hidden="true"></i> Edit Order Discount
                      </button>
                      <button id="clearOrderDiscount" class="btn btn-sm btn-light" type="button" title="Clear order discount" aria-label="Clear order discount">
                        <i class="fa fa-times" aria-hidden="true"></i>
                      </button>
                    </div>
                    <div id="orderDiscountSummary" style="margin-top:6px;color:#555;font-size:.95rem;"></div>

                    {{-- Hidden backing fields used by JS --}}
                    <select id="cartDiscountType" name="cartDiscountType" class="d-none" aria-hidden="true">
                      <option value="fixed" selected>$</option>
                      <option value="percent">%</option>
                    </select>
                    <input type="hidden" id="cartDiscountValue" name="cartDiscountValue" value="0.00"/>
                  </td>
                </tr>
                <tr>
                  <td><h5>Applied Discount</h5></td>
                  <td class="text-right"><h5 id="p_discount">- {{ $currency }}0.00</h5></td>
                </tr>
                <tr>
                  <td><h4>@lang('pos.tax') ({{ $taxPercent }}%)</h4></td>
                  <td class="text-right"><h4 id="p_hst">{{ $currency }}0.00</h4></td>
                </tr>
                <tr style="border-top:3px solid #2d3e33;background:#f4fbf6;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
                  <td style="padding-top:12px;padding-bottom:4px;">
                    <h4 style="margin:0;"><strong>@lang('pos.total')</strong></h4>
                  </td>
                  <td class="text-right" style="padding-top:12px;padding-bottom:4px;">
                    <h4 class="TotalAmount" aria-live="polite"
                        style="margin:0;font-size:2.25rem;line-height:1.1;font-weight:800;letter-spacing:.01em;">
                      {{ $currency }}0.00
                    </h4>
                  </td>
                </tr>

              </tbody>
            </table>

            <table width="100%" style="border-spacing:5px;border-collapse:separate;">
              <tr>
                <td>
                  <label for="OrderType" class="visually-hidden">Order Type</label>
                  <select id="OrderType" name="OrderType" class="form-control">
                    <option value="pos">@lang('In Store')</option>
                    <option value="order">@lang('Phone order')</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="form-group">
                    <label for="customerType">Customer Type</label>
                    <select id="customerType" name="customerType" class="form-control">
                      <option value="consumer" selected>Consumer</option>
                      <option value="patient">Patient</option>
                      <option value="caregiver">Caregiver</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="customerContact">Customer Contact</label>
                    <input type="text" id="customerContact" name="customerContact" class="form-control" placeholder="Name or Email">
                  </div>
                  <div id="medicalFields" style="display:none;margin-bottom:1em;">
                    <div class="form-group">
                      <label for="medNumber">Medical Number</label>
                      <input type="text" id="medNumber" name="medNumber" class="form-control">
                    </div>
                    <div class="form-group">
                      <label for="issuedDate">Issued Date</label>
                      <input type="date" id="issuedDate" name="issuedDate" class="form-control">
                    </div>
                    <div class="form-group">
                      <label for="expirationDate">Expiration Date</label>
                      <input type="date" id="expirationDate" name="expirationDate" class="form-control">
                    </div>
                    <div class="form-group" id="caregiverNumberGroup" style="display:none;">
                      <label for="caregiverNumber">Caregiver Number</label>
                      <input type="text" id="caregiverNumber" name="caregiverNumber" class="form-control">
                    </div>
                  </div>
                </td>
              </tr>
            </table>

            <input type="hidden" id="vat" name="vat" value="{{ $taxPercent }}">
            <input type="hidden" id="delivery_cost" name="delivery_cost" value="0">
            <input type="hidden" id="order_discount" name="order_discount" value="0">
            <input type="hidden" id="payload_county_tax" name="county_tax" value="{{ number_format($countyTax,2,'.','') }}">
            <input type="hidden" id="payload_city_tax"   name="city_tax"   value="{{ number_format($cityTax,2,'.','') }}">
            <input type="hidden" id="payload_state_tax"  name="state_tax"  value="{{ number_format($stateTax,2,'.','') }}">
            <input type="hidden" id="drawer_session_id" name="drawer_session_id" value="{{ optional(auth()->user()->currentSession)->id }}">
          </div>

          <div class="panel-footer green-bg">
            <button id="checkoutBtn" class="btn btn-md btn-success center-block" type="button" aria-haspopup="dialog" aria-controls="checkoutModal">
              <i class="fa fa-money" aria-hidden="true"></i> Complete Sale
            </button>
            <button id="holdOrderBtn" class="btn btn-md btn-warning center-block" style="display:none;" type="button">
              <i class="fa fa-pause" aria-hidden="true"></i> Hold Order
            </button>
          </div>

        </div>
      </div>
      {{-- /RIGHT --}}
    </div>
  </div>
</div>

{{-- Print Label Modal (settings used for automatic post-sale labels) --}}
<div class="modal fade" id="printLabelModal" tabindex="-1" aria-hidden="true" aria-labelledby="printLabelTitle" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="printLabelTitle" class="modal-title">Configure &amp; Print Label</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="printLabelForm" class="row g-3">
          <div class="col-md-2">
            <label class="form-label" for="labelWidth">Width (in)</label>
            <input type="number" class="form-control" id="labelWidth" name="labelWidth" step="0.1" value="4">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="labelHeight">Height (in)</label>
            <input type="number" class="form-control" id="labelHeight" name="labelHeight" step="0.1" value="2">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="labelWeight">Weight (g)</label>
            <input type="number" class="form-control" id="labelWeight" name="labelWeight" step="0.01" value="1">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="labelNotes">Notes</label>
            <input type="text" class="form-control" id="labelNotes" name="labelNotes" placeholder="Optional">
          </div>
          <div class="col-md-3 d-flex align-items-center">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="includeQr" name="includeQr" checked>
              <label class="form-check-label" for="includeQr">Include SKU QR</label>
            </div>
          </div>
        </form>
        <div id="labelPreviewContainer" class="border mt-3 p-2">
          <div id="labelPreview"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button id="printLabelConfirm" class="btn btn-primary" type="button">Print Label</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
      </div>
    </div>
  </div>
</div>

{{-- Hold Orders Modal --}}
<div class="modal fade" id="holdOrdersModal" tabindex="-1" aria-hidden="true" aria-labelledby="holdOrdersTitle" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="holdOrdersTitle" class="modal-title">Held Orders</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm table-striped" id="holdOrdersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Contact</th>
              <th>Type</th>
              <th>Items</h5>
              <th>Created</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <div id="noHoldsMsg" style="text-align:center;color:#888;display:none;">No held orders.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Item Discount Modal --}}
<div class="modal fade" id="itemDiscountModal" tabindex="-1" aria-hidden="true" aria-labelledby="itemDiscountTitle" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="itemDiscountTitle" class="modal-title">Apply Discount</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-2">
          <label for="discountType">Type</label>
          <select id="discountType" name="discountType" class="form-select">
            <option value="fixed" selected>$ Amount</option>
            <option value="percent">% Percent</option>
          </select>
        </div>
        <div class="form-group mb-3">
          <label for="discountValue">Value</label>
          <input type="number" id="discountValue" name="discountValue" class="form-control" step="0.01" min="0">
        </div>

        <div class="mb-2 d-flex justify-content-between align-items-center">
          <label id="itemReasonsLabel" class="m-0">Reasons</label>
          <button type="button" class="btn btn-xs btn-outline-secondary" id="addItemReason" aria-describedby="itemReasonsLabel"><i class="fa fa-plus" aria-hidden="true"></i> Add</button>
        </div>
        <div id="itemReasonsList" aria-labelledby="itemReasonsLabel"></div>
      </div>
      <div class="modal-footer">
        <button id="saveItemDiscount" class="btn btn-primary" type="button">Apply Discount</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
      </div>
    </div>
  </div>
</div>

{{-- Order Discount Modal --}}
<div class="modal fade" id="orderDiscountModal" tabindex="-1" aria-hidden="true" aria-labelledby="orderDiscountTitle" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="orderDiscountTitle" class="modal-title">Order Discount</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-group mb-2">
          <label for="orderDiscountType">Type</label>
          <select id="orderDiscountType" name="orderDiscountType" class="form-select">
            <option value="fixed" selected>$ Amount</option>
            <option value="percent">% Percent</option>
          </select>
        </div>
        <div class="form-group mb-3">
          <label for="orderDiscountValue">Value</label>
          <input type="number" id="orderDiscountValue" name="orderDiscountValue" class="form-control" step="0.01" min="0">
        </div>

        <div class="mb-2 d-flex justify-content-between align-items-center">
          <label id="orderReasonsLabel" class="m-0">Reasons</label>
          <button type="button" class="btn btn-xs btn-outline-secondary" id="addOrderReason" aria-describedby="orderReasonsLabel"><i class="fa fa-plus" aria-hidden="true"></i> Add</button>
        </div>
        <div id="orderReasonsList" aria-labelledby="orderReasonsLabel"></div>
      </div>
      <div class="modal-footer">
        <button id="saveOrderDiscount" class="btn btn-primary" type="button">Save</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
      </div>
    </div>
  </div>
</div>

{{-- Checkout Modal --}}
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true" aria-labelledby="checkoutTitle" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="checkoutTitle" class="modal-title">Checkout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="checkoutForm" autocomplete="off">
        <div class="modal-body">
          <div id="checkoutError" class="alert alert-danger d-none" role="alert"></div>

          <div class="mb-3">
            <label for="checkoutTotal" class="form-label">Total Due</label>
            <input type="text" id="checkoutTotal" name="checkoutTotal" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label" for="paymentType">Payment Method</label>
            <select class="form-control" id="paymentType" name="paymentType" required>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="split">Split (Cash + Card)</option>
            </select>
          </div>

          <div class="mb-3" id="cashPaidGroup">
            <label for="cashPaid" class="form-label">Cash Received</label>
            <input type="number" step="0.01" min="0" id="cashPaid" name="cashPaid" class="form-control" autocomplete="off">
          </div>

          <div class="mb-3" id="changeDueGroup">
            <label for="changeDue" class="form-label">Change Due</label>
            <input type="text" id="changeDue" name="changeDue" class="form-control" readonly>
          </div>

          <div class="mb-3" id="cardTotalGroup" style="display:none;">
            <label for="cardTotal" class="form-label">Card Charged Total</label>
            <input type="text" id="cardTotal" name="cardTotal" class="form-control" autocomplete="off" inputmode="decimal" placeholder="$0.00">
            <small class="text-muted" id="cardHint"></small>
          </div>

          <div class="mb-3" id="cardLast4Group" style="display:none;">
            <label for="cardLast4" class="form-label">Card Last 4 Digits</label>
            <input type="text" maxlength="4" pattern="\d{4}" id="cardLast4" name="cardLast4" class="form-control" autocomplete="off" inputmode="numeric">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" id="confirmCheckoutBtn" class="btn btn-primary w-100">
            <i class="fa fa-check" aria-hidden="true"></i> Confirm Sale
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Change Due Modal (after sale) --}}
<div class="modal fade" id="changeDueModal" tabindex="-1" aria-hidden="true" aria-labelledby="changeDueTitle" role="dialog">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="changeDueTitle" class="modal-title">Change Due</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="changeDueBig" class="change-amount">$0.00</div>
        <div id="changeBreakdown" class="text-center text-muted mt-2" style="font-size:.95rem;"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success w-100" data-bs-dismiss="modal" type="button">Done</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // ===== PHP -> JS seed =====
  window.INVENTORY_SEED = @json($invSeed);
  window.DISCOUNT_TIERS = @json($discountTiers);
  window.SALES_RECEIPT_URL_BASE = "{{ url('/sales') }}";
  window.BUSINESS = {
    name: @json($bizName),
    phone: @json($bizPhone),
    address: @json($bizAddr),
    packager_license: @json($packagerLicense)
  };
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/numpad/jquery.numpad.js') }}"></script>

<script>
(function($){
  $(function(){

    const CSRF = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
    const currency = @json($currency), taxPct = {{ $taxPercent }};
    const countyPct = parseFloat($('#payload_county_tax').val()) || 0;
    const cityPct   = parseFloat($('#payload_city_tax').val())   || 0;
    const statePct  = parseFloat($('#payload_state_tax').val())  || 0;
    const rawTiers = window.DISCOUNT_TIERS || {};
    const lower = s => String(s||'').toLowerCase().trim();

    /* ===== Cross-version modal helpers (BS3/4/5 + manual fallback) ===== */
    function _forceModalCleanupOnce() {
      try {
        document.querySelectorAll('.modal.show, .modal.in').forEach(m => {
          m.classList.remove('show','in');
          m.style.display = '';
          m.removeAttribute('aria-modal');
          m.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
      } catch {}
    }
    function _blurInside(el){ try { if (el && el.contains(document.activeElement)) document.activeElement.blur(); } catch {} }

    function _openWithBootstrap(target){
      if (!window.bootstrap || !bootstrap.Modal) return false;
      try { bootstrap.Modal.getOrCreateInstance(target, { backdrop:true, focus:true, keyboard:true }).show(); return true; }
      catch(e){ return false; }
    }
    function _closeWithBootstrap(target){
      if (!window.bootstrap || !bootstrap.Modal) return false;
      try { bootstrap.Modal.getOrCreateInstance(target).hide(); return true; }
      catch(e){ return false; }
    }
    function _openWithJQueryPlugin(target){
      const fn = window.jQuery && jQuery.fn && jQuery.fn.modal;
      if (!fn) return false;
      try { jQuery(target).modal('show'); return true; } catch(e){ return false; }
    }
    function _closeWithJQueryPlugin(target){
      const fn = window.jQuery && jQuery.fn && jQuery.fn.modal;
      if (!fn) return false;
      try { jQuery(target).modal('hide'); return true; } catch(e){ return false; }
    }
    function _openManually(target){
      _forceModalCleanupOnce();
      target.classList.add('show','in');
      target.style.display = 'block';
      target.setAttribute('aria-modal','true');
      target.removeAttribute('aria-hidden');
      document.body.classList.add('modal-open');
      const bd = document.createElement('div');
      bd.className = 'modal-backdrop fade show in';
      document.body.appendChild(bd);
      const focusable = target.querySelector('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
      (focusable || target).focus({preventScroll:true});
    }
    function _closeManually(target){
      _blurInside(target);
      target.classList.remove('show','in');
      target.style.display = '';
      target.setAttribute('aria-hidden','true');
      target.removeAttribute('aria-modal');
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
    }

    window.openModal = function(sel){
      const target = document.querySelector(sel);
      if (!target) return;
      const open = document.querySelector('.modal.show, .modal.in');
      if (open && open !== target) window.closeModal('#'+open.id);

      if (_openWithBootstrap(target)) return;
      if (_openWithJQueryPlugin(target)) return;
      _openManually(target);
    };
    window.closeModal = function(sel){
      const target = document.querySelector(sel);
      if (!target) return;
      _blurInside(target);
      if (_closeWithBootstrap(target)) return;
      if (_closeWithJQueryPlugin(target)) return;
      _closeManually(target);
    };

    document.addEventListener('click', (e)=>{
      const btn = e.target.closest('[data-bs-dismiss="modal"]');
      if (!btn) return;
      const modal = btn.closest('.modal');
      if (modal) window.closeModal('#'+modal.id);
    });

    document.addEventListener('hide.bs.modal', (e)=> _blurInside(e.target));
    _forceModalCleanupOnce();

    $(document).on('hidden.bs.modal', function(){
      if ($('.modal.show, .modal.in').length===0){ $('.modal-backdrop').remove(); $('body').removeClass('modal-open').css('padding-right',''); }
    });

    /* ===== Helpers ===== */
    function isEmail(s){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(s||'').toLowerCase()); }
    function getCheckoutEmail(){
      const explicit = $('#customerEmail').length ? String($('#customerEmail').val()||'').trim() : '';
      if (explicit && isEmail(explicit)) return explicit;
      const contact = String($('#customerContact').val()||'').trim();
      if (isEmail(contact)) return contact;
      const m = contact.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
      return m ? m[0] : '';
    }
    const toMoney = (n) => currency + (Number(n||0).toFixed(2));

    // --- Currency helpers for the card amount ---
    function sanitizeNumericString(s){
      let cleaned = String(s||'').replace(/[^\d.]/g, '');
      const parts = cleaned.split('.');
      if (parts.length > 2) cleaned = parts.shift() + '.' + parts.join('');
      return cleaned;
    }
    function readCurrencyInput(s){
      const cleaned = sanitizeNumericString(s);
      const n = parseFloat(cleaned);
      return Number.isFinite(n) ? n : 0;
    }
    function formatMoney(n){
      const num = Number(n||0);
      if (!Number.isFinite(num)) return '';
      const parts = num.toFixed(2).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      return currency + parts.join('.');
    }

    /* ===== PRINT CORE ===== */
    function printViaIframe(htmlDoc, iframeId='print_iframe_generic') {
      let iframe = document.getElementById(iframeId);
      if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = iframeId;
        iframe.style.position = 'fixed';
        iframe.style.right = 0;
        iframe.style.bottom = 0;
        iframe.style.width = 0;
        iframe.style.height = 0;
        iframe.style.border = 0;
        iframe.setAttribute('aria-hidden','true');
        iframe.setAttribute('tabindex','-1');
        document.body.appendChild(iframe);
      }
      const win = iframe.contentWindow || iframe;
      const doc = win.document || iframe.contentDocument;
      doc.open(); doc.write(htmlDoc); doc.close();

      const finalize = ()=>{ try { win.focus(); win.print(); } catch(_){} };
      const imgs = doc.images;
      if (!imgs || !imgs.length) { finalize(); return; }
      let loaded = 0;
      const check = ()=>{ if (loaded >= imgs.length) finalize(); };
      [...imgs].forEach(img=>{
        if (img.complete) { loaded++; check(); }
        else { img.addEventListener('load', ()=>{loaded++;check();});
               img.addEventListener('error',()=>{loaded++;check();}); }
      });
      setTimeout(check, 700);
    }

    // ---------- THERMAL RECEIPT (80mm) ----------
    function buildThermalReceiptHTML(saleResp, req, biz, split){
      const now = new Date();
      const ts  = now.toLocaleString();
      const saleId = (saleResp && (saleResp.sale?.id || saleResp.id || saleResp.sale_id || saleResp.data?.sale_id || saleResp.data?.id)) || '—';
      const drawer = String($('#drawer_session_id').val()||'') || '—';
      const custType = String($('#customerType').val()||'consumer');

      // Items
      const items = Array.isArray(req.cart) ? req.cart : [];

      // totals
      const subTotal = Number(req.subtotal || 0);
      const discountTotal = Number(req.discount_total || 0);
      const orderDiscType = (req.order_discount && req.order_discount.type) || (document.getElementById('cartDiscountType')?.value || 'fixed');
      const orderDiscVal  = +(req.order_discount && req.order_discount.value || parseFloat($('#cartDiscountValue').val()||0) || 0);
      const taxTotal      = Number(req.tax_total || 0);
      const totalAmount   = Number(req.total_amount || 0);

      // split tax lines proportionally (state/county/city)
      const sumPct = (statePct + countyPct + cityPct) || 0;
      const share = sumPct ? {
        state:  +(taxTotal * (statePct  / sumPct)).toFixed(2),
        county: +(taxTotal * (countyPct / sumPct)).toFixed(2),
        city:   +(taxTotal * (cityPct   / sumPct)).toFixed(2),
      } : { state:0, county:0, city:0 };

      // payments
      const pType  = String(req.payment_type || '').toLowerCase();
      const pCash  = Number(req.cash_received || req.cashReceived || 0);
      const pCard  = Number(req.card_total || req.cardTotal || 0);
      const pL4    = String(req.card_last4 || req.cardLast4 || '').replace(/\s+/g,'');
      const tender = +(pCash + pCard).toFixed(2);
      const change = Math.max(0, +(tender - totalAmount).toFixed(2));

      const odiscBadge = (orderDiscVal>0)
        ? (orderDiscType==='percent' ? `(${orderDiscVal.toFixed(2)}%)` : `(${currency}${orderDiscVal.toFixed(2)})`)
        : '';

      const orderReasons = (req.order_discount && Array.isArray(req.order_discount.reasons) ? req.order_discount.reasons : []);
      const orderReasonsLine = (orderReasons.length ? `Reasons: ${orderReasons.join('; ')}` : '');

      // rows
      const rowsHtml = items.map((it)=>{
        const name = (it.name || '').toString();
        const qty  = it.price_is_line_total ? (Number(it.quantity||0)).toFixed(2) : (parseInt(it.quantity||1,10));
        const unit = it.price_is_line_total
          ? (Number(it.unit_price || (Number(it.line_total||0)/Math.max(1,Number(it.quantity||1))))).toFixed(2)
          : (Number(it.unit_price || it.price || 0)).toFixed(2);
        const line = Number(it.line_total || (it.price_is_line_total ? (it.price||0) : (Number(unit)*Math.max(1, parseInt(qty,10)))) );
        const lineFmt = (currency + line.toFixed(2));

        const pkg = (it.label || it.sku || '').toString();
        const pkgShort = pkg ? `Pkg: ${pkg.slice(-8)}` : '';

        // inline discount display (if any)
        const dType = it.inline_discount_type || it.discountType;
        const dVal  = +(it.inline_discount_value ?? it.discountValue ?? 0);
        const dBadge = dType && dVal>0
          ? (dType==='percent' ? ` -${dVal}%` : ` -${currency}${dVal.toFixed(2)}`) : '';

        return `
          <div class="li">
            <div class="li-top">
              <span class="li-name">${escapeHtml(name)}${dBadge ? `<span class="li-disc">${escapeHtml(dBadge)}</span>`:''}</span>
              <span class="li-rt">${it.price_is_line_total ? `${qty} @ ${currency}${Number(unit).toFixed(2)}` : `${qty} × ${currency}${Number(unit).toFixed(2)}`}</span>
            </div>
            ${pkgShort ? `<div class="li-sub">${escapeHtml(pkgShort)}</div>`:''}
            <div class="li-bottom">
              <span></span>
              <span class="li-total">${lineFmt}</span>
            </div>
          </div>
        `;
      }).join('');

      const css = `
        @page { size: 80mm auto; margin: 4mm; }
        *{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        body { margin:0; padding:0; font: 12px/1.35 "SFMono-Regular", Menlo, Consolas, "Liberation Mono", monospace; color:#111; }
        .rcpt { width: 80mm; max-width:80mm; margin:0 auto; }
        .center { text-align:center; }
        .muted { color:#666; }
        .hr { border-top:1px dashed #999; margin:6px 0; }
        .hdr h2 { margin:0 0 3px 0; font-size:16px; letter-spacing:.4px; }
        .meta { margin:6px 0; }
        .meta .row{ display:flex; justify-content:space-between; }
        .li { margin:6px 0; }
        .li-top, .li-bottom { display:flex; justify-content:space-between; align-items:flex-end; }
        .li-name { font-weight:700; max-width:58%; }
        .li-disc { margin-left:6px; font-size:11px; font-weight:700; color:#8a5500; }
        .li-rt { color:#333; }
        .li-sub { font-size:11px; color:#444; }
        .li-total { font-weight:700; }
        .totals .row { display:flex; justify-content:space-between; }
        .big { font-size:16px; font-weight:800; }
        .foot { text-align:center; margin-top:8px; }
      `;
      const html = `
        <!doctype html><html><head><meta charset="utf-8">
        <title>Receipt #${escapeHtml(String(saleId))}</title>
        <style>${css}</style></head><body>
          <div class="rcpt">
            <div class="hdr center">
              ${biz.name ? `<h2>${escapeHtml(biz.name)}</h2>` : ''}
              ${biz.address ? `<div class="muted">${escapeHtml(biz.address)}</div>`:''}
              ${biz.phone ? `<div class="muted">${escapeHtml(biz.phone)}</div>`:''}
              ${biz.packager_license ? `<div class="muted">Packaging Lic: ${escapeHtml(biz.packager_license)}</div>`:''}
            </div>

            <div class="hr"></div>

            <div class="meta">
              <div class="row"><span>Date</span><span>${escapeHtml(ts)}</span></div>
              <div class="row"><span>Sale #</span><span>${escapeHtml(String(saleId))}</span></div>
              <div class="row"><span>Drawer</span><span>${escapeHtml(String(drawer))}</span></div>
              <div class="row"><span>Customer Type</span><span>${escapeHtml(custType.charAt(0).toUpperCase()+custType.slice(1))}</span></div>
            </div>

            <div class="hr"></div>

            ${rowsHtml}

            <div class="hr"></div>

            <div class="totals">
              <div class="row"><span>Subtotal</span><span>${currency}${subTotal.toFixed(2)}</span></div>
              <div class="row"><span>Discounts</span><span>- ${currency}${discountTotal.toFixed(2)}</span></div>
              ${orderDiscVal>0 ? `<div class="row"><span>Order Discount ${escapeHtml(odiscBadge)}</span><span>- ${currency}${(orderDiscType==='percent' ? (subTotal*orderDiscVal/100) : Math.min(orderDiscVal, subTotal)).toFixed(2)}</span></div>`:''}
              ${orderReasonsLine ? `<div class="row"><span class="muted">${escapeHtml(orderReasonsLine)}</span><span></span></div>`:''}
              ${taxTotal>0 ? `<div class="row"><span>State Tax</span><span>${currency}${share.state.toFixed(2)}</span></div>`:''}
              ${taxTotal>0 ? `<div class="row"><span>County Tax</span><span>${currency}${share.county.toFixed(2)}</span></div>`:''}
              ${taxTotal>0 ? `<div class="row"><span>City Tax</span><span>${currency}${share.city.toFixed(2)}</span></div>`:''}
              <div class="hr"></div>
              <div class="row big"><span>Total</span><span>${currency}${totalAmount.toFixed(2)}</span></div>
            </div>

            <div class="hr"></div>

            <div class="totals">
              ${pCash>0 ? `<div class="row"><span>Paid (Cash)</span><span>${currency}${pCash.toFixed(2)}</span></div>`:''}
              ${pCard>0 ? `<div class="row"><span>Paid (Card)${pL4? ' • ****'+escapeHtml(pL4.slice(-4)) : ''}</span><span>${currency}${pCard.toFixed(2)}</span></div>`:''}
              <div class="row"><span>Tendered</span><span>${currency}${tender.toFixed(2)}</span></div>
              <div class="row"><span>Change Due</span><span>${currency}${change.toFixed(2)}</span></div>
            </div>

            <div class="foot">
              <div class="muted">Thank you!</div>
            </div>
          </div>
        </body></html>
      `;
      return html;
    }

    // ---------- LABEL SHEET ----------
    function buildLabelDocHTML(cartItems, opts){
      const W = Math.max(1, parseFloat(opts.width||4));
      const H = Math.max(0.5, parseFloat(opts.height||2));
      const weight = opts.weight ? `${Number(opts.weight).toFixed(2)} g` : '';
      const includeQr = !!opts.includeQr;
      const notes = String(opts.notes||'').trim();

      const css = `
        @page { size: auto; margin: 6mm; }
        body{ margin:0; padding:0; font: 12px/1.25 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif; color:#111; }
        .label{ width:${W}in; height:${H}in; border:1px solid #000; padding:6px; margin:4px auto; page-break-inside:avoid; display:flex; gap:6px; }
        .lcol{ flex:1; min-width:0; }
        .name{ font-weight:800; font-size:13px; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .meta{ font-size:11px; color:#333; }
        .meta div{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .qr{ display:flex; align-items:center; justify-content:center; width:1.1in; }
        .qr img{ width:1.1in; height:1.1in; }
        .muted{ color:#555; font-size:11px; }
      `;

      const blocks = cartItems.map(ci=>{
        const name = (ci.name||'').toString();
        const pkg  = (ci.label || ci.sku || '').toString();
        const last = pkg ? '#'+pkg.slice(-8) : '';
        const price = (ci.price_is_line_total ? (ci.line_total||ci.price||0) : (ci.unit_price||ci.price||0));
        const priceLine = isFinite(price) ? `Price: ${currency}${Number(price).toFixed(2)}` : '';
        const skuText = (ci.sku || pkg || '').toString();

        return `
          <div class="label">
            <div class="lcol">
              <div class="name">${escapeHtml(name)}</div>
              ${last ? `<div class="meta"><div>Pkg: ${escapeHtml(last)}</div></div>`:''}
              <div class="meta"><div>${escapeHtml(priceLine)}</div></div>
              ${weight ? `<div class="meta"><div>Weight: ${escapeHtml(weight)}</div></div>`:''}
              ${notes ? `<div class="muted">${escapeHtml(notes)}</div>`:''}
            </div>
            ${includeQr ? `<div class="qr"><img alt="QR" src="/qr?text=${encodeURIComponent(skuText)}&size=220" /></div>`:''}
          </div>
        `;
      }).join('');

      return `<!doctype html><html><head><meta charset="utf-8"><style>${css}</style></head><body>${blocks}</body></html>`;
    }

    function escapeHtml(s){
      return String(s==null?'':s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
    }

    /* ===== Tier map ===== */
    window.tierLookup = {};
    (function(){
      try {
        Object.entries(rawTiers||{}).forEach(([key,t])=>{
          if(!t) return;
          const obj = typeof t === 'object' ? { ...t } : {};
          let pr = obj.pricing;
          if (pr && !Array.isArray(pr)) pr = Object.values(pr);
          obj.pricing = (Array.isArray(pr) ? pr : []).map(p=>{
            const priceCand = [p?.price,p?.amount,p?.value].map(v=>parseFloat(v)).find(Number.isFinite);
            return priceCand ? {...p, price: priceCand } : null;
          }).filter(Boolean);
          const cname = lower(obj.name || key);
          if(!cname || !obj.pricing.length) return;
          window.tierLookup[cname] = obj;
          window.tierLookup[lower(key)] = obj;
          if (obj.name) window.tierLookup[lower(obj.name)] = obj;
          if (obj.id != null) window.tierLookup[String(obj.id).toLowerCase()] = obj;
        });
      } catch(e){ console.error('tierLookup build failed', e); }
    })();

    /* ===== Inventory index (includes OOS + hold) ===== */
    const seed = Array.isArray(window.INVENTORY_SEED) ? window.INVENTORY_SEED : [];
    const inventoryById = {};
    const inventoryList = seed.map(item => {
      const flatten = (val, acc=[])=>{
        if (val==null) return acc;
        const t = typeof val;
        if (t==='string' || t==='number' || t==='boolean'){ acc.push(String(val)); }
        else if (Array.isArray(val)){ val.forEach(v=>flatten(v,acc)); }
        else if (t==='object'){ Object.values(val).forEach(v=>flatten(v,acc)); }
        return acc;
      };
      const metaText = flatten(item.metrc).concat(flatten(item.labs)).join(' ');
      const index = [item.name, item.sku, item.label, item.category_name, metaText]
        .join(' ').toLowerCase().replace(/\s+/g,' ').trim();
      const out = {
        ...item,
        _index: index,
        inventory_type: item.inventory_type || 'inventories'
      };
      inventoryById[item.id] = out;
      return out;
    });
    window.inventoryById = inventoryById;

    function computeWeightForItem(item){
      try{
        const pkg = item.metrc || {};
        let payload = {};
        try { payload = pkg && typeof pkg.payload === 'string' ? JSON.parse(pkg.payload) : (pkg?.payload || {}); } catch { payload = {}; }
        const limitCat = String(item.limit_category||'').toLowerCase();

        if (limitCat.includes('flower') || limitCat.includes('joint')) {
          const rawThc = parseFloat(payload?.Item?.UnitThcContent) || parseFloat(pkg.UnitThcContent) || 0;
          if (rawThc) return +rawThc.toFixed(3);
          const pkgWt = parseFloat(pkg.Weight) || 0;
          const pkgQt = parseFloat(pkg.Quantity) || 1;
          return +(pkgQt>0 ? (pkgWt/pkgQt) : 0).toFixed(3);
        } else if (limitCat==='tinctures' || limitCat==='drinks') {
          const rawWt = parseFloat(payload?.Item?.UnitWeight) || 0;
          const uom   = String(payload?.Item?.UnitWeightUnitOfMeasureName||'').toLowerCase();
          let oz = 0;
          if (rawWt>0) oz = uom.includes('gram') ? (rawWt/28.3495) : rawWt;
          if (!oz){
            const pkgWt = parseFloat(pkg.Weight) || 0;
            const pkgQt = parseFloat(pkg.Quantity) || 1;
            oz = pkgQt>0 ? ((pkgWt/pkgQt)/28.3495) : 0;
          }
          return +oz.toFixed(3);
        } else {
          const rawGr = parseFloat(payload?.Item?.UnitWeight) || ((parseFloat(pkg.Weight)||0) / (parseFloat(pkg.Quantity)||1)) || 0;
          return +rawGr.toFixed(3);
        }
      } catch { return 0; }
    }

    /* ===== Search UI (no availability filter) ===== */
    function renderResults(list){
      const $wrap = $('#searchResults').empty();
      if (!list || !list.length){ $('#searchHint').show(); return; }
      $('#searchHint').hide();

      list.forEach(item=>{
        const weight = computeWeightForItem(item);
        const tierKey = (item.selected_discount_tier_name || item.selected_discount_tier || '').toString();
        const last5 = (item.label||'').slice(-5);
        const isHold = String(item.inventory_type||'') === 'hold_inventories';
        const isOOS = (parseFloat(item.available)||0) <= 0.0001;

        const $card = $(`
          <div class="result-card inventory-card"
               data-id="${item.id}"
               data-price="${item.price}"
               data-name="${$('<div>').text(item.name).html()}"
               data-sku="${$('<div>').text(item.sku||'').html()}"
               data-label="${$('<div>').text(item.label||'').html()}"
               data-weight="${weight}"
               data-available="${(item.available||0).toFixed(3)}"
               data-selected-discount-tier="${$('<div>').text(item.selected_discount_tier||'').html()}"
               data-selected-discount-tier-key="${$('<div>').text(item.selected_discount_tier||'').html()}"
               data-selected-discount-tier-name="${$('<div>').text(item.selected_discount_tier_name||'').html()}"
               data-category-id="${item.category_id||''}"
               data-limit-category="${$('<div>').text(item.limit_category||'').html()}"
               data-limit-value="${item.limit_value||0}"
               data-tax-exempt="${item.tax_exempt?1:0}"
               data-inventory_type="${item.inventory_type||'inventories'}">
            <div class="result-img">${ item.image_url ? `<img src="${item.image_url}" alt="">` : '<i class="fa fa-image" aria-hidden="true" style="opacity:.5;"></i>' }</div>
            <div class="result-body">
              <div class="result-name">
                ${item.name}
                ${ last5 ? `<span class="pkg-short">#${last5}</span>` : '' }
              </div>
              <div class="result-meta">
                ${tierKey ? `<span class="field">Tier: ${tierKey}</span>` : ''}
                <span class="field">Price: ${currency}${(item.price||0).toFixed(2)}</span>
                <span class="field">Qty: <span class="available-qty">${(item.available||0).toFixed(2)}</span> <span class="badge-live">live</span></span>
                ${isHold ? `<span class="field"><span class="badge-hold">On Hold</span></span>` : ''}
                ${isOOS && !isHold ? `<span class="field"><span class="badge-oos">Out of Stock</span></span>` : ''}
                ${item.category_name ? `<span class="field">Cat: ${item.category_name}</span>` : ''}
              </div>
            </div>
            <div class="result-actions">
              <label class="btn btn-light btn-xs" style="display:inline-flex;align-items:center;gap:6px;">
                Hold <input type="checkbox" class="hold-toggle" data-id="${item.id}" ${isHold?'checked':''} />
              </label>

              <button class="btn btn-secondary btn-xs print-label" type="button" aria-haspopup="dialog" aria-controls="printLabelModal">Label</button>

              <button class="btn btn-success btn-sm add-to-cart-btn" type="button" aria-label="Add ${$('<div>').text(item.name).html()} to cart"${(isHold || isOOS)?' disabled':''}>
                <i class="fa fa-plus" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        `);
        $card.data('metrc', item.metrc||{});
        $card.data('labs',  item.labs ||[]);
        $wrap.append($card);
      });
    }

    let searchTimer=null;
    function doSearch(){
      const q = String($('#inventorySearch').val()||'').toLowerCase().trim();
      if(!q){ $('#searchResults').empty(); $('#searchHint').show(); return; }
      const results = inventoryList.filter(it => it._index.indexOf(q) !== -1).slice(0, 250);
      renderResults(results);
      results.forEach(it => setAvail(it.id, getAvail(it.id)));
    }
    $('#inventorySearch').on('input', function(){ clearTimeout(searchTimer); searchTimer=setTimeout(doSearch,120); });
    $('#clearInventorySearch').on('click', function(){ $('#inventorySearch').val('').trigger('input').focus(); });

    /* ===== Cart state ===== */
    let cart = {}, cartOrder = [];
    window.cart = cart; window.cartOrder = cartOrder;

    /* ===== Flower pricing helpers ===== */
    function getFlowerTierBandsByKey(key){
      const tier = window.tierLookup[lower(key)];
      if (!tier || !tier.pricing) return null;
      const OZ_TO_G = 28.349523125;
      function extractNominal(p){
        const gramFields = ['min_quantity','min_qty','grams','quantity','qty','size_g','size_grams'];
        for (const f of gramFields){ const v = parseFloat(p[f]); if (Number.isFinite(v) && v>0) return v; }
        const ozFields = ['size_oz','oz','ounces','size_ounces'];
        for (const f of ozFields){ const v = parseFloat(p[f]); if (Number.isFinite(v) && v>0) return v*OZ_TO_G; }
        const n = String(p.name||'').toLowerCase();
        if (/eighth/.test(n)) return 3.5;
        if (/quarter/.test(n)) return 7;
        if (/\bhalf\b/.test(n)) return 14;
        if (/\bounce| oz\b/.test(n)) return 28;
        const gMatch = n.match(/(\d+(?:\.\d+)?)\s*g\b/); if (gMatch) return parseFloat(gMatch[1]);
        const ozMatch= n.match(/(\d+(?:\.\d+)?)\s*(?:oz|ounces?)\b/); if (ozMatch) return parseFloat(ozMatch[1]) * OZ_TO_G;
        const frac = n.match(/\b(1\/8|1\/4|1\/2|2\/1)\b/); if (frac) return ({'1/8':3.5,'1/4':7,'1/2':14,'2\/1':56})[frac[1]] || null;
        return null;
      }
      const byNominal = new Map();
      (tier.pricing||[]).forEach(p=>{
        const nominal = extractNominal(p), flat = parseFloat(p.price);
        if (!Number.isFinite(nominal)||!Number.isFinite(flat)||nominal<=0) return;
        const prev = byNominal.get(nominal);
        if (!prev || flat < prev.flat) byNominal.set(nominal, { nominal, flat, rate: flat/nominal });
      });
      const bands = Array.from(byNominal.values()).sort((a,b)=>a.nominal-b.nominal);
      return bands.length ? bands : null;
    }

    function priceFlowerFromTiersGeneric(itemOrCard, grams){
      const sel =
        lower(itemOrCard?.selected_discount_tier_name
           || itemOrCard?.selected_discount_tier
           || itemOrCard?.dataset?.selectedDiscountTierName
           || itemOrCard?.dataset?.selectedDiscountTierKey
           || itemOrCard?.dataset?.selectedDiscountTier
           || '');
      const bands = getFlowerTierBandsByKey(sel);
      if (!bands || !bands.length) return { total:0, rate:0, billable:0 };
      const GRACE = 0.20, EPS=1e-9;
      const q = Math.max(0, parseFloat(grams)||0);
      if (q<=0) return { total:0, rate:0, billable:0 };
      const anchors = bands.map(b=>b.nominal), rates = bands.map(b=>b.rate);
      let idx=0; for (let i=0;i<anchors.length-1;i++){ if (q<anchors[i+1]-EPS){ idx=i; break; } idx=i+1; }
      const tierAnchor = anchors[idx], rate = rates[idx];
      let billable = q;
      if (q>tierAnchor+EPS && q<=tierAnchor+GRACE+EPS) billable = tierAnchor;
      return { total: rate*billable, rate, billable };
    }
    function isFlowerFromFields(name, limitCategory, selectedTier){
      const looksPreRoll = /(pre[\s-]?roll|preroll|joint|cone)\b/i.test(String(name||''));
      const looksFlowerish =
        /flower/i.test(String(limitCategory||'')) ||
        /(bud|eighth|1\/8|quarter|1\/4|half|1\/2|ounce|oz|gram|\bg\b)/i.test(String(name||''));
      const sel = lower(selectedTier||'');
      const hasBands = !!(window.tierLookup[sel] && Array.isArray(window.tierLookup[sel].pricing) && window.tierLookup[sel].pricing.length);
      return hasBands && looksFlowerish && !looksPreRoll;
    }

    /* ===== Availability ===== */
    function getAvail(id){
      const $c = $(`.inventory-card[data-id="${id}"]`);
      if ($c.length) {
        const v = parseFloat($c.attr('data-available')); if (Number.isFinite(v)) return v;
      }
      const it = inventoryById[id]; return it ? (parseFloat(it.available)||0) : 0;
    }
    function setAvail(id, val){
      const v = Math.max(0, +(+val).toFixed(3));
      const $c = $(`.inventory-card[data-id="${id}"]`);
      if ($c.length){
        $c.attr('data-available', v.toFixed(3));
        $c.find('.available-qty').text(v.toFixed(2));
        const isHold = String($c.data('inventory_type')||'') === 'hold_inventories';
        $c.find('.add-to-cart-btn').prop('disabled', v<=0.0001 || isHold);
        if (v<=0.0001 && !isHold && !$c.find('.badge-oos').length){
          $c.find('.result-meta').append(`<span class="field"><span class="badge-oos">Out of Stock</span></span>`);
        }
      }
      if (inventoryById[id]) inventoryById[id].available = v;
    }
    function bumpAvail(id, delta){ setAvail(id, getAvail(id)+delta); }

    /* ===== Reservations ===== */
    let myResv = {}, isSyncing=false;

    function reserveAjax(id, qty, onOk, onFail){
      if (qty<=0){ onOk&&onOk(); return; }
      $.ajax({
        url: `/inventory/${id}/reserve`, method: 'POST',
        data: { quantity: qty, _token: CSRF },
        success: function(res){ if (typeof res?.available!=='undefined') setAvail(id, res.available); else bumpAvail(id, -qty); onOk&&onOk(res); },
        error: function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to reserve.'); onFail&&onFail(xhr); }
      });
    }
    function releaseAjax(id, qty, onOk, onFail){
      if (qty<=0){ onOk&&onOk(); return; }
      $.ajax({
        url: `/inventory/${id}/release`, method: 'POST',
        data: { quantity: qty, _token: CSRF },
        success: function(res){ if (typeof res?.available!=='undefined') setAvail(id, res.available); else bumpAvail(id, +qty); onOk&&onOk(res); },
        error: function(xhr){ toastr.error(xhr.responseJSON?.message||'Unable to release.'); onFail&&onFail(xhr); }
      });
    }
    function syncReservations(){
      if (isSyncing) return; isSyncing=true;
      cartOrder.forEach(id=>{
        const want = parseFloat(cart[id]?.qty||0), have = parseFloat(myResv[id]||0);
        const delta = +(want-have).toFixed(3);
        if (delta>0){
          reserveAjax(id, delta, ()=>{ myResv[id]=+(have+delta).toFixed(3); }, ()=>{
            cart[id].qty = +Math.max(0, have).toFixed(3); scheduleRenderCart();
          });
        } else if (delta<0){
          const rel = Math.abs(delta);
          releaseAjax(id, rel, ()=>{ const left=+(have-rel).toFixed(3); if (left<=0.0005) delete myResv[id]; else myResv[id]=left; });
        }
      });
      Object.keys(myResv).forEach(id=>{ if(!cart[id]){ const amt=myResv[id]; releaseAjax(id, amt, ()=>{ delete myResv[id]; }); }});
      isSyncing=false;
    }
    function releaseAllReservations(){
      const ids = Object.keys(myResv);
      ids.forEach(id=> releaseAjax(id, myResv[id], function(){}));
      myResv = {};
    }
    window.releaseAllReservations = releaseAllReservations;

    window.addEventListener('beforeunload', function(){
      try{
        Object.keys(myResv).forEach(id => navigator.sendBeacon(
          `/inventory/${id}/release`,
          new URLSearchParams({ quantity: myResv[id], _token: CSRF })
        ));
      }catch(e){}
    });

    /* ===== Limits/progress ===== */
    const OR_DEFAULT_LIMITS = {
      consumer: {'flower/joints':56.7,'extracts/concentrates':10,'inhalable cannabinoid':10,'edibles':454,'tinctures':72,'clones':4},
      patient:  {'flower/joints':224,'extracts/concentrates':10,'inhalable cannabinoid':10,'edibles':454,'tinctures':72,'clones':12},
      caregiver:{'flower/joints':224,'extracts/concentrates':10,'inhalable cannabinoid':10,'edibles':454,'tinctures':72,'clones':12}
    };
    function getCustomerType(){ return ($('#customerType').val()||'consumer').toLowerCase(); }

    function itemDataById(id){
      const $c = $(`.inventory-card[data-id="${id}"]`);
      if ($c.length) {
        return {
          $card: $c,
          data: {
            id: id,
            name: $c.data('name'),
            price: parseFloat($c.data('price'))||0,
            sku: $c.data('sku')||'',
            label: $c.data('label')||'',
            weight: parseFloat($c.data('weight'))||0,
            tax_exempt: ($c.data('tax-exempt')==1),
            category_id: $c.data('category-id')||'',
            limit_category: $c.data('limit-category')||'',
            limit_value: parseFloat($c.data('limit-value')||0),
            selected_discount_tier: $c.data('selected-discount-tier')||'',
            selected_discount_tier_name: $c.data('selected-tiert-name')||$c.data('selected-discount-tier-name')||$c.data('selectedDiscountTierName')||'',
            metrc: $c.data('metrc')||{},
            labs: $c.data('labs')||[]
          }
        };
      }
      const d = inventoryById[id];
      return { $card: null, data: d ? { ...d, weight: computeWeightForItem(d), tax_exempt: !!d.tax_exempt } : null };
    }

    function cartCategoryTotals(){
      const totals = {};
      cartOrder.forEach(id=>{
        const {data} = itemDataById(id); if(!data) return;
        const cat = String(data.limit_category||'uncategorized').toLowerCase();
        const unit = parseFloat(data.weight)||1;
        const grams = (parseFloat(cart[id]?.qty||0))*unit;
        if(!totals[cat]){
          const type = getCustomerType();
          const limit = parseFloat(data.limit_value)||OR_DEFAULT_LIMITS[type][cat]||0;
          const name = cat.charAt(0).toUpperCase()+cat.slice(1);
          totals[cat] = { total:0, limit, name };
        }
        totals[cat].total += grams;
      });
      return totals;
    }

    function checkCategoryLimits(){
      const totals = cartCategoryTotals(), exceeded = [];
      Object.values(totals).forEach(info=>{ if(info.limit && info.total>info.limit){ exceeded.push(`${info.name} (${info.total.toFixed(2)} > ${info.limit})`); }});
      if(exceeded.length){
        $('#limitWarning').html('⚠️ <b>Oregon sales limit exceeded for:</b><br>'+exceeded.map(e=>`<span style="color:#dc3545;">${e}</span>`).join('<br>')).show();
        $('#checkoutBtn').prop('disabled',true).addClass('btn-danger').removeClass('btn-success').html('<i class="fa fa-ban" aria-hidden="true"></i> Limit Exceeded');
      } else {
        $('#limitWarning').hide();
        $('#checkoutBtn').prop('disabled',false).removeClass('btn-danger').addClass('btn-success').html('<i class="fa fa-money" aria-hidden="true"></i> Complete Sale');
      }
    }

    function renderSalesProgress(){
      const totals = cartCategoryTotals(), type = getCustomerType();
      let html='';
      Object.keys(OR_DEFAULT_LIMITS[type]).forEach(cat=>{
        const base={ total:0, limit:OR_DEFAULT_LIMITS[type][cat], name:cat.charAt(0).toUpperCase()+cat.slice(1) };
        const info = totals[cat] || base;
        if (!info.limit) return;
        if ((info.total||0) <= 0.00001) return;
        const pct = Math.min(100,(info.total/info.limit)*100);
        const over = info.total>info.limit;
        html += `
          <div class="mb-2">
            <small>${info.name}: ${info.total.toFixed(2)} / ${info.limit}${over? ' <b style="color:#b82323;">(Exceeded)</b>' : ''}</small>
            <div class="progress"><div class="progress-bar${over?' over':''}" style="width:${pct}%">${pct.toFixed(0)}%</div></div>
          </div>`;
      });
      if (html) { $('#salesProgress').html(html).show(); } else { $('#salesProgress').empty().hide(); }
    }

    /* ===== Order discount (reasons) ===== */
    let orderDiscountReasons = [];

    function renderReasonList($container, reasons){
      $container.empty();
      if (!Array.isArray(reasons) || reasons.length===0) reasons = [''];
      const isItem = $container.attr('id') === 'itemReasonsList';
      const nameAttr = isItem ? 'item_reasons[]' : 'order_reasons[]';
      const ariaBase = isItem ? 'Item discount reason' : 'Order discount reason';

      reasons.forEach((r,i)=>{
        const idx = i+1;
        const $row = $(`
          <div class="reason-row">
            <input type="text" class="form-control reason-input" name="${nameAttr}" aria-label="${ariaBase} ${idx}" value="${$('<div>').text(r||'').html()}">
            <button type="button" class="btn btn-outline-danger btn-xs remove-reason" aria-label="Remove reason ${idx}"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>
        `);
        $row.find('.remove-reason').on('click', function(){ $row.remove(); });
        $container.append($row);
      });
    }
    function readReasons($container){
      return $container.find('.reason-input').map(function(){
        const v = String($(this).val()||'').trim();
        return v ? v : null;
      }).get().filter(Boolean);
    }
    function updateOrderDiscountSummary(){
      const type = $('#cartDiscountType').val() || 'fixed';
      const val  = parseFloat($('#cartDiscountValue').val())||0;
      let text = (val>0) ? (type==='percent' ? `-${val.toFixed(2)}%` : `-${currency}${val.toFixed(2)}`) : 'None';
      if (val>0 && orderDiscountReasons.length){ text += ` • ${orderDiscountReasons.length} reason${orderDiscountReasons.length>1?'s':''}`; }
      $('#orderDiscountSummary').text(text);
    }

    $('#openOrderDiscountModal').on('click', function(){
      $('#orderDiscountType').val( ($('#cartDiscountType').length ? $('#cartDiscountType').val() : null) || 'percent' );
      $('#orderDiscountValue').val($('#cartDiscountValue').val()||'0.00');
      renderReasonList($('#orderReasonsList'), orderDiscountReasons);
      openModal('#orderDiscountModal');
    });
    $('#addOrderReason').on('click', function(){
      const $c = $('#orderReasonsList');
      const idx = $c.find('.reason-row').length + 1;
      $c.append(`
        <div class="reason-row">
          <input type="text" class="form-control reason-input" name="order_reasons[]" aria-label="Order discount reason ${idx}" placeholder="Reason">
          <button type="button" class="btn btn-outline-danger btn-xs remove-reason" aria-label="Remove reason ${idx}"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>`);
      $c.find('.remove-reason').last().on('click', function(){ $(this).closest('.reason-row').remove(); });
    });
    $('#saveOrderDiscount').on('click', function(){
      const type = $('#orderDiscountType').val();
      const val  = parseFloat($('#orderDiscountValue').val())||0;
      const reasons = readReasons($('#orderReasonsList'));
      if (val>0 && reasons.length===0){ toastr.error('Please add at least one reason for the order discount.'); return; }
      $('#cartDiscountType').val(type);
      $('#cartDiscountValue').val(val.toFixed(2));
      orderDiscountReasons = (val>0) ? reasons : [];
      updateOrderDiscountSummary();
      scheduleRenderCart();
      closeModal('#orderDiscountModal');
    });
    $('#clearOrderDiscount').on('click', function(){
      $('#cartDiscountValue').val('0.00'); $('#cartDiscountType').val('percent'); orderDiscountReasons = [];
      updateOrderDiscountSummary(); scheduleRenderCart();
    });

    /* ===== Render cart & totals ===== */
    let _renderRAF=null, _syncTimer=null;
    function scheduleRenderCart(){ if(_renderRAF) cancelAnimationFrame(_renderRAF); _renderRAF = requestAnimationFrame(renderCart); }
    function scheduleSync(){ clearTimeout(_syncTimer); _syncTimer=setTimeout(syncReservations,120); }

    function calcLineForItem(item, data){
      const isFlower = (item.isFlower !== undefined) ? item.isFlower :
        isFlowerFromFields(data.name, data.limit_category, data.selected_discount_tier_name || data.selected_discount_tier);

      const qty = Math.max(0, parseFloat(item.qty)||0);
      let linePre=0, displayUnitPrice=0;

      if (isFlower){
        const pf = priceFlowerFromTiersGeneric(data, qty);
        linePre = pf.total;
        displayUnitPrice = pf.rate;
      } else {
        const perUnit = parseFloat(data.price)||0;
        const units = Math.max(1, Math.floor(qty));
        linePre = perUnit * units;
        displayUnitPrice = perUnit;
      }

      let inline = 0;
      if (item.discountType && item.discountValue){
        const val = parseFloat(item.discountValue)||0;
        inline = item.discountType==='percent' ? linePre*(val/100) : Math.min(val, linePre);
      }
      const lineAfterInline = Math.max(0, linePre - inline);

      return { isFlower, qty, displayUnitPrice, linePre, lineAfterInline };
    }

    function renderCart(){
      let grossSubTotal=0, subTotal=0, taxableSubTotal=0, inlineDiscountTotal=0;
      const $tbody = $('#CartHTML').empty();

      cartOrder.forEach(id=>{
        const it = cart[id]; if(!it) return;
        const { data } = itemDataById(id); if(!data) return;

        const unitWeight = parseFloat(data.weight)||1;
        const M = calcLineForItem(it, data);

        grossSubTotal += M.linePre;
        subTotal      += M.lineAfterInline;
        inlineDiscountTotal += (M.linePre - M.lineAfterInline);
        if (!data.tax_exempt) taxableSubTotal += M.lineAfterInline;

        it.unit_price          = +M.displayUnitPrice.toFixed(2);
        it.line_total          = +M.linePre.toFixed(2);
        it.price               = M.isFlower ? it.line_total : it.unit_price;
        it.price_is_line_total = !!M.isFlower;

        const safeName = String(data.name||'').replace(/"/g,'&quot;');

        const qtyInputHtml = M.isFlower ? `
          <input type="text" class="form-control form-control-sm cart-qty cart-qty-flower" name="line_qty[]" aria-label="Quantity (in tiered grams) for &quot;${safeName}&quot;" value="${(parseFloat(it.qty)||0).toFixed(2).replace(/\.00$/,'')}" inputmode="decimal" placeholder="0.00"/>`
        : `
          <input type="number" class="form-control form-control-sm cart-qty" name="line_qty[]" aria-label="Quantity for &quot;${safeName}&quot;" value="${Math.max(1, Math.floor(parseFloat(it.qty)||1))}" step="1" min="1" inputmode="numeric"/>`;

        const discountBadge = (it.discountType && it.discountValue)
          ? `<span class="badge" style="background:#ffe082;color:#333;font-size:0.9em;">-${it.discountType==='percent' ? (parseFloat(it.discountValue)||0).toFixed(0)+'%' : currency+(parseFloat(it.discountValue)||0).toFixed(2)}</span>`
          : '';

        const reasonsInfo = Array.isArray(it.discountReasons) && it.discountReasons.length ? ` title="${it.discountReasons.join('; ').replace(/"/g,'&quot;')}"` : '';

        $tbody.append(`
          <tr data-id="${id}">
            <td>${data.name || ''}${discountBadge ? ' '+discountBadge : ''}${reasonsInfo ? ' <i class="fa fa-info-circle" aria-hidden="true"'+reasonsInfo+'></i>' : ''}</td>
            <td class="qty-cell">
              ${qtyInputHtml}
              ${M.isFlower ? `<div class="unit-readout" aria-live="polite">${((parseFloat(it.qty)||0) * unitWeight).toFixed(2)} g</div>` : ''}
            </td>
            <td class="price-per">${M.displayUnitPrice.toFixed(2)}</td>
            <td class="text-right line-after">${M.lineAfterInline.toFixed(2)}</td>
            <td>
              <button class="btn btn-xs btn-info item-discount" type="button" aria-haspopup="dialog" aria-controls="itemDiscountModal"><i class="fa fa-tag" aria-hidden="true"></i></button>
              <button class="btn btn-sm btn-danger remove-cart-item" type="button" aria-label="Remove ${safeName} from cart">&times;</button>
            </td>
          </tr>`);
      });

      const dtype = $('#cartDiscountType').val() || 'fixed';
      const dval  = parseFloat($('#cartDiscountValue').val())||0;
      let orderDiscount = 0;
      if (dval>0) orderDiscount = (dtype==='percent') ? (subTotal*(dval/100)) : Math.min(dval, subTotal);

      const taxablePortion = subTotal>0 ? (taxableSubTotal/subTotal) : 0;
      const taxAmount = ($('#customerType').val()||'').toLowerCase()==='consumer'
        ? ((taxableSubTotal - orderDiscount*taxablePortion) * (taxPct/100))
        : 0;

      const totalDue = (subTotal - orderDiscount + taxAmount);

      $('#p_subtotal').text(`${currency}${grossSubTotal.toFixed(2)}`);
      $('#p_discount').text(`- ${currency}${(inlineDiscountTotal+orderDiscount).toFixed(2)}`);
      $('#p_hst').text(`${currency}${taxAmount.toFixed(2)}`);
      $('.TotalAmount').text(`${currency}${totalDue.toFixed(2)}`);

      checkCategoryLimits();
      renderSalesProgress();
      scheduleSync();
      updateOrderDiscountSummary();
    }
    window.renderCart = renderCart;

    function softUpdateRow($row){
      const id = $row.data('id'), it = cart[id]; if(!it) return;
      const { data } = itemDataById(id); if(!data) return;
      const M = calcLineForItem(it, data);
      $row.find('.price-per').text(M.displayUnitPrice.toFixed(2));
      $row.find('.line-after').text(M.lineAfterInline.toFixed(2));
      if (M.isFlower){
        const unitWeight = parseFloat(data.weight)||1;
        $row.find('.unit-readout').text(`${((parseFloat(it.qty)||0)*unitWeight).toFixed(2)} g`);
      }
    }

    /* ===== totals-only recalculation ===== */
    function recalcTotalsOnly(){
      let grossSubTotal=0, subTotal=0, taxableSubTotal=0, inlineDiscountTotal=0;

      cartOrder.forEach(id=>{
        const it = cart[id]; if(!it) return;
        const { data } = itemDataById(id); if(!data) return;
        const M = calcLineForItem(it, data);
        grossSubTotal       += M.linePre;
        subTotal            += M.lineAfterInline;
        inlineDiscountTotal += (M.linePre - M.lineAfterInline);
        if (!data.tax_exempt) taxableSubTotal += M.lineAfterInline;
      });

      const dtype = $('#cartDiscountType').val() || 'fixed';
      const dval  = parseFloat($('#cartDiscountValue').val())||0;
      let orderDiscount = 0;
      if (dval>0) orderDiscount = (dtype==='percent') ? (subTotal*(dval/100)) : Math.min(dval, subTotal);

      const taxablePortion = subTotal>0 ? (taxableSubTotal/subTotal) : 0;
      const taxAmount = ($('#customerType').val()||'').toLowerCase()==='consumer'
        ? ((taxableSubTotal - orderDiscount*taxablePortion) * (taxPct/100))
        : 0;

      const totalDue = (subTotal - orderDiscount + taxAmount);

      $('#p_subtotal').text(`${currency}${grossSubTotal.toFixed(2)}`);
      $('#p_discount').text(`- ${currency}${(inlineDiscountTotal+orderDiscount).toFixed(2)}`);
      $('#p_hst').text(`${currency}${taxAmount.toFixed(2)}`);
      $('.TotalAmount').text(`${currency}${totalDue.toFixed(2)}`);

      checkCategoryLimits();
      renderSalesProgress();
      updateOrderDiscountSummary();
    }

    let softTotalsRAF=null;
    function softUpdateTotals(){
      if (softTotalsRAF) return;
      softTotalsRAF = requestAnimationFrame(()=>{
        softTotalsRAF=null;
        recalcTotalsOnly();
      });
    }

    $(document).on('input', '.cart-qty-flower', function(){
      const $row = $(this).closest('tr'), id = $row.data('id'), it = cart[id]; if(!it) return;
      const raw = String(this.value||'').replace(',', '.').replace(/[^0-9.]/g,'');
      const parsed = parseFloat(raw);
      it.qty = isNaN(parsed) ? 0 : Math.max(0.01, +parsed.toFixed(2));
      softUpdateRow($row); softUpdateTotals(); scheduleSync();
    });
    $(document).on('blur', '.cart-qty-flower', function(){
      const $row = $(this).closest('tr'), id = $row.data('id'), it = cart[id]; if(!it) return;
      if(!isFinite(it.qty) || it.qty<=0) it.qty=0.01;
      this.value = (+it.qty).toFixed(2).replace(/\.00$/,'');
      softUpdateRow($row); softUpdateTotals();
    });
    $(document).on('keydown', '.cart-qty-flower', function(e){ if(e.key==='Enter'){ e.preventDefault(); $(this).blur(); } });

    $(document).on('input', 'input.cart-qty:not(.cart-qty-flower)', function(){
      const $row = $(this).closest('tr'), id = $row.data('id'), it = cart[id]; if(!it) return;
      let num = parseInt(this.value, 10); if(!isFinite(num)||num<1) num=1;
      it.qty = num; softUpdateRow($row); softUpdateTotals(); scheduleSync();
    });

    /* ===== Cart add/remove ===== */
    $(document).on('click', '.add-to-cart-btn', function(){
      const $c = $(this).closest('.inventory-card');
      const id = $c.data('id');
      const data = itemDataById(id).data;
      if (!data) return;
      if (getAvail(id) <= 0.0001) { toastr.warning('No stock available for this item.'); return; }

      const isFl = isFlowerFromFields(data.name, data.limit_category, data.selected_discount_tier_name || data.selected_discount_tier);
      if(cart[id]){ cart[id].qty = isFl ? parseFloat((parseFloat(cart[id].qty||0)+1).toFixed(2)) : (parseInt(cart[id].qty||0)+1); }
      else { cart[id] = { id, name: data.name, qty: isFl ? 1 : 1, isFlower: isFl, discountReasons: [] }; cartOrder.push(id); }
      scheduleRenderCart();
    });

    $(document).on('click', '.remove-cart-item', function(){
      const id = $(this).closest('tr').data('id');
      if (myResv[id]) { releaseAjax(id, myResv[id], function(){ delete myResv[id]; }); }
      delete cart[id];
      cartOrder = cartOrder.filter(x=>x!==id);
      scheduleRenderCart();
    });
    $(document).on('click', '#ClearCart', function(){ releaseAllReservations(); cart = {}; cartOrder = []; scheduleRenderCart(); });

    /* ===== Item discount modal ===== */
    let currentDiscountRowId = null;
    $(document).on('click','.item-discount',function(){
      const id = $(this).closest('tr').data('id');
      currentDiscountRowId = id;
      const item = cart[id] || {};
      $('#discountType').val(item.discountType||'percent');
      $('#discountValue').val(item.discountValue||'');
      renderReasonList($('#itemReasonsList'), Array.isArray(item.discountReasons)? item.discountReasons : []);
      openModal('#itemDiscountModal');
    });
    $('#addItemReason').on('click', function(){
      const $c = $('#itemReasonsList');
      const idx = $c.find('.reason-row').length + 1;
      $c.append(`
        <div class="reason-row">
          <input type="text" class="form-control reason-input" name="item_reasons[]" aria-label="Item discount reason ${idx}" placeholder="Reason">
          <button type="button" class="btn btn-outline-danger btn-xs remove-reason" aria-label="Remove reason ${idx}"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>`);
      $c.find('.remove-reason').last().on('click', function(){ $(this).closest('.reason-row').remove(); });
    });
    $('#saveItemDiscount').on('click', function(){
      const id = currentDiscountRowId;
      if (!id || !cart[id]) return;
      const type = $('#discountType').val();
      const val  = parseFloat($('#discountValue').val())||0;
      const reasons = readReasons($('#itemReasonsList'));
      if (val>0 && reasons.length===0){ toastr.error('Please add at least one reason for the item discount.'); return; }
      cart[id].discountType = (val>0) ? type : null;
      cart[id].discountValue = (val>0) ? val : 0;
      cart[id].discountReasons = (val>0) ? reasons : [];
      closeModal('#itemDiscountModal');
      scheduleRenderCart();
    });

    /* ===== Hold orders (local) ===== */
    function getHeldOrders(){ return JSON.parse(localStorage.getItem('heldOrders')||'[]'); }
    function saveHeldOrders(h){ localStorage.setItem('heldOrders',JSON.stringify(h)); }
    function updateHoldCountBadge(){ const c=getHeldOrders().length, $b=$('#holdCountBadge'); c>0?$b.text(c).show():$b.hide(); }
    function addHeldOrder(o){ let h=getHeldOrders(); h.push(o); saveHeldOrders(h); updateHoldCountBadge(); }
    function removeHeldOrder(i){ let h=getHeldOrders(); h.splice(i,1); saveHeldOrders(h); updateHoldCountBadge(); }
    function renderHoldOrdersTable(){
      const holds = getHeldOrders(), $tb = $('#holdOrdersTable tbody').empty();
      $('#noHoldsMsg').toggle(holds.length===0);
      holds.forEach((h,i)=>{
        const created = new Date(h.held_at).toLocaleString(),
              count = (h.cart||[]).reduce((s,it)=>s+Number(it.quantity||0),0);
        $tb.append(`
          <tr>
            <td>${i+1}</td>
            <td>${h.customerContact||''}</td>
            <td>${h.customerType||''}</td>
            <td>${count}</td>
            <td>${created}</td>
            <td>
              <button class="btn btn-sm btn-success reinstateHoldBtn" data-idx="${i}" type="button"><i class="fa fa-upload" aria-hidden="true"></i> Reinstate</button>
              <button class="btn btn-sm btn-danger deleteHoldBtn" data-idx="${i}" type="button" aria-label="Delete held order ${i+1}"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
          </tr>`);
      });
    }
    $('#holdOrders').on('click',function(){ renderHoldOrdersTable(); openModal('#holdOrdersModal'); });

    $('#OrderType').on('change', function(){
      if (this.value === 'order') { $('#checkoutBtn').hide(); $('#holdOrderBtn').show(); }
      else { $('#holdOrderBtn').hide(); $('#checkoutBtn').show(); }
    }).trigger('change');

    $(document).on('click','#holdOrderBtn',function(){
      const contact = $('#customerContact').val().trim();
      if(!contact){ toastr.error('Enter a customer name or email to hold the order.'); $('#customerContact').focus(); return; }
      releaseAllReservations();

      const cartArr = cartOrder.map(id => {
        const it = cart[id];
        return {
          product_id:            it.id,
          quantity:              it.qty,
          price:                 it.price,
          price_is_line_total:   !!it.price_is_line_total,
          unit_price:            it.unit_price ?? null,
          line_total:            it.line_total ?? null,
          inline_discount_type:  it.inline_discount_type || it.discountType || null,
          inline_discount_value: it.inline_discount_value ?? it.discountValue ?? 0,
          inline_discount_reasons: Array.isArray(it.discountReasons) ? it.discountReasons : []
        };
      });

      const holdObj = {
        cart: cartArr,
        order_discount_type: $('#cartDiscountType').val(),
        order_discount_value: parseFloat($('#cartDiscountValue').val()) || 0,
        order_discount_reasons: Array.isArray(orderDiscountReasons) ? orderDiscountReasons : [],
        customerContact: contact,
        customerType: $('#customerType').val(),
        order_type: 'order',
        held_at: new Date().toISOString()
      };

      addHeldOrder(holdObj);
      cart={}; cartOrder=[]; scheduleRenderCart();
      $('#orderDiscountValue').val('');
      toastr.success('Order held for '+contact+'!');
    });

    $(document).on('click','.reinstateHoldBtn',function(){
      const idx = $(this).data('idx'), h = getHeldOrders()[idx];
      if(!h) return;
      cart = {}; cartOrder = [];

      $('#cartDiscountType').val(h.order_discount_type || 'fixed');
      $('#cartDiscountValue').val((parseFloat(h.order_discount_value||0)).toFixed(2));
      orderDiscountReasons = Array.isArray(h.order_discount_reasons) ? h.order_discount_reasons : [];
      updateOrderDiscountSummary();

      (h.cart||[]).forEach(row => {
        const isFl = !!row.price_is_line_total;
        cart[row.product_id] = {
          id: row.product_id,
          name: row.name,
          qty: isFl ? parseFloat(row.quantity) : Math.max(1, parseInt(row.quantity, 10) || 1),
          isFlower: isFl,
          price: row.price,
          unit_price: row.unit_price ?? (isFl && row.line_total && row.quantity ? (parseFloat(row.line_total)/parseFloat(row.quantity)) : null),
          line_total: row.line_total ?? (isFl ? row.price : null),
          price_is_line_total: isFl,
          discountType: row.inline_discount_type || null,
          discountValue: parseFloat(row.inline_discount_value) || 0,
          discountReasons: Array.isArray(row.inline_discount_reasons) ? row.inline_discount_reasons : []
        };
        cartOrder.push(row.product_id);
      });

      $('#customerContact').val(h.customerContact||'');
      $('#customerType').val(h.customerType||'consumer');
      $('#OrderType').val('pos').trigger('change');

      closeModal('#holdOrdersModal'); removeHeldOrder(idx); scheduleRenderCart();
    });
    $(document).on('click','.deleteHoldBtn',function(){ removeHeldOrder($(this).data('idx')); renderHoldOrdersTable(); });
    updateHoldCountBadge();

    /* ===== Checkout open ===== */
    $(document).on('click','#checkoutBtn',function(){
      $('#checkoutTotal').val($('.TotalAmount').text().replace(/[^0-9.]/g, ''));
      $('#checkoutError').addClass('d-none').text('');
      openModal('#checkoutModal');
      updateCardFields();
    });

    /* ===== Payment UI & submit ===== */
    function computeChange(total, payType, cash, card){
      const t   = Number(total) || 0;
      const csh = Number(cash)  || 0;
      const crd = Number(card)  || 0;
      let diff = 0;

      if (payType === 'cash')       diff = csh - t;
      else if (payType === 'card')  diff = crd - t;
      else if (payType === 'split') diff = (csh + crd) - t;

      return Math.max(0, Number(diff.toFixed(2)));
    }

    function updateCardFields(){
      const type  = $('#paymentType').val();
      const total = parseFloat($('#checkoutTotal').val()) || 0;
      const cash  = readCurrencyInput($('#cashPaid').val());

      if (type === 'card') {
        $('#cashPaidGroup, #changeDueGroup').hide();
        $('#cardTotalGroup, #cardLast4Group').show();
        $('#cardTotal')
          .prop('readonly', false)
          .attr('placeholder', formatMoney(total));
        $('#cardHint').text('');
        $('#cardLast4').prop('required', true);
        $('#changeDue').val('0.00');

      } else if (type === 'cash') {
        $('#cashPaidGroup, #changeDueGroup').show();
        $('#cardTotalGroup, #cardLast4Group').hide();
        $('#cardLast4').prop('required', false).val('');
        const change = computeChange(total, 'cash', cash, 0);
        $('#changeDue').val(change.toFixed(2));

      } else { // split
        $('#cashPaidGroup, #changeDueGroup, #cardTotalGroup, #cardLast4Group').show();
        $('#cardTotal').prop('readonly', false);

        const suggested = Math.max(0, total - cash);
        $('#cardTotal').attr('placeholder', formatMoney(suggested));
        $('#cardHint').text('Tip: charge the card for the placeholder amount if cash is exact.');
        $('#cardLast4').prop('required', true);

        const cardEntered = readCurrencyInput($('#cardTotal').val());
        const change = computeChange(total, 'split', cash, cardEntered);
        $('#changeDue').val(change.toFixed(2));
      }
    }
    $('#paymentType').on('change', updateCardFields);
    $('#cashPaid').on('input', function(){ updateCardFields(); });

    $('#cardTotal').on('input', function(){
      const raw = sanitizeNumericString(this.value);
      this.value = raw;
      updateCardFields();
    });
    $('#cardTotal').on('blur', function(){
      const n = readCurrencyInput(this.value);
      this.value = n > 0 ? formatMoney(n) : '';
    });
    $('#cardTotal').on('focus', function(){
      const n = readCurrencyInput(this.value);
      this.value = n ? n.toFixed(2) : '';
      this.select?.();
    });

    $('#checkoutForm').on('submit', function(e){
      e.preventDefault();
      const total = parseFloat($('#checkoutTotal').val() || 0);
      if (!isFinite(total) || total < 0.01) {
        $('#checkoutError').removeClass('d-none').text('Cart is empty or total is invalid.');
        return;
      }

      const payType = $('#paymentType').val();
      const cash = readCurrencyInput($('#cashPaid').val());
      const card = readCurrencyInput($('#cardTotal').val());
      const last4 = String($('#cardLast4').val()||'').trim();

      if (payType === 'cash' && cash < total) {
        $('#checkoutError').removeClass('d-none').text('Cash received is less than total due.');
        return;
      }
      if (payType === 'card') {
        if (!isFinite(card) || card <= 0) {
          $('#checkoutError').removeClass('d-none').text('Enter a valid card amount.');
          return;
        }
        if (!/^\d{4}$/.test(last4)) {
          $('#checkoutError').removeClass('d-none').text('Please enter the last 4 digits of the card.');
          return;
        }
      }
      if (payType === 'split') {
        if (!/^\d{4}$/.test(last4)) {
          $('#checkoutError').removeClass('d-none').text('Please enter the last 4 digits of the card.');
          return;
        }
        if (cash + card < total - 0.005) {
          $('#checkoutError').removeClass('d-none').text('Cash + Card must cover the total.');
          return;
        }
      }

      // Build items for cart[]
      const items = cartOrder.map(id => {
        const {data} = itemDataById(id);
        const row = cart[id];
        const line = calcLineForItem(row, data);

        const unitPrice = row.unit_price;             // number
        const lineAfter = +line.lineAfterInline.toFixed(2);
        const priceIsLine = !!row.price_is_line_total;

        return {
          product_id: id,
          name: data.name,
          quantity: row.isFlower ? parseFloat(row.qty) : Math.max(1, parseInt(row.qty, 10) || 1),
          price: priceIsLine ? lineAfter : (unitPrice ?? 0),
          unit_weight: data.weight,
          unit_price: unitPrice,
          line_total: lineAfter,
          price_is_line_total: priceIsLine,
          inline_discount_type: row.discountType || null,
          inline_discount_value: parseFloat(row.discountValue||0) || 0,
          inline_discount_reasons: Array.isArray(row.discountReasons)? row.discountReasons: [],
          sku: data.sku,
          label: data.label,
          tax_exempt: !!data.tax_exempt,
          category_id: data.category_id,
          limit_category: data.limit_category
        };
      });

      const subtotalText = $('#p_subtotal').text().replace(/[^0-9.]/g,'');
      const discountText = $('#p_discount').text().replace(/[^0-9.]/g,'');
      const taxText      = $('#p_hst').text().replace(/[^0-9.]/g,'');

      const requestBody = {
        cart: items,
        payment_type: payType,
        total_amount: +total.toFixed(2),

        ...( (payType === 'card' || payType === 'split') && {
          cardTotal: +card.toFixed(2),
          cardLast4: last4
        }),

        card_total: (payType === 'card' || payType === 'split') ? +card.toFixed(2) : 0,
        card_last4: (payType === 'card' || payType === 'split') ? last4 : '',

        cashReceived: (payType==='cash' || payType==='split') ? +cash.toFixed(2) : 0,
        cash_received: (payType==='cash' || payType==='split') ? +cash.toFixed(2) : 0,

        order_discount: {
          type: $('#cartDiscountType').val() || 'fixed',
          value: +(parseFloat($('#cartDiscountValue').val())||0).toFixed(2),
          reasons: orderDiscountReasons
        },

        subtotal: +(parseFloat(subtotalText)||0).toFixed(2),
        discount_total: +(parseFloat(discountText)||0).toFixed(2),
        tax_total: +(parseFloat(taxText)||0).toFixed(2),

        tax_breakdown: {
          county: +(parseFloat($('#payload_county_tax').val())||0).toFixed(2),
          city:   +(parseFloat($('#payload_city_tax').val())||0).toFixed(2),
          state:  +(parseFloat($('#payload_state_tax').val())||0).toFixed(2),
          vat:    +(parseFloat($('#vat').val())||0).toFixed(2)
        },

        drawer_session_id: $('#drawer_session_id').val(),
        order_type: $('#OrderType').val(),
        customer_type: $('#customerType').val(),
        customer_contact: $('#customerContact').val(),
        medical: {
          med_number: $('#medNumber').val()||'',
          issued_date: $('#issuedDate').val()||'',
          expiration_date: $('#expirationDate').val()||'',
          caregiver_number: $('#caregiverNumber').val()||''
        },
        receipt_email: getCheckoutEmail()
      };

      $('#confirmCheckoutBtn').prop('disabled', true).addClass('disabled');

      $.ajax({
        url: "{{ route('sales.complete_sale') }}",
        method: 'POST',
        data: JSON.stringify(requestBody),
        contentType: 'application/json',
        dataType: 'json',
        processData: false,
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json'
        },
        success: function(res){
          toastr.success('Sale completed!');

          /* ----- Direct thermal receipt (80mm) + simple labels (from this page) ----- */
          const receiptHtml = buildThermalReceiptHTML(res, requestBody, (window.BUSINESS||{}));
          printViaIframe(receiptHtml, 'print_iframe_receipt');

          // Label options from modal (use current settings)
          const labelOpts = {
            width:  parseFloat($('#labelWidth').val()) || 4,
            height: parseFloat($('#labelHeight').val()) || 2,
            weight: parseFloat($('#labelWeight').val()) || '',
            includeQr: $('#includeQr').is(':checked'),
            notes:  $('#labelNotes').val() || ''
          };

          // We print one label per cart line by default
          const labelDoc = buildLabelDocHTML(requestBody.cart || [], labelOpts);
          setTimeout(()=> printViaIframe(labelDoc, 'print_iframe_labels'), 500);

          /* ----- NEW: also open full receipt/labels page (METRC-driven) ----- */
          const saleId =
            (res && (res.sale?.id || res.id || res.sale_id || res.data?.id || res.data?.sale_id)) || null;
          if (saleId) {
            window.open(`${window.SALES_RECEIPT_URL_BASE}/${saleId}/receipt?auto=1`, '_blank');
          }

          // Compute and show Change Due modal
          const change = computeChange(
            requestBody.total_amount,
            requestBody.payment_type,
            parseFloat(requestBody.cashReceived || requestBody.cash_received || 0),
            parseFloat(requestBody.cardTotal || requestBody.card_total || 0)
          );
          const payCard = (requestBody.payment_type==='card' || requestBody.payment_type==='split') ? (requestBody.cardTotal || requestBody.card_total || 0) : 0;
          const payCash = (requestBody.payment_type==='cash' || requestBody.payment_type==='split') ? (requestBody.cashReceived || requestBody.cash_received || 0) : 0;

          $('#changeDueBig').text(formatMoney(change));
          $('#changeBreakdown').html([
            `Total: <b>${formatMoney(requestBody.total_amount)}</b>`,
            `Cash: <b>${formatMoney(payCash)}</b>`,
            `Card: <b>${formatMoney(payCard)}</b>`
          ].join('<br>'));

          // Clear everything
          releaseAllReservations();
          cart = {}; cartOrder = []; scheduleRenderCart();
          $('#checkoutForm')[0].reset();
          window.closeModal('#checkoutModal');
          openModal('#changeDueModal');
        },
        error: function(xhr){
          const j = xhr?.responseJSON;
          let msg = j?.message || 'Checkout failed. Please try again.';
          if (j?.errors && typeof j.errors === 'object') {
            const lines = [];
            Object.keys(j.errors).forEach(k => {
              const arr = j.errors[k];
              if (Array.isArray(arr)) arr.forEach(m => lines.push(`${k}: ${m}`));
            });
            if (lines.length) msg = lines.join('\n');
          }
          $('#checkoutError').removeClass('d-none').text(msg);
          toastr.error('Checkout failed');
        },
        complete: function(){
          $('#confirmCheckoutBtn').prop('disabled', false).removeClass('disabled');
        }
      });

    });

    /* ===== Medical fields toggle ===== */
    $('#customerType').on('change', function(){
      const type = (this.value||'').toLowerCase();
      if (type === 'patient' || type === 'caregiver') {
        $('#medicalFields').slideDown(120);
        $('#caregiverNumberGroup').toggle(type==='caregiver');
      } else {
        $('#medicalFields').slideUp(120);
      }
      scheduleRenderCart();
    }).trigger('change');

    /* ===== Barcode focus & scan ===== */
    const INPUT_TAGS = /^(input|textarea|select)$/i;
    function isTypingInForm() {
      const a = document.activeElement;
      if (!a) return false;
      if (a === document.getElementById('barcodeInput')) return false;
      if (INPUT_TAGS.test(a.tagName)) return true;
      if (a.isContentEditable) return true;
      return false;
    }
    let lastUserTypeAt = 0;
    document.addEventListener('keydown', (e) => {
      if (e.target && (INPUT_TAGS.test(e.target.tagName) || e.target.isContentEditable)) {
        lastUserTypeAt = Date.now();
      }
    });
    function focusBarcodeIfIdle() {
      const now = Date.now();
      if (document.querySelector('.modal.show, .modal.in')) return;
      if (isTypingInForm()) return;
      if (now - lastUserTypeAt < 4000) return;
      const bi = document.getElementById('barcodeInput');
      if (bi && document.activeElement !== bi) bi.focus();
    }
    setInterval(focusBarcodeIfIdle, 1000);

    window.simScan = function(code){ handleBarcode(String(code||''), { allowFuzzy:false }); };

    const $barcode = $('#barcodeInput');
    $barcode.on('focus', function(){ this.setSelectionRange?.(this.value.length, this.value.length); });

    function handleBarcode(raw, opts = {}) {
      const allowFuzzy = !!opts.allowFuzzy;
      const code = String(raw||'').trim();
      if (!code) return;

      const qLower = code.toLowerCase();

      // 1) Exact SKU match (preferred)
      let match = inventoryList.find(it => String(it.sku||'').toLowerCase() === qLower);

      // 2) Exact Label match
      if (!match) match = inventoryList.find(it => String(it.label||'').toLowerCase() === qLower);

      // 3) Optional: fuzzy fallback only if explicitly allowed
      if (!match && allowFuzzy) match = inventoryList.find(it => it._index.indexOf(qLower) !== -1);

      if (match) {
        const id = match.id;
        if (getAvail(id) > 0) {
          if (cart[id]) {
            cart[id].qty = cart[id].isFlower
              ? parseFloat((parseFloat(cart[id].qty||0) + 1).toFixed(2))
              : ((parseInt(cart[id].qty||0) + 1));
          } else {
            const isFl = isFlowerFromFields(match.name, match.limit_category, match.selected_discount_tier_name || match.selected_discount_tier);
            cart[id] = { id, name: match.name, qty: 1, isFlower: isFl, discountReasons: [] };
            cartOrder.push(id);
          }
          scheduleRenderCart();
        } else {
          toastr.info('Scanned item found but currently out of stock.');
        }
      } else {
        toastr.info('No exact SKU/Label match for: ' + code);
      }
    }

    let scanTimer = null;
    const SCAN_IDLE_MS = 120;
    const MIN_LEN = 3;

    $barcode.on('input', function(){
      const val = String(this.value||'');
      if (!val.trim()) return;
      clearTimeout(scanTimer);
      scanTimer = setTimeout(() => {
        const code = String($barcode.val()||'').trim();
        if (code.length >= MIN_LEN) handleBarcode(code, { allowFuzzy:false });
        $barcode.val('');
      }, SCAN_IDLE_MS);
    });
    $barcode.on('keydown', function(e){
      if (e.key === 'Enter' || e.key === 'NumpadEnter') {
        e.preventDefault();
        clearTimeout(scanTimer);
        const code = String($barcode.val()||'').trim();
        if (code.length >= MIN_LEN) handleBarcode(code, { allowFuzzy:false });
        $barcode.val('');
      }
    });

    (function(){
      let buf = '';
      let lastAt = 0;
      const GAP_MS = 80;

      document.addEventListener('keydown', function(e){
        if (isTypingInForm() || document.querySelector('.modal.show, .modal.in')) return;

        const now = Date.now();
        if (now - lastAt > GAP_MS) buf = '';
        lastAt = now;

        if (e.key === 'Enter' || e.key === 'NumpadEnter') {
          if (buf.length >= MIN_LEN) handleBarcode(buf, { allowFuzzy:false });
          buf = '';
          return;
        }

        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
          buf += e.key;
        }
      });
    })();

    /* ===== Label preview (manual trigger still works) ===== */
    let currentLabelItem = null;
    $(document).on('click','.print-label', function(){
      const { data } = itemDataById($(this).closest('.inventory-card').data('id'));
      currentLabelItem = data;
      updateLabelPreview();
      openModal('#printLabelModal');
    });
    function updateLabelPreview(){
      const w = parseFloat($('#labelWidth').val()) || 4;
      const h = parseFloat($('#labelHeight').val()) || 2;
      const weight = $('#labelWeight').val() || '';
      const notes = $('#labelNotes').val() || '';
      const includeQr = $('#includeQr').is(':checked');
      if (!currentLabelItem) return;
      const sku = currentLabelItem.sku || '';
      $('#labelPreview').html(`
        <div style="width:${w}in;height:${h}in;border:1px dashed #999;padding:6px;font-family:Arial, sans-serif;">
          <div style="font-size:14px;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${currentLabelItem.name||''}</div>
          <div style="font-size:12px;">Pkg: ${(currentLabelItem.label||'').slice(-8)}</div>
          <div style="font-size:12px;">Price: ${toMoney(currentLabelItem.price||0)}</div>
          <div style="font-size:12px;">Weight: ${weight? weight+' g':''}</div>
          ${notes ? `<div style="font-size:11px;color:#444;">${notes}</div>`:''}
          ${includeQr ? `<div style="margin-top:6px;"><img alt="QR" style="height:1.1in" src="/qr?text=${encodeURIComponent(sku||currentLabelItem.label||currentLabelItem.name||'')}&size=200"></div>`:''}
        </div>
      `);
    }
    $('#printLabelForm input, #printLabelForm checkbox').on('input change', updateLabelPreview);
    $('#printLabelConfirm').on('click', function(){
      if (!currentLabelItem) return;
      const html = buildLabelDocHTML([{
        name: currentLabelItem.name,
        label: currentLabelItem.label,
        sku: currentLabelItem.sku,
        price: currentLabelItem.price,
        unit_price: currentLabelItem.price,
        line_total: currentLabelItem.price,
        price_is_line_total: false
      }], {
        width:  parseFloat($('#labelWidth').val()) || 4,
        height: parseFloat($('#labelHeight').val()) || 2,
        weight: parseFloat($('#labelWeight').val()) || '',
        includeQr: $('#includeQr').is(':checked'),
        notes:  $('#labelNotes').val() || ''
      });
      printViaIframe(html, 'print_iframe_manual_label');
      closeModal('#printLabelModal');
    });

    /* ===== Hold toggle (server) ===== */
    $(document).on('change', '.hold-toggle', function(){
      const $chk = $(this);
      const id = $chk.data('id');
      const hold = $chk.is(':checked') ? 1 : 0;
      $chk.prop('disabled', true);

      $.ajax({
        url: `/inventory/${id}/hold`,
        method: 'POST',
        data: { hold, _token: CSRF },
        success: function(res){
          const $card = $chk.closest('.inventory-card');
          const btn = $card.find('.add-to-cart-btn');
          if (hold){
            $card.find('.result-meta .field .badge-hold').length || $card.find('.result-meta').append(`<span class="field"><span class="badge-hold">On Hold</span></span>`);
            btn.prop('disabled', true);
          } else {
            $card.find('.badge-hold').closest('.field').remove();
            if (getAvail(id) > 0) btn.prop('disabled', false);
          }
          toastr.success(hold ? 'Item moved to hold.' : 'Item returned to available.');
        },
        error: function(xhr){
          toastr.error(xhr?.responseJSON?.message || 'Failed to toggle hold.');
          $chk.prop('checked', !hold); // revert
        },
        complete: function(){ $chk.prop('disabled', false); }
      });
    });

    /* ===== Initial render ===== */
    $('#inventorySearch').trigger('input');

  }); // jQuery ready
})(jQuery);
</script>
@endsection
