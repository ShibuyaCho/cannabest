@extends('layouts.app')

@section('content')

@php
    // Normalize discount tiers coming from settings or old form data
    $rawDT = old('discount_tiers', setting_by_key('discount_tiers'));
    $discountTiers = [];
    if (is_string($rawDT)) {
        $decoded = json_decode($rawDT, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $discountTiers = $decoded;
        }
    } elseif (is_array($rawDT)) {
        $discountTiers = $rawDT;
    }

    // Useful defaults for new tiers/options
    $defaultOptions    = ['1g', '1/8OZ', '1/4OZ', '1/2OZ', '1OZ'];
    $defaultQuantities = [1, 3.5, 7, 14, 28];
@endphp

<link href="{{ asset('assets/css/plugins/toastr/toastr.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>General Settings</h5>
          <div class="ibox-tools">
            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
          </div>
        </div>

        <div class="ibox-content">
          <form action="{{ url('settings/update') }}" class="form-horizontal" method="POST" enctype="multipart/form-data" autocomplete="off">
            {{ csrf_field() }}

            {{-- ==================== SMS Alert Phone Numbers ==================== --}}
            <div class="form-group">
              <label class="col-sm-2 control-label">SMS Alert Numbers</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  name="sms_alert_phone_numbers"
                  value="{{ old('sms_alert_phone_numbers', setting_by_key('sms_alert_phone_numbers')) }}"
                  placeholder="+15551234567, +15557654321"
                >
                <small class="help-block">
                  Comma-separated list of numbers to text when a customer is created.
                </small>
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- ==================== Enable/Disable SMS on Customer Creation ==================== --}}
            <div class="form-group">
              <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                  <label>
                    <input
                      type="checkbox"
                      name="sms_alert_customer_creation"
                      value="1"
                      {{ old('sms_alert_customer_creation', setting_by_key('sms_alert_customer_creation')) ? 'checked' : '' }}
                    > Enable SMS alert when a customer is created
                  </label>
                </div>
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- ==================== Other Settings (exclude discount_tiers here to avoid conflicts) ==================== --}}
            @forelse($settings as $setting)
              @if($setting->key === 'discount_tiers')
                @continue
              @endif

              <div class="form-group">
                <label class="col-sm-2 control-label">{{ $setting->label }}</label>
                <div class="col-sm-10">
                  <input type="text"
                         class="form-control"
                         id="{{ $setting->key }}"
                         name="{{ $setting->key }}"
                         value="{{ old($setting->key, is_array($setting->value) ? json_encode($setting->value) : $setting->value) }}">
                </div>
              </div>
              <div class="hr-line-dashed"></div>
            @empty
              <p class="text-muted">No settings found.</p>
            @endforelse

            {{-- ==================== Logo ==================== --}}
            <div class="form-group">
              <label class="col-sm-2 control-label">Logo</label>
              <div class="col-sm-10">
                <input type="file" name="logo" class="form-control"/>
                <div class="m-t-sm">
                  <img src="{{ url('public/uploads/logo.jpg?r=' . rand(0,999)) }}" width="100" alt="Logo">
                </div>
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- ==================== Discount Tiers (Nested) ==================== --}}
            <div class="form-group">
              <label class="col-sm-2 control-label">Discount Tiers</label>
              <div class="col-sm-10">
                <div id="discount-tiers-container">
                  @if(!empty($discountTiers))
                    @foreach($discountTiers as $tIndex => $tier)
                      @php
                        // Ensure numeric index (in case keys are strings)
                        $tIndexNum = is_numeric($tIndex) ? (int)$tIndex : $loop->index;
                        $pricing = isset($tier['pricing']) && is_array($tier['pricing']) ? $tier['pricing'] : [];
                      @endphp
                      <div class="tier" data-tier-index="{{ $tIndexNum }}">
                        <div class="form-group">
                          <label>Tier Name:</label>
                          <input type="text"
                                 name="discount_tiers[{{ $tIndexNum }}][name]"
                                 class="form-control"
                                 value="{{ $tier['name'] ?? '' }}"
                                 placeholder="Tier Name">
                        </div>

                        <div class="pricing-options">
                          <table class="table">
                            <thead>
                              <tr>
                                <th>Option Name</th>
                                <th>Min Quantity</th>
                                <th>Price</th>
                                <th>Price Per Gram</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              @if(!empty($pricing))
                                @foreach($pricing as $pIndex => $p)
                                  @php
                                    $pIndexNum = is_numeric($pIndex) ? (int)$pIndex : $loop->index;
                                    $minQ = isset($p['min_quantity']) ? (float)$p['min_quantity'] : 0;
                                    $price = isset($p['price']) ? (float)$p['price'] : 0;
                                    $ppg = ($minQ > 0) ? number_format($price / $minQ, 4) : '0.0000';
                                  @endphp
                                  <tr data-pricing-index="{{ $pIndexNum }}">
                                    <td>
                                      <input type="text"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIndexNum }}][name]"
                                             class="form-control"
                                             value="{{ $p['name'] ?? '' }}"
                                             placeholder="Option Name">
                                    </td>
                                    <td>
                                      <input type="number"
                                             step="0.01"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIndexNum }}][min_quantity]"
                                             class="form-control min_quantity_field"
                                             value="{{ $minQ }}"
                                             placeholder="Min Quantity">
                                    </td>
                                    <td>
                                      <input type="number"
                                             step="0.01"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIndexNum }}][price]"
                                             class="form-control price-field"
                                             value="{{ $price }}"
                                             placeholder="Price">
                                    </td>
                                    <td>
                                      <input type="text" readonly class="form-control price-per-gram" value="{{ $ppg }}">
                                    </td>
                                    <td>
                                      <button type="button" class="btn btn-danger remove-pricing-option">Remove</button>
                                    </td>
                                  </tr>
                                @endforeach
                              @else
                                @foreach($defaultOptions as $pIdx => $opt)
                                  <tr data-pricing-index="{{ $pIdx }}">
                                    <td>
                                      <input type="text"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIdx }}][name]"
                                             class="form-control"
                                             value="{{ $opt }}"
                                             placeholder="Option Name">
                                    </td>
                                    <td>
                                      <input type="number"
                                             step="0.01"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIdx }}][min_quantity]"
                                             class="form-control min_quantity_field"
                                             value="{{ $defaultQuantities[$pIdx] }}"
                                             placeholder="Min Quantity">
                                    </td>
                                    <td>
                                      <input type="number"
                                             step="0.01"
                                             name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIdx }}][price]"
                                             class="form-control price-field"
                                             placeholder="Price">
                                    </td>
                                    <td>
                                      <input type="text" readonly class="form-control price-per-gram" value="0.0000">
                                    </td>
                                    <td>
                                      <button type="button" class="btn btn-danger remove-pricing-option">Remove</button>
                                    </td>
                                  </tr>
                                @endforeach
                              @endif
                            </tbody>
                          </table>
                          <button type="button" class="btn btn-secondary add-pricing-option">Add Pricing Option</button>
                        </div>

                        <button type="button" class="btn btn-danger remove-tier m-t-sm">Remove Tier</button>
                        <hr>
                      </div>
                    @endforeach
                  @else
                    {{-- If no tiers exist, render a default starting tier at index 0 --}}
                    <div class="tier" data-tier-index="0">
                      <div class="form-group">
                        <label>Tier Name:</label>
                        <input type="text" name="discount_tiers[0][name]" class="form-control" placeholder="Tier Name">
                      </div>

                      <div class="pricing-options">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>Option Name</th>
                              <th>Min Quantity</th>
                              <th>Price</th>
                              <th>Price Per Gram</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($defaultOptions as $pIdx => $opt)
                              <tr data-pricing-index="{{ $pIdx }}">
                                <td>
                                  <input type="text"
                                         name="discount_tiers[0][pricing][{{ $pIdx }}][name]"
                                         class="form-control"
                                         value="{{ $opt }}"
                                         placeholder="Option Name">
                                </td>
                                <td>
                                  <input type="number"
                                         step="0.01"
                                         name="discount_tiers[0][pricing][{{ $pIdx }}][min_quantity]"
                                         class="form-control min_quantity_field"
                                         value="{{ $defaultQuantities[$pIdx] }}"
                                         placeholder="Min Quantity">
                                </td>
                                <td>
                                  <input type="number"
                                         step="0.01"
                                         name="discount_tiers[0][pricing][{{ $pIdx }}][price]"
                                         class="form-control price-field"
                                         placeholder="Price">
                                </td>
                                <td>
                                  <input type="text" readonly class="form-control price-per-gram" value="0.0000">
                                </td>
                                <td>
                                  <button type="button" class="btn btn-danger remove-pricing-option">Remove</button>
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                        <button type="button" class="btn btn-secondary add-pricing-option">Add Pricing Option</button>
                      </div>

                      <button type="button" class="btn btn-danger remove-tier m-t-sm">Remove Tier</button>
                      <hr>
                    </div>
                  @endif
                </div>

                <button type="button" class="btn btn-secondary" id="add-tier">Add Tier</button>
              </div>
            </div>

            <div class="hr-line-dashed"></div>

            {{-- ==================== Save / Cancel ==================== --}}
            <div class="form-group">
              <div class="col-sm-4 col-sm-offset-2">
                <a class="btn btn-white" href="{{ url('settings/general') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Changes</button>
              </div>
            </div>

          </form>
        </div> {{-- /.ibox-content --}}
      </div> {{-- /.ibox --}}
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
(function($){
  $(function(){

    // ---------- Helpers to avoid index collisions ----------
    function nextTierIndexFromDOM() {
      var idxs = Array.from(document.querySelectorAll('#discount-tiers-container .tier'))
        .map(el => parseInt(el.getAttribute('data-tier-index'), 10))
        .filter(Number.isFinite);
      return idxs.length ? Math.max(...idxs) + 1 : 0;
    }

    function nextPricingIndexFromTbody(tbodyEl) {
      var idxs = Array.from(tbodyEl.querySelectorAll('tr[data-pricing-index]'))
        .map(tr => parseInt(tr.getAttribute('data-pricing-index'), 10))
        .filter(Number.isFinite);
      return idxs.length ? Math.max(...idxs) + 1 : 0;
    }

    // ---------- Price-per-gram updater ----------
    function updatePricePerGram(row){
      var minQuantity = parseFloat($(row).find('.min_quantity_field').val()) || 0;
      var price       = parseFloat($(row).find('.price-field').val()) || 0;
      var computed    = (minQuantity > 0) ? (price / minQuantity).toFixed(4) : '0.0000';
      $(row).find('.price-per-gram').val(computed);
    }

    // Recalc on input changes
    $('#discount-tiers-container').on('input', '.min_quantity_field, .price-field', function(){
      var row = $(this).closest('tr')[0];
      if (row) updatePricePerGram(row);
    });

    // Initial recalc pass
    $('#discount-tiers-container tr').each(function(){
      updatePricePerGram(this);
    });

    // Defaults used when adding new rows
    var defaultOptions    = @json($defaultOptions);
    var defaultQuantities = @json($defaultQuantities);

    // ---------- Add Tier ----------
    $('#add-tier').on('click', function(){
      var tierIndex = nextTierIndexFromDOM();

      var pricingRows = '';
      for (var p = 0; p < defaultOptions.length; p++) {
        var opt = defaultOptions[p] || '';
        var dq  = (typeof defaultQuantities[p] !== 'undefined') ? defaultQuantities[p] : '';
        pricingRows += `
          <tr data-pricing-index="${p}">
            <td>
              <input type="text" name="discount_tiers[${tierIndex}][pricing][${p}][name]" class="form-control" value="${opt}" placeholder="Option Name">
            </td>
            <td>
              <input type="number" step="0.01" name="discount_tiers[${tierIndex}][pricing][${p}][min_quantity]" class="form-control min_quantity_field" value="${dq}" placeholder="Min Quantity">
            </td>
            <td>
              <input type="number" step="0.01" name="discount_tiers[${tierIndex}][pricing][${p}][price]" class="form-control price-field" placeholder="Price">
            </td>
            <td>
              <input type="text" readonly class="form-control price-per-gram" value="0.0000">
            </td>
            <td>
              <button type="button" class="btn btn-danger remove-pricing-option">Remove</button>
            </td>
          </tr>
        `;
      }

      var tierHtml = `
        <div class="tier" data-tier-index="${tierIndex}">
          <div class="form-group">
            <label>Tier Name:</label>
            <input type="text" name="discount_tiers[${tierIndex}][name]" class="form-control" placeholder="Tier Name">
          </div>

          <div class="pricing-options">
            <table class="table">
              <thead>
                <tr>
                  <th>Option Name</th>
                  <th>Min Quantity</th>
                  <th>Price</th>
                  <th>Price Per Gram</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                ${pricingRows}
              </tbody>
            </table>
            <button type="button" class="btn btn-secondary add-pricing-option">Add Pricing Option</button>
          </div>

          <button type="button" class="btn btn-danger remove-tier m-t-sm">Remove Tier</button>
          <hr>
        </div>
      `;

      $('#discount-tiers-container').append(tierHtml);
    });

    // ---------- Remove Tier ----------
    $(document).on('click', '.remove-tier', function(){
      $(this).closest('.tier').remove();
    });

    // ---------- Add Pricing Row ----------
    $(document).on('click', '.add-pricing-option', function(){
      var tierDiv = $(this).closest('.tier');
      var tIndex  = parseInt(tierDiv.attr('data-tier-index'), 10);
      var tbody   = tierDiv.find('table tbody')[0];
      var nextP   = nextPricingIndexFromTbody(tbody);

      // Default min_quantity if adding beyond defaults
      var defaultValue = (nextP < defaultQuantities.length) ? defaultQuantities[nextP] : '';

      var newRow = `
        <tr data-pricing-index="${nextP}">
          <td>
            <input type="text" name="discount_tiers[${tIndex}][pricing][${nextP}][name]" class="form-control" placeholder="Option Name">
          </td>
          <td>
            <input type="number" step="0.01" name="discount_tiers[${tIndex}][pricing][${nextP}][min_quantity]" class="form-control min_quantity_field" value="${defaultValue}" placeholder="Min Quantity">
          </td>
          <td>
            <input type="number" step="0.01" name="discount_tiers[${tIndex}][pricing][${nextP}][price]" class="form-control price-field" placeholder="Price">
          </td>
          <td>
            <input type="text" readonly class="form-control price-per-gram" value="0.0000">
          </td>
          <td>
            <button type="button" class="btn btn-danger remove-pricing-option">Remove</button>
          </td>
        </tr>
      `;
      $(tbody).append(newRow);
    });

    // ---------- Remove Pricing Row ----------
    $(document).on('click', '.remove-pricing-option', function(){
      $(this).closest('tr').remove();
    });

  });
})(jQuery);
</script>
@endsection
