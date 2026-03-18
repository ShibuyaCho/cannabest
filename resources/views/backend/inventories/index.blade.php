{{-- resources/views/inventories/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php use Illuminate\Support\Str; @endphp

<link href="{{ asset('assets/css/plugins/toastr/toastr.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .wrapper { max-width:1280px; margin:0 auto; padding:0 16px; }
  .position-relative { width:100%; }
  .search-bar { width:100%; padding:.75rem 1rem; font-size:1.2rem; border:1px solid #ddd; border-radius:12px; }
  .clear-btn { right:1rem; cursor:pointer; position:absolute; top:50%; transform:translateY(-50%); font-size:1.5rem; color:#aaa; }

  .custom-card { position:relative; display:flex; gap:16px; padding:16px; background:#fff; border:1px solid #ddd; border-radius:12px; transition:box-shadow .2s; }
  .custom-card:hover { box-shadow:0 2px 8px rgba(0,0,0,0.1); }
  .delete-x { position:absolute; top:8px; left: 8px; right: auto; border:none; background:transparent; color:#e74c3c; font-size:1.2rem; cursor:pointer; }
  .custom-img-container { display:flex; flex-direction:column; align-items:center; flex:0 0 100px; }
  .custom-img-container img { width:100px; height:100px; border-radius:8px; object-fit:cover; }
  .actions-col { display:grid; grid-template-columns:repeat(4,auto); gap:8px; margin-top:12px; }
  .actions-col button, .actions-col label { font-size:.8rem; padding:4px 6px; cursor:pointer; }
  .actions-col label { display:flex; align-items:center; gap:4px; }
  .card-content { flex:1; display:flex; flex-direction:column; }
  .custom-name { font-weight:900; font-size:1.6rem; margin-bottom:8px; }
  .custom-details { display:flex; flex-wrap:wrap; gap:8px 16px; font-size:1.05rem; color:#555; }
  .custom-field { flex:1 1 150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .lab-label { font-weight:600; color:#159048; }
  .subtype-badge { font-weight:600; border:1px solid #e5e7eb; background:#f9fafb; padding:2px 6px; border-radius:6px; }

  /* STAGES */
  .stage { display:none; }
  .stage.active { display:block; }
  .stage-animate-in { animation: zoomIn .2s ease-out; }
  @keyframes zoomIn { from{ transform:scale(.98); opacity:.0; } to{ transform:scale(1); opacity:1; } }

  .category-grid {
    list-style:none; margin:0; padding:0;
    display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:16px;
  }
  .category-card {
    cursor:pointer; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px;
    transition: box-shadow .2s, transform .08s;
  }
  .category-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.08); }
  .category-card:active { transform: scale(.99); }
  .category-card .title { font-weight:800; font-size:1.2rem; }
  .category-card .meta { color:#6b7280; margin-top:6px; }

  .detail-header { display:flex; align-items:center; gap:12px; margin: 0 0 12px; }
  .detail-title { font-size:1.6rem; font-weight:900; }
  .back-btn { white-space:nowrap; }

  /* Two-column masonry via CSS columns */
  .cards-list { list-style:none; margin:0; padding:0; column-count: 2; column-gap: 20px; }
  .inventory-card { display:inline-block; width:100%; margin:0 0 20px; break-inside:avoid; }
  @media (max-width: 1024px){ .cards-list{ column-count: 1; } }

  .list-view .cards-list{ column-count: 1; }
  .list-view .custom-card {
    display: grid; grid-template-columns: 64px 1fr auto;
    gap: 12px; padding: 10px 12px; border-radius: 10px;
  }
  .list-view .custom-img-container { align-items: center; flex: 0 0 auto; }
  .list-view .custom-img-container img { width: 56px; height: 56px; border-radius: 6px; }
  .list-view .custom-name { font-size: 1.05rem; margin: 0 0 2px; font-weight: 700; }
  .list-view .custom-details { font-size: 0.95rem; gap: 4px 10px; }
  .list-view .actions-col { grid-template-columns: repeat(3, auto); gap: 6px; margin-top: 0; align-content: start; }
  .list-view .custom-field[data-secondary="1"] { display: none; }

  #labelPreviewContainer { overflow:hidden; width:4in; height:2in; margin:0 auto; }
  #labelPreview { display:flex; width:4in; height:2in; background:#fff; }
  .label-column { flex:1; padding:0.1in; box-sizing:border-box; font-family:sans-serif; font-size:10pt; line-height:1.2; }
  .label-column + .label-column { border-left:1px solid #000; }
  .label-column h1 { font-size:12pt; margin:0 0 0.05in; font-weight:bold; }
  .label-column p, .label-column img { margin:0 0 0.05in; display:block; }
  .label-column p { font-size:8pt; line-height:1.1; }
  .label-column img { width:.5in !important; height:.5in !important; }

  @media print { body, html { margin:0; padding:0; } }

  .toolbar-row{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    flex-wrap:wrap; margin-bottom:8px;
  }
  .filters-right .form-inline { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .filters-right .btn-reset{ padding:4px 8px; }

  /* Print Columns Modal */
  #columnList { list-style:none; margin:0; padding:0; }
  #columnList li {
    display:flex; align-items:center; gap:8px;
    padding:6px 8px; border:1px solid #e5e7eb; border-radius:8px;
    margin-bottom:6px; background:#fafafa;
  }
  #columnList .handle { cursor:ns-resize; color:#888; user-select:none; }
  #columnList .name { flex:1; }
  #columnList .move-btn { padding:2px 6px; }

  /* NEW: hide subtype rows unless a subtype is actively filtered */
  .subtype-field{ display:none; }
  body.subtype-active .subtype-field{ display:block; }

  /* CSV import modal output */
  pre.import-output{background:#0b1021;color:#e6e8ff;padding:10px;border-radius:8px;max-height:260px;overflow:auto;}
</style>

<div class="wrapper wrapper-content">
  <div class="mb-2" style="display:flex; gap:8px; flex-wrap:wrap;">
    <button id="syncMetrc" class="btn btn-warning">
      <i class="fa fa-refresh"></i> Sync METRC Now
    </button>

    {{-- NEW: Upload CSV & run ImportInventoriesSeeder --}}
    <button id="openCsvImport" class="btn btn-primary">
      <i class="fa fa-upload"></i> Upload CSV & Import
    </button>
    <button id="showTemplateHint" class="btn btn-light">
      <i class="fa fa-info-circle"></i> CSV Columns
    </button>

    {{-- Add Inventory --}}
    <button id="openCreateInventory" class="btn btn-success">
      <i class="fa fa-plus"></i> Add Inventory
    </button>
  </div>

  <div class="position-relative mb-3">
    <input id="inventorySearch" class="search-bar" placeholder="Search…" autocomplete="off">
    <div id="clearInventorySearch" class="clear-btn">&times;</div>
  </div>

  <div class="toolbar-row">
    <div class="toolbar">
      <button class="btn fil-type active" data-type="all">All Types</button>
      <button class="btn fil-type" data-type="inventories">Available</button>
      <button class="btn fil-type" data-type="hold_inventories">On Hold</button>
    </div>

    <div class="filters-right">
      <div class="form-inline">
        <label class="text-muted small mb-0">Sort</label>
        <select id="sortSelect" class="form-control form-control-sm">
          <option value="name_az">Name (A → Z)</option>
          <option value="name_za">Name (Z → A)</option>
          <option value="price_asc">Price (Low → High)</option>
          <option value="price_desc">Price (High → Low)</option>
          <option value="qty_asc">Qty (Low → High)</option>
          <option value="qty_desc">Qty (High → Low)</option>
          <option value="date_new">Date Added (New → Old)</option>
          <option value="date_old">Date Added (Old → New)</option>
        </select>

        <label class="text-muted small mb-0">Subtype</label>
        <select id="subtypeFilter" class="form-control form-control-sm">
          <option value="">— None —</option>
        </select>

        <button id="resetFilters" class="btn btn-link btn-sm btn-reset">Reset</button>
        <button id="printPdfBtn" class="btn btn-dark btn-sm"><i class="fa fa-print"></i> Print PDF</button>
      </div>
    </div>
  </div>

  <div class="btn-group mb-3" role="group" aria-label="View mode">
    <button id="viewCardsBtn" class="btn btn-outline-secondary view-toggle active" data-view="cards">
      <i class="fa fa-th-large"></i> Cards
    </button>
    <button id="viewListBtn" class="btn btn-outline-secondary view-toggle" data-view="list">
      <i class="fa fa-list"></i> List
    </button>
  </div>

  {{-- =============== Helpers / Grouping =============== --}}
  @php
    $catNameOf = function($inv){
      return optional($inv->categoryDetail)->name ?? 'Uncategorized';
    };
    $byCategory = $inventories
      ->groupBy(fn($inv) => $catNameOf($inv))
      ->sortKeys();
  @endphp

  {{-- =============== STAGE 1: Category Cards =============== --}}
  <div id="categoryStage" class="stage active">
    <ul class="category-grid">
      @foreach($byCategory as $categoryName => $itemsInCategory)
      @php
        $catKey = 'cat::'.Str::slug($categoryName);
        // exclude anything that has a non-empty status_subtype
        $count  = $itemsInCategory
                    ->filter(fn($inv) => !trim($inv->status_subtype ?? ''))
                    ->count();
      @endphp
        <li class="category-card" data-cat-key="{{ $catKey }}">
          <div class="title">{{ $categoryName }}</div>
          <div class="meta"><span class="cat-count" data-cat-key="{{ $catKey }}">{{ $count }}</span> items</div>
        </li>
      @endforeach
    </ul>
  </div>

  {{-- =============== STAGE 2: Category Detail =============== --}}
  <div id="categoryDetailStage" class="stage">
    <div class="detail-header">
      <button id="backToCategories" class="btn btn-light back-btn"><i class="fa fa-arrow-left"></i> Back</button>
      <div id="detailTitle" class="detail-title">Category</div>
    </div>

    @foreach($byCategory as $categoryName => $itemsInCategory)
      @php
        $catKey = 'cat::'.Str::slug($categoryName);
        $sorted = $itemsInCategory->sortBy(fn($inv) => Str::lower($inv->name))->values();
      @endphp

      <div class="category-container" data-cat-key="{{ $catKey }}" style="display:none;">
        <ul class="cards-list">
          @foreach($sorted as $inv)
            @php
              $labs      = $inv->metrc_full_labs ?? collect();
              $pkg       = $inv->metrc_package;
              $catName   = Str::lower(optional($inv->categoryDetail)->name ?? 'uncategorized');
              $isTotalMg = in_array($catName, ['edibles','drinks/tinctures']);
              $raw       = data_get($pkg,'payload');
              $payload   = is_array($raw) ? $raw : (json_decode($raw,true) ?: []);

              $uw = ($catName==='flower') ? ($inv->weight ?: 1) : ($payload['Item']['UnitWeight'] ?? $inv->weight ?: 1);
              if(in_array(strtolower($payload['Item']['UnitWeightUnitOfMeasureName'] ?? ''), ['ounces','ounce','oz'])) $uw *= 28.3495;
              $qtyLab    = $payload['Item']['Quantity'] ?? 1;
              $thcLab    = $labs->first(fn($l)=> Str::contains(Str::lower($l->TestTypeName),'total thc'));
              $cbdLab    = $labs->first(fn($l)=> Str::contains(Str::lower($l->TestTypeName),'total cbd'));
              $thcMg     = $thcLab->TestResultLevel ?? null;
              $cbdMg     = $cbdLab->TestResultLevel ?? null;
              $totalMg   = ($isTotalMg && is_numeric($thcMg)) ? round($thcMg * $uw * $qtyLab,2) : null;
              $thcPct    = is_numeric($thcMg) ? round($thcMg/10,2) : null;
              $cbdPct    = is_numeric($cbdMg) ? round($cbdMg/10,2) : null;

              $rawTiers  = auth()->user()->organization->discount_tiers;
              $tiers     = is_string($rawTiers) ? json_decode($rawTiers, true) : ($rawTiers ?: []);
              $tier      = collect($tiers)->first(fn($t) => data_get($t,'name') === $inv->selected_discount_tier);

              $currency = setting_by_key('currency_symbol') ?? '$';
              $price = $inv->original_price
                    ?? $inv->retail_price
                    ?? $inv->unit_price
                    ?? $inv->sale_price
                    ?? data_get($payload, 'Item.Price')
                    ?? data_get($payload, 'Price')
                    ?? null;

              $cost  = $inv->original_cost
                    ?? $inv->unit_cost
                    ?? data_get($payload, 'Item.Cost')
                    ?? data_get($payload, 'Cost')
                    ?? null;

              $price = is_numeric($price) ? (float)$price : null;
              $cost  = is_numeric($cost)  ? (float)$cost  : null;

              $dateAddedSrc =
                  data_get($payload,'CreatedDateTime')
                  ?? data_get($payload,'ReceivedDateTime')
                  ?? data_get($payload,'PackagedDate')
                  ?? data_get($payload,'ReceivedDate')
                  ?? data_get($payload,'Item.CreatedDateTime')
                  ?? data_get($pkg,'created_at');
              $dateAddedIso = (is_object($dateAddedSrc) && method_exists($dateAddedSrc,'toIso8601String'))
                  ? $dateAddedSrc->toIso8601String()
                  : (is_string($dateAddedSrc) ? $dateAddedSrc : '');

              $producer = data_get($payload, 'ItemFromFacilityName')
                       ?? data_get($payload, 'ProductionFacilityName')
                       ?? data_get($payload, 'FacilityName')
                       ?? data_get($payload, 'ProducerName')
                       ?? data_get($payload, 'Item.ProducerName')
                       ?? data_get($payload, 'Item.ManufacturerName')
                       ?? data_get($payload, 'Item.FacilityName')
                       ?? '';

              $fullLabel  = $inv->Label ?: (data_get($payload,'Label') ?: '');
              $labelShort = $fullLabel ? substr($fullLabel, -5) : null;

              $statusSubtype = $inv->status_subtype ?? '';

              $hayParts = [
                Str::lower($inv->name ?? ''),
                Str::lower($fullLabel ?? ''),
                Str::lower($inv->sku ?? ''),
                Str::lower(optional($inv->categoryDetail)->name ?? ''),
                Str::lower($producer ?? ''),
              ];
              if (is_array($payload)) {
                $hayParts[] = Str::lower(data_get($payload, 'Item.Name', ''));
                $hayParts[] = Str::lower(data_get($payload, 'Item.StrainName', ''));
                $hayParts[] = Str::lower(data_get($payload, 'ProductCategoryName', ''));
                $hayParts[] = Str::lower(data_get($payload, 'ItemFromFacilityName', ''));
              }
              $hay = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($hayParts))));
            @endphp

            <li class="inventory-card"
                data-category-id="{{ optional($inv->categoryDetail)->id ?? 0 }}"
                data-store-qty="{{ $inv->storeQty }}"
                data-inv-type="{{ $inv->inventory_type }}"
                data-hay="{{ e($hay) }}">
              <div class="custom-card product-card"
                   data-id="{{ $inv->id }}"
                   data-name="{{ $inv->name }}"
                   data-sku="{{ $inv->sku }}"
                   data-label="{{ e($fullLabel) }}"
                   data-category-name="{{ optional($inv->categoryDetail)->name }}"
                   data-price="{{ $price ?? '' }}"
                   data-cost="{{ $cost ?? '' }}"
                   data-producer="{{ e($producer) }}"
                   data-date-added="{{ e($dateAddedIso) }}"
                   data-subtype="{{ e($statusSubtype) }}"
                   @if($inv->Label && $pkg)
                     data-metrc='@json($pkg)' data-labs='@json($labs)'
                   @endif>
                <button class="delete-x delete-inventory" data-id="{{ $inv->id }}">×</button>

                <div class="custom-img-container">
                  <img src="{{ $inv->image_url }}" alt="{{ $inv->name }}">
                  <div class="actions-col">
                    @if($inv->Label && $pkg)
                      <button class="btn btn-info btn-sm view-metrc">View</button>
                      <button class="btn btn-secondary btn-sm print-label">Print Label</button>
                      <button class="btn btn-secondary btn-sm print-qr">Print Barcode</button>
                    @endif
                    <a href="{{ route('inventories.edit',$inv->id) }}" class="btn btn-primary btn-sm">Edit</a>

                    <label title="Toggle Hold">
                      Hold <input type="checkbox" class="hold-inventory-checkbox" data-id="{{ $inv->id }}"
                                 {{ $inv->inventory_type==='hold_inventories'?'checked':'' }}>
                    </label>

                    <button class="btn btn-outline-secondary btn-sm set-subtype" title="Set subtype">Set Subtype</button>
                  </div>
                </div>

                <div class="card-content">
                  <div class="custom-name">{!! parseEmojis($inv->name) !!}</div>
                  <div class="custom-details">
                    @if($tier)
                      <div class="custom-field">Tier: {{ $tier['name'] }}</div>
                    @endif

                    <div class="custom-field">
                      Qty: {{ floor($inv->storeQty)===$inv->storeQty? intval($inv->storeQty): number_format($inv->storeQty,2) }}
                    </div>
                    <div class="custom-field">SKU: <span class="sku">{{ $inv->sku }}</span></div>

                    @if(!is_null($price))
                      <div class="custom-field">Price: {{ $currency }}{{ number_format($price, 2) }}</div>
                    @endif
                    @if(!is_null($cost))
                      <div class="custom-field">Cost: {{ $currency }}{{ number_format($cost, 2) }}</div>
                    @endif

                    @if($fullLabel)
                      <div class="custom-field" title="PKG ID: {{ $fullLabel }}">PKG ID: {{ $labelShort ?: $fullLabel }}</div>
                    @endif
                    @if(!empty($producer))
                      <div class="custom-field" title="{{ $producer }}">Producer: {{ Str::limit($producer, 42) }}</div>
                    @endif

                    @if($statusSubtype !== '')
                      <div class="custom-field subtype-field">Subtype: <span class="subtype-badge">{{ $statusSubtype }}</span></div>
                    @endif

                    @if($totalMg !== null)
                      <div class="custom-field lab-label">Total potency: {{ $totalMg }} mg</div>
                    @else
                      @if($thcPct !== null)
                        <div class="custom-field lab-label">THC %: {{ $thcPct }}</div>
                      @endif
                      @if($cbdPct !== null)
                        <div class="custom-field lab-label">CBD %: {{ $cbdPct }}</div>
                      @endif
                    @endif
                  </div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>

  {{-- ============ STAGE 3: Subtype Results (no category grouping) ============ --}}
  <div id="subtypeStage" class="stage">
    <div class="detail-header">
      <button id="backFromSubtype" class="btn btn-light back-btn"><i class="fa fa-arrow-left"></i> Back</button>
      <div id="subtypeTitle" class="detail-title">Subtype</div>
    </div>
    <ul class="cards-list" id="subtypeCardsList"></ul>
  </div>
</div>

{{-- ============ METRC + Label/QR Modals ============ --}}
<div class="modal fade" id="metrcModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">METRC Package Details</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body p-0">
        <table class="table table-striped mb-0">
          <tbody id="metrcModalBody"></tbody>
          <tbody id="metrcLabBody" style="display:none;"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button id="showLabBtn" class="btn btn-info">View Full Tests</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="printLabelModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Configure &amp; Print Label</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <form id="printLabelForm">
          <div class="form-row">
            <div class="form-group col-md-2">
              <label for="labelWidth">Width (inches)</label>
              <input type="number" step="0.1" class="form-control" id="labelWidth" value="4">
            </div>
            <div class="form-group col-md-2">
              <label for="labelHeight">Height (inches)</label>
              <input type="number" step="0.1" class="form-control" id="labelHeight" value="2">
            </div>
            <div class="form-group col-md-2">
              <label for="labelWeight">Weight (g)</label>
              <input type="number" step="0.01" class="form-control" id="labelWeight" value="1">
            </div>
            <div class="form-group col-md-3">
              <label for="labelNotes">Notes</label>
              <input type="text" class="form-control" id="labelNotes" placeholder="Optional">
            </div>
            <div class="form-group col-md-3 d-flex align-items-center">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="includeQr" checked>
                <label class="form-check-label" for="includeQr">Include SKU QR</label>
              </div>
            </div>
          </div>
        </form>
        <div id="labelPreviewContainer" class="border mb-3 p-2">
          <div id="labelPreview"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button id="printLabelConfirm" class="btn btn-primary">Print Label</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="printQRModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Print SKU Code</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-2 text-left">
          <label class="d-block font-weight-bold mb-1">Code type</label>
          <div class="custom-control custom-radio custom-control-inline">
            <input class="custom-control-input" type="radio" name="codeType" id="codeTypeBarcode" value="barcode" checked>
            <label class="custom-control-label" for="codeTypeBarcode">Barcode (Code 128)</label>
          </div>
          <div class="custom-control custom-radio custom-control-inline">
            <input class="custom-control-input" type="radio" name="codeType" id="codeTypeQR" value="qr">
            <label class="custom-control-label" for="codeTypeQR">QR</label>
          </div>
        </div>

        <img id="qrPreview" src="" alt="Code" style="max-width:100%; height:auto;"/>

        <div class="text-left small mt-2">
          <div><strong>Name:</strong> <span id="codeName"></span></div>
          <div><strong>PKG ID:</strong> <span id="codePkgId"></span></div>
          <div><strong>SKU:</strong> <span id="codeSku"></span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button id="printQRConfirm" class="btn btn-primary">Print</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

{{-- ============ Print Columns Config Modal ============ --}}
<div class="modal fade" id="printConfigModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choose PDF Columns &amp; Order</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <div class="mb-2 d-flex gap-2">
          <button id="colsSelectAll" class="btn btn-sm btn-light mr-1">Select All</button>
          <button id="colsSelectNone" class="btn btn-sm btn-light mr-1">Select None</button>
          <button id="colsResetDefault" class="btn btn-sm btn-outline-secondary">Reset to Default</button>
        </div>
        <ul id="columnList"></ul>
        <small class="text-muted d-block">Tip: Use the ↑/↓ buttons to reorder.</small>
      </div>
      <div class="modal-footer">
        <button id="applyPrintConfig" class="btn btn-primary">Apply &amp; Print</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

{{-- ============ NEW: Set Subtype Modal ============ --}}
<div class="modal fade" id="setSubtypeModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Set Subtype</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold">Choose an existing subtype</label>
          <select id="subtypeExistingSelect" class="form-control">
            <option value="">— None —</option>
          </select>
          <small class="text-muted d-block mt-1">Tip: If you don’t see what you need, create a new one below.</small>
        </div>

        <div class="form-group mb-0">
          <label class="font-weight-bold">…or create a new subtype</label>
          <input id="subtypeNewInput" type="text" class="form-control" placeholder="e.g. 'Premium', 'Clearance', 'Half-Ounce'">
          <small class="text-muted d-block mt-1">If you type a new subtype, it will be used instead of the selection above.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button id="subtypeClearBtn" type="button" class="btn btn-link text-danger mr-auto">Clear subtype</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button id="subtypeSaveBtn" type="button" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

{{-- ============ NEW: Create Inventory Modal ============ --}}
@php
  $catOptions = isset($categories) && $categories
      ? $categories
      : $inventories->pluck('categoryDetail')->filter()->unique('id')->values();

  $org = auth()->user()->organization;
  $rawTiersCreate = $org->discount_tiers ?? null;
  $tiersCreate = [];
  if (!empty($rawTiersCreate)) {
      $tiersCreate = is_string($rawTiersCreate)
                    ? json_decode($rawTiersCreate, true) ?? []
                    : (is_array($rawTiersCreate) ? $rawTiersCreate : []);
  }
@endphp

<div class="modal fade" id="createInventoryModal" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Inventory</h5>
        <button type="button" class="close" data-dismiss="modal">×</button>
      </div>

      <div class="modal-body">
        <div class="alert alert-danger d-none" id="createErrors"><ul class="mb-0" id="createErrorsList"></ul></div>

        <form id="createInventoryForm" action="{{ route('inventories.store') }}" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
          @csrf

          {{-- Image --}}
          <div class="text-center mb-4">
            <label for="create_file" class="mb-2 d-block">
              <img src="{{ asset('herbs/noimage.jpg') }}" class="rounded-circle" style="width:160px;height:160px;" alt="Preview">
            </label>
            <input type="file" id="create_file" name="file" class="form-control-file mx-auto" style="width:auto;">
          </div>

          {{-- Name --}}
          <div class="form-group">
            <label for="create_name" class="font-weight-bold">Inventory Name</label>
            <input type="text" id="create_name" name="name" class="form-control form-control-lg" required>
          </div>

          {{-- Add Leaf --}}
          <input type="hidden" name="add_leaf" value="0">
          <div class="form-group form-check">
            <input type="checkbox" id="create_addLeaf" name="add_leaf" class="form-check-input" value="1">
            <label for="create_addLeaf" class="form-check-label">Append Green Leaf Emoji</label>
          </div>

          {{-- Apply name to group --}}
          <input type="hidden" name="apply_name_to_group" value="0">
          <div class="form-group form-check">
            <input type="checkbox" id="create_apply_name_to_group" name="apply_name_to_group" class="form-check-input" value="1">
            <label for="create_apply_name_to_group" class="form-check-label">
              Apply this name to all items with the same base name (this organization)
            </label>
            <small class="form-text text-muted">
              “Base name” ignores a trailing <code>:gls:</code> or a trailing image tag.
            </small>
          </div>

          {{-- Category --}}
          <div class="form-group">
            <label for="create_category_id" class="font-weight-bold">Category</label>
            <select id="create_category_id" name="category_id" class="form-control form-control-lg" required>
              <option value="">-- Select Category --</option>
              @foreach($catOptions as $cat)
                @if($cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endif
              @endforeach
            </select>
          </div>

          {{-- Discount Tier (flower) --}}
          <div class="form-group" id="create_tier_section" style="display:none;">
            <label for="create_selected_discount_tier" class="font-weight-bold">Discount Tier</label>
            <select id="create_selected_discount_tier" name="selected_discount_tier" class="form-control form-control-lg">
              <option value="">-- None --</option>
              @foreach($tiersCreate as $tier)
                <option value="{{ $tier['name'] }}">{{ $tier['name'] }} ({{ data_get($tier, 'pricing.0.price', '–') }})</option>
              @endforeach
            </select>
          </div>

          {{-- Price (non-flower) --}}
          <div class="form-group" id="create_price_section" style="display:block;">
            <label for="create_original_price" class="font-weight-bold">Price</label>
            <input type="number" step="0.01" id="create_original_price" name="original_price" class="form-control form-control-lg">
          </div>

          {{-- Cost --}}
          <div class="form-group">
            <label for="create_original_cost" class="font-weight-bold">Cost</label>
            <input type="number" step="0.01" id="create_original_cost" name="original_cost" class="form-control form-control-lg">
          </div>

          {{-- Package ID --}}
          <div class="form-group">
            <label for="create_Label" class="font-weight-bold">Package ID</label>
            <input type="text" id="create_Label" name="Label" class="form-control form-control-lg">
          </div>

          {{-- Quantity --}}
          <div class="form-group">
            <label for="create_storeQty" class="font-weight-bold">Quantity (on hand)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              id="create_storeQty"
              name="storeQty"
              class="form-control form-control-lg"
              value="1"
              placeholder="e.g. 12 or 12.5"
            >
            <small class="form-text text-muted">
              Decimals are allowed. This will save to <code>inventories.storeQty</code>.
            </small>
          </div>

          {{-- SKU --}}
          <div class="form-group">
            <label for="create_sku" class="font-weight-bold">SKU / Barcode</label>
            <div class="input-group">
              <input type="text" id="create_sku" name="sku" class="form-control form-control-lg" oninput="resizeSkuInput(this)">
              <div class="input-group-append">
                <button type="button" id="create_generateSku" class="btn btn-outline-secondary">Generate</button>
              </div>
            </div>
          </div>

          {{-- Base Type (Available / On Hold) --}}
          <div class="form-group">
            <label for="create_inventory_type" class="font-weight-bold">Inventory Type</label>
            <select id="create_inventory_type" name="inventory_type" class="form-control form-control-lg">
              <option value="inventories" selected>Available</option>
              <option value="hold_inventories">On Hold</option>
            </select>
          </div>

          <div class="form-group text-right mb-0">
            <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

{{-- ============ NEW: CSV Import Modal (runs ImportInventoriesSeeder) ============ --}}
<div class="modal fade" id="csvImportModal" tabindex="-1" role="dialog" >
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Inventories (CSV)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">×</button>
      </div>
      <div class="modal-body">
        <form id="csvImportForm" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label class="font-weight-bold">CSV File</label>
            <input type="file" name="file" id="csvFile" accept=".csv,text/csv" class="form-control-file" required>
            <small class="form-text text-muted">
              The file will be saved and then <code>ImportInventoriesSeeder</code> will run in queued chunks to upsert inventories.
            </small>
          </div>
          <div class="text-right">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" id="runImportBtn" class="btn btn-primary">
              <i class="fa fa-play"></i> Run Import
            </button>
          </div>
        </form>

        <hr>
        <h6 class="mb-2">Seeder Output</h6>
        <pre class="import-output" id="csvImportOutput">(no output yet)</pre>
      </div>
    </div>
  </div>
</div>
@endsection


@section('scripts')

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="{{ asset('assets/js/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/sweetalert/sweetalert.min.js') }}"></script>

<script>
$(function () {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

  /* ============ Modal a11y: prevent aria-hidden on focused modals; inert background ============ */
  function setBackgroundInert(modalEl, inert) {
    Array.from(document.body.children).forEach(el => {
      if (el === modalEl || el.classList.contains('modal-backdrop')) return;
      if (inert) el.setAttribute('inert', '');
      else el.removeAttribute('inert');
    });
  }
  $('.modal')
    .on('show.bs.modal', function () {
      this.removeAttribute('aria-hidden');
      this.setAttribute('aria-modal', 'true');
      setBackgroundInert(this, true);
    })
    .on('hidden.bs.modal', function () {
      setBackgroundInert(this, false);
      this.setAttribute('aria-hidden', 'true');
    });

  /* =========================
     Constants (endpoints)
  ========================== */
  const ORG_SUBTYPES_URL   = '{{ route('inventories.listSubtypes') }}';
  const UPDATE_SUBTYPE_URL = (id) => '/inventories/' + encodeURIComponent(id) + '/subtype';
  const UPDATE_TYPE_URL    = (id) => '/inventories/' + encodeURIComponent(id) + '/update-type';

  // NEW: CSV import endpoint
  const ROUTE_IMPORT = @json(route('inventories.import.csv'));

  /* =========================
     Helpers & State
  ========================== */
  const $stageCat = $('#categoryStage');
  const $stageDet = $('#categoryDetailStage');
  const $stageSub = $('#subtypeStage');
  const $subList  = $('#subtypeCardsList');

  const norm = s => (s == null ? '' : String(s)).toLowerCase().trim();
  const raf = window.requestAnimationFrame || (fn => setTimeout(fn, 0));
  const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn.apply(null, a), ms); }; };
  const escapeRE = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

  const VIEW_LS_KEY       = 'pos_inv_view_v1';
  const ITEM_SUBTYPE_LS   = 'pos_item_subtype_map_v2'; // optimistic cache only (non-authoritative)
  const SUBTYPE_BASE_LS   = 'pos_subtype_base_map_v2'; // legacy local cache for group mapping
  const ACTIVE_SUBTYPE_LS = 'pos_inv_subtype_v2';

  const loadObj = (k, fb = {}) => { try { const v = JSON.parse(localStorage.getItem(k) || 'null'); return v && typeof v === 'object' ? v : fb; } catch { return fb; } };
  const saveObj = (k, v) => localStorage.setItem(k, JSON.stringify(v || {}));

  let ITEM_SUBTYPE   = loadObj(ITEM_SUBTYPE_LS, {}); // { [inventoryId]: 'subtype' } (lowercased)
  let SUBTYPE_BASE   = loadObj(SUBTYPE_BASE_LS, {}); // { [subtype]: 'inventories'|'hold_inventories' }
  let activeSubtype  = localStorage.getItem(ACTIVE_SUBTYPE_LS) || '';
  let activeType     = 'all'; // 'all' | 'inventories' | 'hold_inventories'
  let currentCatKey  = null;

  // Org-wide authoritative subtype list (strings exactly as stored in DB)
  let ORG_SUBTYPES = [];

  /* =========================
     Index items once
  ========================== */
  const LIs = Array.from(document.querySelectorAll('li.inventory-card'));
  const Cards = LIs.map(li => {
    const pc = li.querySelector('.product-card');
    const container = li.closest('.category-container');
    const catKey = container ? container.getAttribute('data-cat-key') : '';
    const safeNum = v => { const x = parseFloat(v); return isNaN(x) ? null : x; };

    // Use precomputed data-hay if present; otherwise build a compact haystack
    const hay = (
      li.dataset.hay ||
      [
        pc?.dataset.name || '',
        pc?.dataset.label || '',
        pc?.dataset.sku || '',
        pc?.dataset.categoryName || '',
        pc?.dataset.producer || ''
      ].join(' ')
    ).toLowerCase();

    return {
      li,
      pc,
      catKey,
      homeList: li.closest('.cards-list'),
      id: String(pc?.dataset.id || ''),
      name: pc?.dataset.name || '',
      hay,
      group: li.dataset.invType || 'inventories',
      subtype: (pc?.dataset.subtype || '').toLowerCase(), // from DB on initial render
      qty: safeNum(li.dataset.storeQty) ?? 0,
      dateAdded: pc?.dataset.dateAdded || '',
      price: safeNum(pc?.dataset.price),
      cost:  safeNum(pc?.dataset.cost),
    };
  });

  const cardByLi = new Map(Cards.map(o => [o.li, o]));

  /* =========================
     Org-wide subtype API
  ========================== */
  function fetchOrgSubtypes () {
    return $.getJSON(ORG_SUBTYPES_URL)
      .done(res => {
        if (res && Array.isArray(res.subtypes)) {
          ORG_SUBTYPES = res.subtypes.map(s => String(s));
        }
      })
      .fail(() => { /* silent */ });
  }
  function persistSubtypeToServer (id, rawSubtype) {
    return $.ajax({
      url: UPDATE_SUBTYPE_URL(id),
      type: 'POST',
      data: { subtype: rawSubtype }
    });
  }

  /* =========================
     Subtype badge sync (initial)
  ========================== */
  (function ensureInitialSubtypeBindings () {
    for (const obj of Cards) {
      const id = obj.id;
      const st = (obj.subtype && String(obj.subtype)) || (ITEM_SUBTYPE[id] ? String(ITEM_SUBTYPE[id]).toLowerCase() : '');
      if (!st) continue;

      obj.subtype = st;
      if (obj.pc) obj.pc.dataset.subtype = st;

      const details = obj.pc?.querySelector('.custom-details');
      if (details) {
        let fieldWrap = details.querySelector('.subtype-field');
        if (!fieldWrap) {
          fieldWrap = document.createElement('div');
          fieldWrap.className = 'custom-field subtype-field';
          fieldWrap.innerHTML = 'Subtype: <span class="subtype-badge"></span>';
          details.appendChild(fieldWrap);
        }
        const badge = fieldWrap.querySelector('.subtype-badge');
        if (badge) badge.textContent = st;
      }
      if (!SUBTYPE_BASE[st]) SUBTYPE_BASE[st] = obj.group || 'inventories';
    }
    saveObj(ITEM_SUBTYPE_LS, ITEM_SUBTYPE);
    saveObj(SUBTYPE_BASE_LS, SUBTYPE_BASE);
  })();

  /* =========================
     Stage switching
  ========================== */
  function showStage ($toShow, titleText) {
    $('.stage').removeClass('active stage-animate-in');
    $toShow.addClass('active stage-animate-in');
    if (titleText) $('#detailTitle').text(titleText);
  }
  function showCategory (catKey) {
    currentCatKey = catKey;
    const name = $('.category-card[data-cat-key="' + catKey + '"] .title').text().trim() || 'Category';
    $('#detailTitle').text(name);
    $('.category-container').hide();
    $('.category-container[data-cat-key="' + catKey + '"]').show();
    applyViewMode(localStorage.getItem(VIEW_LS_KEY) || 'cards');
    showStage($stageDet);
    populateSubtypeFilter();
    applyFiltersNow();
    applySort();
  }
  function enterSearchMode () {
    currentCatKey = null;
    $('#detailTitle').text('Search Results');
    $('.category-container').show();
    applyViewMode(localStorage.getItem(VIEW_LS_KEY) || 'cards');
    showStage($stageDet);
  }
  function exitSearchMode () {
    $('.category-container').hide();
    showStage($stageCat);
  }

  function showSubtypeStage () {
    $('#subtypeTitle').text('Subtype: ' + (activeSubtype || ''));
    applyViewMode(localStorage.getItem(VIEW_LS_KEY) || 'cards');
    showStage($stageSub);
    rebuildSubtypeStage();
  }

  function restoreAllToHome () {
    const listNode = $subList[0];
    if (!listNode) return;
    const nodes = Array.from(listNode.children);
    for (const li of nodes) {
      const obj = cardByLi.get(li);
      if (obj && obj.homeList) obj.homeList.appendChild(li);
    }
  }

  $('body').on('click', '.category-card', function () { showCategory($(this).data('cat-key')); });
  $('#backToCategories').on('click', function () {
    $('#inventorySearch').val('');
    currentCatKey = null;
    exitSearchMode();
    populateSubtypeFilter();
    applyFiltersNow();
  });
  $('#backFromSubtype').on('click', function () {
    $('#subtypeFilter').val('');
    activeSubtype = '';
    localStorage.removeItem(ACTIVE_SUBTYPE_LS);
    restoreAllToHome();
    exitSearchMode();
    populateSubtypeFilter();
    updateSubtypeVisibilityFlag();
    applyFiltersNow();
    applySort();
  });

  /* =========================
     Subtype picker (modal)
  ========================== */
  function allKnownSubtypes () {
    const set = new Set();
    (ORG_SUBTYPES || []).forEach(s => s && set.add(String(s)));
    Cards.forEach(c => { if (c.subtype) set.add(String(c.subtype)); });
    Object.values(ITEM_SUBTYPE || {}).forEach(s => s && set.add(String(s)));
    return Array.from(set).sort((a,b) => a.toLowerCase().localeCompare(b.toLowerCase()));
  }

  function renderSubtypeOnObj (obj, stRaw) {
    const $pc      = $(obj.li).find('.product-card');
    const $details = $pc.find('.custom-details');

    const stText = (stRaw || '').trim();
    const stKey  = stText.toLowerCase();

    obj.subtype = stKey || '';
    $pc.attr('data-subtype', stKey || '');

    let $wrap = $details.find('.subtype-field');
    if (stKey) {
      if (!$wrap.length) {
        $wrap = $('<div class="custom-field subtype-field">Subtype: <span class="subtype-badge"></span></div>');
        $details.append($wrap);
      }
      $wrap.find('.subtype-badge').text(stText);
      ITEM_SUBTYPE[obj.id] = stKey;
    } else {
      if ($wrap.length) $wrap.remove();
      delete ITEM_SUBTYPE[obj.id];
    }
    saveObj(ITEM_SUBTYPE_LS, ITEM_SUBTYPE);

    if (stKey && !SUBTYPE_BASE[stKey]) {
      SUBTYPE_BASE[stKey] = obj.group || 'inventories';
      saveObj(SUBTYPE_BASE_LS, SUBTYPE_BASE);
    }

    updateSubtypeVisibilityFlag();
  }

  let _subtypeCtx = null;

  $('body').on('click', '.set-subtype', function () {
    const $li = $(this).closest('li.inventory-card');
    const obj = cardByLi.get($li[0]);
    if (!obj) return;

    _subtypeCtx = obj;

    const $sel = $('#subtypeExistingSelect').empty().append('<option value="">— None —</option>');
    allKnownSubtypes().forEach(s => {
      const val = String(s).toLowerCase();
      const label = s.replace(/\b\w/g, c => c.toUpperCase());
      $sel.append('<option value="' + val.replace(/"/g, '&quot;') + '">' + label + '</option>');
    });
    $sel.val(obj.subtype || '');

    $('#subtypeNewInput').val('');
    $('#setSubtypeModal .modal-title').text('Set Subtype — ' + (obj.name || 'Item'));
    $('#setSubtypeModal').modal('show');
  });

  $('#subtypeSaveBtn').on('click', function () {
    if (!_subtypeCtx) return;

    const typed  = ($('#subtypeNewInput').val() || '').trim();
    const chosen = ($('#subtypeExistingSelect').val() || '').trim();
    const finalRaw = typed || chosen;
    const id = _subtypeCtx.id;

    const $btn = $(this).prop('disabled', true).text('Saving…');

    persistSubtypeToServer(id, finalRaw)
      .done(function (res) {
        const saved = res && typeof res.subtype === 'string' ? res.subtype : '';
        renderSubtypeOnObj(_subtypeCtx, saved);
        toastr.success(saved ? `Subtype set to "${saved}"` : 'Subtype cleared');

        fetchOrgSubtypes().always(function () {
          populateSubtypeFilter();
          applyFiltersNow();
          applySort();
        });
        $('#setSubtypeModal').modal('hide');
      })
      .fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to save subtype.';
        toastr.error(msg);
      })
      .always(function () { $btn.prop('disabled', false).text('Save'); });
  });

  $('#subtypeClearBtn').on('click', function () {
    if (!_subtypeCtx) return;
    const id = _subtypeCtx.id;
    persistSubtypeToServer(id, '')
      .done(function () {
        renderSubtypeOnObj(_subtypeCtx, '');
        toastr.success('Subtype cleared');
        fetchOrgSubtypes().always(function () {
          populateSubtypeFilter();
          applyFiltersNow();
          applySort();
        });
        $('#setSubtypeModal').modal('hide');
      })
      .fail(function () { toastr.error('Failed to clear subtype.'); });
  });

  /* =========================
     Available / Hold buttons
  ========================== */
  function setActiveType (type) {
    activeType = type;
    $('.fil-type').removeClass('active');
    $('.fil-type[data-type="' + type + '"]').addClass('active');
    if (activeSubtype) {
      rebuildSubtypeStage();
      applySort();
    } else {
      applyFiltersNow();
      populateSubtypeFilter();
      applySort();
    }
  }
  $('.fil-type').on('click', function () { setActiveType($(this).data('type')); });

  /* =========================
     Visible containers
  ========================== */
  function getVisibleContainers () {
    return Array.from(document.querySelectorAll('.category-container')).filter(el =>
      el.offsetParent !== null && getComputedStyle(el).display !== 'none'
    );
  }

  /* =========================
     Subtype dropdown
  ========================== */
  function collectVisibleSubtypes () {
    const set = new Set();
    (ORG_SUBTYPES || []).forEach(s => s && set.add(String(s).toLowerCase()));
    Cards.forEach(c => { if (c.subtype) set.add(String(c.subtype).toLowerCase()); });
    Object.values(ITEM_SUBTYPE).forEach(s => { if (s) set.add(String(s).toLowerCase()); });
    return Array.from(set).sort((a, b) => a.localeCompare(b));
  }

  function populateSubtypeFilter () {
    const $sel = $('#subtypeFilter'); if (!$sel.length) return;
    const was = ($sel.val() || '').toLowerCase();
    $sel.empty().append('<option value="">— None —</option>');
    collectVisibleSubtypes().forEach(s => {
      const label = s.replace(/\b\w/g, c => c.toUpperCase());
      $sel.append('<option value="' + s.replace(/"/g, '&quot;') + '">' + label + '</option>');
    });
    if (was && $sel.find('option[value="' + was.replace(/"/g, '&quot;') + '"]').length) {
      $sel.val(was); activeSubtype = was;
    } else {
      if (!activeSubtype || !$sel.find('option[value="' + activeSubtype.replace(/"/g, '&quot;') + '"]').length) {
        $sel.val(''); activeSubtype = '';
      } else {
        $sel.val(activeSubtype);
      }
    }
    updateSubtypeVisibilityFlag();
  }

  function updateSubtypeVisibilityFlag() {
    document.body.classList.toggle('subtype-active', !!activeSubtype);
  }

  $('#subtypeFilter').on('change', function () {
    activeSubtype = (($(this).val() || '') + '').toLowerCase();
    if (activeSubtype) {
      localStorage.setItem(ACTIVE_SUBTYPE_LS, activeSubtype);
      updateSubtypeVisibilityFlag();
      showSubtypeStage();
      applySort();
    } else {
      localStorage.removeItem(ACTIVE_SUBTYPE_LS);
      updateSubtypeVisibilityFlag();
      restoreAllToHome();
      if (currentCatKey) showCategory(currentCatKey); else exitSearchMode();
      applyFiltersNow();
      applySort();
    }
  });

  /* =========================
     SEARCH / FILTERS
  ========================== */
  const $search = $('#inventorySearch');
  const $clear  = $('#clearInventorySearch');

  function buildTokenRegexes (q) {
    const raw = (q || '').trim().toLowerCase();
    if (!raw) return [];
    const parts = raw.split(/\s+/).filter(Boolean);
    const tokens = parts.filter(t => t.length >= 2 || /^\d$/.test(t));
    return tokens.map(t => new RegExp('\\b' + escapeRE(t), 'i'));
  }

  function rebuildSubtypeStage () {
    const regs = buildTokenRegexes($search.val());
    const type = activeType;

    restoreAllToHome();
    $subList.empty();

    const passItems = [];
    for (const obj of Cards) {
      const inType = (type === 'all' || obj.group === type);
      const subOK  = (obj.subtype === (activeSubtype || '').toLowerCase());
      let textOK = true;
      if (regs.length) {
        for (let i = 0; i < regs.length; i++) {
          if (!regs[i].test(obj.hay)) { textOK = false; break; }
        }
      }
      if (inType && subOK && textOK) passItems.push(obj.li);
    }

    if (!$stageSub.hasClass('active')) showStage($stageSub);
    passItems.forEach(li => { li.style.display = ''; $subList[0].appendChild(li); });

    applyViewMode(localStorage.getItem(VIEW_LS_KEY) || 'cards');
  }

  function applyFiltersNow () {
    if (activeSubtype) {
      rebuildSubtypeStage();
      return;
    }

    const q = $search.val();
    const regs = buildTokenRegexes(q);
    const modeType = activeType;

    if (q && !$stageDet.hasClass('active')) enterSearchMode();
    if (!q && !$stageCat.hasClass('active') && !currentCatKey) exitSearchMode();

    raf(() => {
      const restrictToCatKey = $stageDet.hasClass('active') && currentCatKey ? currentCatKey : null;
      const countByCat = {};

      for (const obj of Cards) {
        if ($stageDet.hasClass('active') && restrictToCatKey && obj.catKey !== restrictToCatKey) {
          obj.li.style.display = 'none';
          continue;
        }

        const groupOK = (modeType === 'all' || obj.group === modeType);
        let textOK = true;
        if (regs.length) {
          for (let i = 0; i < regs.length; i++) {
            if (!regs[i].test(obj.hay)) { textOK = false; break; }
          }
        } else if ((q || '').trim().length > 0) {
          textOK = true;
        }

        const pass = groupOK && textOK;

        if ($stageDet.hasClass('active')) {
          obj.li.style.display = pass ? '' : 'none';
        }

        if (!countByCat[obj.catKey]) countByCat[obj.catKey] = 0;
        if (pass && !obj.subtype) countByCat[obj.catKey]++;
      }

      if ($stageCat.hasClass('active')) {
        $('.category-card').each(function () {
          const key = $(this).data('cat-key');
          const matches = countByCat[key] || 0;
          $(this).find('.cat-count').text(matches);
          const title = ($(this).find('.title').text() || '').toLowerCase();
          const regsTitle = buildTokenRegexes(q);
          const titleMatch = !regsTitle.length || regsTitle.every(r => r.test(title));
          $(this).toggle(titleMatch || matches > 0);
        });
      }
    });
  }

  const applyFiltersDebounced = debounce(applyFiltersNow, 120);
  $search.on('input', function () {
    if (activeSubtype) rebuildSubtypeStage();
    else applyFiltersDebounced();
  });
  $clear.on('click', function () { $search.val(''); if (activeSubtype) rebuildSubtypeStage(); else applyFiltersNow(); });

  $('#resetFilters').on('click', function (e) {
    e.preventDefault();
    $search.val('');
    setActiveType('all');
    $('#sortSelect').val('name_az');
    activeSubtype = '';
    $('#subtypeFilter').val('');
    localStorage.removeItem(ACTIVE_SUBTYPE_LS);
    restoreAllToHome();
    updateSubtypeVisibilityFlag();
    if (!currentCatKey) exitSearchMode();
    applyFiltersNow();
    applySort();
  });

  /* =========================
     Sorting
  ========================== */
  function parseMaybeDate (v) { if (!v) return null; const d = new Date(v); return isNaN(d.getTime()) ? null : d; }
  function cmp (a, b) { return a < b ? -1 : a > b ? 1 : 0; }
  function safeStr (v) { return String(v == null ? '' : v).toLowerCase(); }

  function sortListEls(listEl) {
    const items = Array.from(listEl.children).filter(li => li.style.display !== 'none');
    const val = $('#sortSelect').val();
    items.sort(function (liA, liB) {
      const a = cardByLi.get(liA);
      const b = cardByLi.get(liB);
      const nameA = safeStr(a?.name), nameB = safeStr(b?.name);
      const priceA = a?.price; const priceB = b?.price;
      const qtyA = a?.qty; const qtyB = b?.qty;
      const dateA = a?.dateAdded ? parseMaybeDate(a.dateAdded) : null;
      const dateB = b?.dateAdded ? parseMaybeDate(b.dateAdded) : null;

      switch (val) {
        case 'name_za':    return -cmp(nameA, nameB) || cmp(priceA ?? 0, priceB ?? 0);
        case 'price_asc':  return cmp(priceA ?? Infinity, priceB ?? Infinity) || cmp(nameA, nameB);
        case 'price_desc': return -cmp(priceA ?? -Infinity, priceB ?? -Infinity) || cmp(nameA, nameB);
        case 'qty_asc':    return cmp(qtyA ?? Infinity, qtyB ?? Infinity) || cmp(nameA, nameB);
        case 'qty_desc':   return -cmp(qtyA ?? -Infinity, qtyB ?? -Infinity) || cmp(nameA, nameB);
        case 'date_new':   return -cmp(dateA ? dateA.getTime() : -Infinity, dateB ? dateB.getTime() : -Infinity) || cmp(nameA, nameB);
        case 'date_old':   return cmp(dateA ? dateA.getTime() : Infinity, dateB ? dateB.getTime() : Infinity) || cmp(nameA, nameB);
        case 'name_az':
        default:           return cmp(nameA, nameB) || cmp(priceA ?? 0, priceB ?? 0);
      }
    });
    for (const li of items) listEl.appendChild(li);
  }

  function applySort () {
    if ($stageSub.hasClass('active')) {
      const list = document.getElementById('subtypeCardsList');
      if (list) sortListEls(list);
      return;
    }
    if (!$stageDet.hasClass('active')) return;
    getVisibleContainers().forEach(container => {
      const list = container.querySelector('.cards-list');
      if (list) sortListEls(list);
    });
  }
  $('#sortSelect').on('change', applySort);

  /* =========================
     View mode
  ========================== */
  function relocateActions (scope, mode) {
    scope.querySelectorAll('.custom-card').forEach(card => {
      const imgCol = card.querySelector('.custom-img-container');
      if (!imgCol) return;
      if (mode === 'list') {
        const under = imgCol.querySelector('.actions-col');
        if (under) card.appendChild(under);
      } else {
        const top = card.querySelector(':scope > .actions-col');
        if (top) imgCol.appendChild(top);
      }
    });
  }
  function applyViewMode (mode) {
    const isList = (mode === 'list');
    let scopes = [];

    if ($stageSub.hasClass('active')) {
      scopes = [document.getElementById('subtypeStage')];
    } else if ($stageDet.hasClass('active') && currentCatKey) {
      const el = document.querySelector('.category-container[data-cat-key="' + currentCatKey + '"]');
      if (el) scopes = [el];
    } else if ($stageDet.hasClass('active')) {
      scopes = getVisibleContainers();
    } else {
      scopes = [];
    }

    scopes.forEach(scope => {
      if (!scope) return;
      relocateActions(scope, mode);
      scope.classList.toggle('list-view', isList);
    });

    $('.view-toggle').removeClass('active');
    (isList ? $('#viewListBtn') : $('#viewCardsBtn')).addClass('active');
  }
  (function initViewMode () { applyViewMode(localStorage.getItem(VIEW_LS_KEY) || 'cards'); })();
  $('body').on('click', '.view-toggle', function () {
    const mode = $(this).data('view');
    localStorage.setItem(VIEW_LS_KEY, mode);
    applyViewMode(mode);
  });

  /* =========================
     Delete + Hold toggle
  ========================== */
  $('body').on('click', '.delete-inventory', function () {
    const id = String($(this).data('id') || '');
    if (!confirm('Delete this item?')) return;

    $.ajax({ url: '/inventories/' + id, type: 'DELETE' })
      .done(() => {
        const li = $(this).closest('li.inventory-card')[0];
        if (li) li.remove();
        toastr.success('Deleted #' + id);
        applyFiltersNow();
      })
      .fail(() => {
        toastr.error('Delete failed for #' + id);
      });
  });

  function updateInventoryType(id, newType) {
    return $.ajax({
      url: UPDATE_TYPE_URL(id),
      type: 'POST',
      data: { inventory_type: newType },
    });
  }

  $('body').on('change', '.hold-inventory-checkbox', function () {
    const $cb    = $(this);
    const id     = String($cb.data('id') || '');
    const toHold = $cb.is(':checked');
    const newType = toHold ? 'hold_inventories' : 'inventories';

    const $li  = $cb.closest('li.inventory-card');
    const obj  = (typeof cardByLi !== 'undefined') ? cardByLi.get($li[0]) : null;

    $cb.prop('disabled', true);
    if (obj) obj.group = newType;
    $li.attr('data-inv-type', newType);

    updateInventoryType(id, newType)
      .done(function () {
        if (window.toastr) toastr.success(toHold ? 'Moved to On Hold' : 'Moved to Available');
        if (typeof applyFiltersNow === 'function') applyFiltersNow();
        if (typeof applySort === 'function')       applySort();
      })
      .fail(function (xhr) {
        if (window.toastr) {
          const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update hold state.';
          toastr.error(msg);
        }
        $cb.prop('checked', !toHold);
        const revertType = toHold ? 'inventories' : 'hold_inventories';
        if (obj) obj.group = revertType;
        $li.attr('data-inv-type', revertType);
        if (typeof applyFiltersNow === 'function') applyFiltersNow();
        if (typeof applySort === 'function')       applySort();
      })
      .always(function () {
        $cb.prop('disabled', false);
      });
  });

  /* =========================
     Sync METRC Now
  ========================== */
  $('#syncMetrc').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Starting…');
    $.ajax({ url: '{{ url("/metrc/sync-now") }}', type: 'POST' })
      .done(r => { toastr.success(r && r.message ? r.message : 'Sync started'); })
      .fail(() => { toastr.error('Failed to start METRC sync'); })
      .always(() => { $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Sync METRC Now'); });
  });

  /* =========================
     METRC modal
  ========================== */
  $('body').on('click', '.view-metrc', function () {
    let $pc = $(this).closest('.product-card'), pkg = $pc.data('metrc') || {}, labs = $pc.data('labs') || [];
    const rawAttr = $pc.attr('data-metrc');
    if (rawAttr && rawAttr.trim().charAt(0) === '{') { try { pkg = JSON.parse(rawAttr); } catch (e) {} }
    if (pkg && typeof pkg.payload === 'string') { try { pkg.payload = JSON.parse(pkg.payload); } catch (e) {} }

    const esc = s => String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    $('#metrcModalBody').empty();
    $.each(pkg, function (k, v) {
      let valStr = ''; try { valStr = JSON.stringify(v, null, 2); } catch (e) { valStr = String(v); }
      $('#metrcModalBody').append('<tr><th>' + esc(k) + '</th><td><pre>' + esc(valStr) + '</pre></td></tr>');
    });

    $('#metrcLabBody').empty();
    if (labs && labs.length) {
      let hdr = '<tr class="table-primary">';
      $.each(Object.keys(labs[0]), function (i, h) { hdr += '<th>' + esc(h) + '</th>'; });
      hdr += '</tr>';
      $('#metrcLabBody').append(hdr);
      labs.forEach(function (row) {
        let r = '<tr>'; $.each(row, function (_, c) { r += '<td>' + esc(String(c)) + '</td>'; }); r += '</tr>';
        $('#metrcLabBody').append(r);
      });
    } else {
      $('#metrcLabBody').append('<tr><td colspan="100%" class="text-center">No full lab data</td></tr>');
    }

    $('#metrcLabBody').hide();
    $('#showLabBtn').text('View Full Tests');
    $('#metrcModal').modal('show');
  });
  $('#showLabBtn').on('click', function () {
    const show = $(this).text() === 'View Full Tests';
    $('#metrcModalBody,#metrcLabBody').toggle();
    $(this).text(show ? 'View Summary' : 'View Full Tests');
  });

  /* =========================
     Label preview & QR/Barcode
  ========================== */
  function buildQRUrl (text) { return 'https://api.qrserver.com/v1/create-qr-code?size=150x150&data=' + encodeURIComponent(text || ''); }
  function buildBarcodeUrl (text) {
    return 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' + encodeURIComponent(text || '') +
           '&scale=3&height=12&textxalign=center&includetext';
  }

  $('body').on('click', '.print-label', function () {
    const $c = $(this).closest('.product-card');
    window.printPkg = $c.data('metrc') || {};
    window.printLabs = $c.data('labs') || [];
    $('#labelWidth').val(4);
    $('#labelHeight').val(2);
    try {
      const p = window.printPkg;
      const payload = (p && typeof p.payload === 'string') ? JSON.parse(p.payload) : (p?.payload || p || {});
      $('#labelWeight').val(payload?.Item?.UnitWeight || 1);
    } catch { $('#labelWeight').val(1); }
    $('#labelNotes').val('');
    updateLabelPreview();
    $('#printLabelModal').modal('show');
  });

  $('#printLabelModal').on('shown.bs.modal', updateLabelPreview);
  $('#labelWidth,#labelHeight,#labelWeight,#labelNotes,#includeQr').on('input change', updateLabelPreview);

  function updateLabelPreview () {
    const p = window.printPkg || {};
    let payload = p?.payload || p || {};
    if (typeof payload === 'string') { try { payload = JSON.parse(payload); } catch (e) {} }
    const item = payload.Item || payload || {};
    const biz = '{{ setting_by_key("business_name") ?? config("app.name") }}';
    const wt = parseFloat($('#labelWeight').val()) || 0;
    const notes = $('#labelNotes').val();
    const includeQr = $('#includeQr').is(':checked');
    const sku = item.Sku || p.Sku || p.Label || '';

    const $root = $('#labelPreview').empty();
    const col = (html) => $('<div class="label-column">').append(html);
    const now = new Date().toLocaleString();

    const $c1 = col([
      $('<h1>').text(biz),
      $('<p>').html('<strong>(Usable Marijuana) - ' + $('<div>').text(item.Name || '').html() + '</strong>'),
      $('<p>').text((wt || 0) + ' g'),
      $('<p>').text(now),
      $('<p>').text('SKU: ' + (sku || '')),
      notes ? $('<p>').text(notes) : null
    ]);
    const $c2 = col([
      $('<p>').text('Produced by: ' + (payload.ItemFromFacilityName || '')),
      includeQr ? $('<div style="margin-top:0.1in;text-align:center;">')
        .append($('<img>').attr({ src: buildQRUrl(sku), alt: 'QR' }).css({ width: '.5in', height: '.5in' })) : null
    ]);
    const $c3 = col([
      $('<p>').text('For use only by adults 21 and older. Keep out of reach of children. Do not drive a motor vehicle while under the influence of marijuana.'),
      $('<p>').html('<strong>BE CAUTIOUS</strong>')
    ]);

    $root.append($c1, $c2, $c3);
  }

  // QR/Barcode modal
  (function initCodePrint () {
    function setPreview (type, sku) {
      const src = type === 'qr' ? buildQRUrl(sku) : buildBarcodeUrl(sku);
      const $img = $('#qrPreview');
      $img.off('error._qr load._qr')
          .attr('src', '')
          .on('load._qr', function () { $(this).removeClass('img-error'); })
          .on('error._qr', function () { $(this).addClass('img-error'); toastr.error('Failed to load code image.'); })
          .attr('src', src + '&_=' + Date.now());
    }

    $('body').on('click', '.print-qr', function () {
      const $pc = $(this).closest('.product-card');
      const sku = String($pc.data('sku') || '');
      const name = String($pc.data('name') || '');
      const meta = $pc.data('metrc') || {};
      const pkgId = String($pc.data('label') || meta.Label || (meta.payload && meta.payload.Label) || '');
      window._codeData = { sku, name, pkgId, type: 'barcode' };
      $('#codeTypeBarcode').prop('checked', true);
      $('#codeTypeQR').prop('checked', false);
      $('#codeName').text(name || 'N/A');
      $('#codePkgId').text(pkgId || 'N/A');
      $('#codeSku').text(sku || 'N/A');
      setPreview('barcode', sku);
      $('#printQRModal').modal('show');
    });

    $('body').on('change', 'input[name="codeType"]', function () {
      const type = $(this).val() === 'qr' ? 'qr' : 'barcode';
      if (window._codeData) { window._codeData.type = type; setPreview(type, window._codeData.sku || ''); }
    });

    $('#printQRConfirm').on('click', function () {
      const data = window._codeData || {};
      const type = data.type === 'qr' ? 'qr' : 'barcode';
      const imgSrc = (type === 'qr' ? buildQRUrl(data.sku || '') : buildBarcodeUrl(data.sku || ''));

      const pop = window.open('', '_blank');
      if (!pop) { toastr.error('Popup blocked. Allow popups to print.'); return; }

      const html =
        '<!doctype html><html><head><meta charset="utf-8"><title>Print Code</title>' +
        '<style>@page{ size:4in 3in; margin:0.15in; } body{ margin:0; font-family:sans-serif; }' +
        '.wrap{ display:flex; flex-direction:column; align-items:center; } img{ max-width:100%; height:auto; }' +
        '.meta{ width:100%; font-size:11pt; margin-top:0.15in; } .meta p{ margin:.05in 0; } .name{ font-weight:700; }</style>' +
        '</head><body><div class="wrap">' +
        '<img id="codeImg" alt="Code">' +
        '<div class="meta"><p class="name" id="nm"></p><p>PKG ID: <span id="pkg"></span></p><p>SKU: <span id="sku"></span></p></div>' +
        '</div></body></html>';

      pop.document.open(); pop.document.write(html); pop.document.close(); pop.focus();
      pop.onload = function () {
        try {
          const d = pop.document, img = d.getElementById('codeImg');
          d.getElementById('nm').textContent = data.name || '';
          d.getElementById('pkg').textContent = data.pkgId || '';
          d.getElementById('sku').textContent = data.sku || '';
          img.onerror = function () { d.body.insertAdjacentHTML('beforeend', '<p style="color:#c00;margin:.25in;">Failed to render code image.</p>'); };
          img.src = imgSrc + (imgSrc.indexOf('?') > -1 ? '&' : '?') + '_=' + Date.now();
          setTimeout(function () { try { pop.print(); } catch (e) {} }, 200);
        } catch (e) {}
      };
    });
  })();

  /* =========================
     Create Inventory Modal
  ========================== */
  function resizeSkuInput (el) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const style = window.getComputedStyle(el);
    ctx.font = style.fontSize + ' ' + style.fontFamily;
    const text = el.value || el.placeholder || '';
    const width = ctx.measureText(text).width + 30;
    el.style.width = width + 'px';
  }
  window.resizeSkuInput = resizeSkuInput;

  $('#openCreateInventory').on('click', function () {
    const $f = $('#createInventoryForm')[0];
    if ($f) $f.reset();
    $('#createErrors').addClass('d-none'); $('#createErrorsList').empty();
    $('#create_tier_section').hide();
    $('#create_price_section').show();
    $('#createInventoryModal').modal('show');
  });

  function createToggleSections () {
    const isFlower = ($('#create_category_id option:selected').text() || '').trim().toLowerCase() === 'flower';
    $('#create_tier_section').toggle(isFlower);
    $('#create_price_section').toggle(!isFlower);
  }
  $('#create_category_id').on('change', createToggleSections);

  const LEAF = ':gls:', leafRegex = /\s*:gls:\s*$/i, trailingImgRegex = /\s*<img[^>]+>\s*$/i;
  const cleanName  = val => (val || '').replace(trailingImgRegex, '').replace(leafRegex, '').trim();
  const appendLeaf = val => { const base = cleanName(val); return base ? (base + ' ' + LEAF) : LEAF; };

  $('#create_addLeaf').on('change', function () {
    let current = $('#create_name').val();
    if (this.checked) { if (!leafRegex.test(current)) { $('#create_name').val(appendLeaf(current)); } }
    else { $('#create_name').val(cleanName(current)); }
  });

  $('#create_generateSku').on('click', function () {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; let sku = '';
    for (let i = 0; i < 9; i++) { sku += chars.charAt(Math.floor(Math.random() * chars.length)); }
    $('#create_sku').val(sku);
    const el = document.getElementById('create_sku'); if (el) resizeSkuInput(el);
  });

  $('#createInventoryForm').on('submit', function (e) {
    e.preventDefault();
    let current = $('#create_name').val();
    if ($('#create_addLeaf').is(':checked')) { if (!leafRegex.test(current)) { $('#create_name').val(appendLeaf(current)); } }
    else { $('#create_name').val(cleanName(current)); }

    const form = this;
    const fd = new FormData(form);
    const url = form.action;
    const $btn = $(form).find('button[type="submit"]').prop('disabled', true).text('Creating…');

    $.ajax({
      url: url,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: { 'Accept': 'application/json' }
    })
      .done(function () {
        toastr.success('Inventory created');
        $('#createInventoryModal').modal('hide');
        const ret = $(form).find('input[name="return_url"]').val() || window.location.href;
        window.location.assign(ret);
      })
      .fail(function (xhr) {
        if (xhr.status >= 200 && xhr.status < 400) {
          const ret = $(form).find('input[name="return_url"]').val() || window.location.href;
          window.location.assign(ret);
          return;
        }
        const errs = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : null;
        $('#createErrorsList').empty();
        if (errs) {
          Object.keys(errs).forEach(function (k) {
            (errs[k] || []).forEach(function (msg) {
              $('#createErrorsList').append('<li>' + String(msg) + '</li>');
            });
          });
          $('#createErrors').removeClass('d-none');
        } else {
          toastr.error('Could not create inventory.');
        }
      })
      .always(function () { $btn.prop('disabled', false).text('Create'); });
  });

  $('#createInventoryModal').on('shown.bs.modal', function () {
    createToggleSections();
    const el = document.getElementById('create_sku'); if (el) resizeSkuInput(el);
    $('#create_name').trigger('focus');
  });

  /* =========================
     CSV Import wiring (uploads & runs ImportInventoriesSeeder)
  ========================== */
  $('#openCsvImport').on('click', function(){
    $('#csvImportForm')[0].reset();
    $('#csvImportOutput').text('(no output yet)');
    $('#csvImportModal').modal('show');
  });
function poll(key){
  const STATUS_URL = '{{ route('inventories.import.status', '__K__') }}'.replace('__K__', encodeURIComponent(key));
  const $btn = $('#runImportBtn');
  const $out = $('#csvImportOutput');

  let last = '';
  (function tick(){
    $.getJSON(STATUS_URL)
      .done(function(res){
        if (!res || !res.ok) return;

        const line = [
          'status=' + res.status,
          'processed=' + res.processed + '/' + res.total,
          'created=' + res.created,
          'updated=' + res.updated,
          'errors=' + res.errors
        ].join(' | ');

        if (line !== last) {
          $out.text(($out.text() + '\n' + line).trim());
          last = line;
        }

        if (res.status === 'done' || res.status === 'failed') {
          $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Run Import');
          if (res.status === 'done')  toastr.success('Import completed');  else toastr.error('Import failed'); 
          setTimeout(() => window.location.reload(), 750);
        } else {
          setTimeout(tick, 1200);
        }
      })
      .fail(function(xhr){
        const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Status polling failed';
        toastr.error(msg);
        $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Run Import');
      });
  })();
}

  $('#showTemplateHint').on('click', function(){
    swal({
      title: 'CSV Columns',
      text:
        'Header row required. Common columns used by the seeder:\n' +
        'Category, Product Name, Variant Name, Brand, SKU, Quantity,\n' +
        'Regulatory ID, Room, % THC, % CBD, Weight, Cost, Price\n\n' +
        '• METRC rows: group by Regulatory ID; Label = Regulatory ID\n' +
        '• Non-METRC rows: group by SKU (fallback Product+Variant)\n' +
        '• Inventory Type auto-sets from Room (Sales Floor vs Hold)',
      type: 'info'
    });
  });

$('#csvImportForm').off('submit').on('submit', function(e){
  e.preventDefault();
  const $btn = $('#runImportBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Queued…');
  const fd = new FormData(this);

  $.ajax({
    url: ROUTE_IMPORT,
    method: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    headers: { 'Accept': 'application/json' }
  })
  .done(function(res){
    if (!res || !res.ok || !res.key) {
      toastr.error((res && res.message) || 'Failed to queue import.');
      return;
    }
    toastr.success('Import started.');
    poll(res.key);
  })
  .fail(function (xhr) {
    var msg =
      (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) ||
      (xhr.status ? (xhr.status + ' ' + xhr.statusText) : '') ||
      xhr.responseText ||
      'Failed to queue import.';

    toastr.error(msg);
    $('#csvImportOutput').text(String(msg).slice(0, 8000));
    $('#runImportBtn').prop('disabled', false).html('<i class="fa fa-play"></i> Run Import');
  });
});


  /* =========================
     Initial render
  ========================== */
  fetchOrgSubtypes().always(function () {
    populateSubtypeFilter();
  });

  setActiveType('all');
  updateSubtypeVisibilityFlag();

  if (activeSubtype) {
    $('#subtypeFilter').val(activeSubtype);
    showSubtypeStage();
  } else if (norm($('#inventorySearch').val())) {
    enterSearchMode();
  }

  applyFiltersNow();
  applySort();
});
</script>

@endsection
