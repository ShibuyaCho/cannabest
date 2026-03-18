@extends('layouts.app')

@section('content')
@php
    // ---------- Normalize discount tiers ----------
    $rawDT = old('discount_tiers', $org->discount_tiers ?? []);
    if (is_string($rawDT)) {
        $decoded = json_decode($rawDT, true);
        $discountTiers = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    } elseif (is_array($rawDT)) {
        $discountTiers = $rawDT;
    } else {
        $discountTiers = [];
    }

    // Defaults for new tiers/options
    $defaultOptions    = ['1g','1/8OZ','1/4OZ','1/2OZ','1OZ'];
    $defaultQuantities = [1, 3.5, 7, 14, 28];

    // Normalize SMS text area value (array or string → lines)
    $smsOld = old('sms_alert_phone_numbers');
    if (is_array($smsOld)) {
        $smsTextareaValue = implode("\n", $smsOld);
    } elseif (is_string($smsOld)) {
        $smsTextareaValue = $smsOld;
    } else {
        $orgSms = $org->sms_alert_phone_numbers ?? [];
        if (is_array($orgSms)) {
            $smsTextareaValue = implode("\n", $orgSms);
        } else {
            $smsTextareaValue = (string) $orgSms;
        }
    }
@endphp

<div class="container">
  <form action="{{ route('organizations.update') }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Contact & Address --}}
    <div class="form-group">
      <label for="phone">Phone</label>
      <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $org->phone) }}">
    </div>

    <div class="form-group">
      <label for="physical_address">Address</label>
      <input id="physical_address" type="text" name="physical_address" class="form-control" value="{{ old('physical_address', $org->physical_address) }}">
    </div>

    {{-- Tax/VAT --}}
    <div class="row mb-4">
      @foreach(['county_tax','city_tax','state_tax'] as $tax)
      <div class="col">
        <label for="{{ $tax }}" class="font-weight-bold">{{ strtoupper(str_replace('_',' ', $tax)) }}%</label>
        <input id="{{ $tax }}" type="number" name="{{ $tax }}" min="0" max="100" class="form-control" value="{{ old($tax, $org->$tax) }}">
      </div>
      @endforeach
    </div>

    {{-- Discount Tiers Section (Nested Structure) --}}
    <div class="form-group">
      <label class="control-label d-block mb-2">Discount Tiers</label>

      <div id="discount-tiers-container">
        @if(!empty($discountTiers))
          @foreach($discountTiers as $tIndex => $tier)
            @php
              // Ensure numeric index for attributes/names
              $tIndexNum = is_numeric($tIndex) ? (int)$tIndex : $loop->index;
              $pricing   = (isset($tier['pricing']) && is_array($tier['pricing'])) ? $tier['pricing'] : [];
            @endphp
            <div class="tier card mb-4 p-3" data-tier-index="{{ $tIndexNum }}">
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
                        <tr class="pricing-row" data-pricing-index="{{ $pIndexNum }}">
                          <td>
                            <input type="text"
                              name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIndexNum }}][name]"
                              class="form-control"
                              value="{{ $p['name'] ?? '' }}"
                              placeholder="Option Name">
                          </td>
                          <td>
                            <input type="number" step="0.01"
                              name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIndexNum }}][min_quantity]"
                              class="form-control min_quantity_field"
                              value="{{ $minQ }}"
                              placeholder="Min Quantity">
                          </td>
                          <td>
                            <input type="number" step="0.01"
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
                        <tr class="pricing-row" data-pricing-index="{{ $pIdx }}">
                          <td>
                            <input type="text"
                              name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIdx }}][name]"
                              class="form-control"
                              value="{{ $opt }}"
                              placeholder="Option Name">
                          </td>
                          <td>
                            <input type="number" step="0.01"
                              name="discount_tiers[{{ $tIndexNum }}][pricing][{{ $pIdx }}][min_quantity]"
                              class="form-control min_quantity_field"
                              value="{{ $defaultQuantities[$pIdx] }}"
                              placeholder="Min Quantity">
                          </td>
                          <td>
                            <input type="number" step="0.01"
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

              <button type="button" class="btn btn-danger remove-tier mt-3">Remove Tier</button>
            </div>
          @endforeach
        @else
          {{-- Default single tier --}}
          <div class="tier card mb-4 p-3" data-tier-index="0">
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
                    <tr class="pricing-row" data-pricing-index="{{ $pIdx }}">
                      <td>
                        <input type="text"
                          name="discount_tiers[0][pricing][{{ $pIdx }}][name]"
                          class="form-control"
                          value="{{ $opt }}"
                          placeholder="Option Name">
                      </td>
                      <td>
                        <input type="number" step="0.01"
                          name="discount_tiers[0][pricing][{{ $pIdx }}][min_quantity]"
                          class="form-control min_quantity_field"
                          value="{{ $defaultQuantities[$pIdx] }}"
                          placeholder="Min Quantity">
                      </td>
                      <td>
                        <input type="number" step="0.01"
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

            <button type="button" class="btn btn-danger remove-tier mt-3">Remove Tier</button>
          </div>
        @endif
      </div>

      <button type="button" class="btn btn-secondary" id="add-tier">Add Tier</button>
    </div>

    <hr>

    {{-- SMS settings --}}
    <div class="form-group">
      <label for="sms_alert_phone_numbers">SMS Alert Phone Numbers (one per line)</label>
      <textarea id="sms_alert_phone_numbers" name="sms_alert_phone_numbers" class="form-control" rows="2">{{ $smsTextareaValue }}</textarea>
      <small class="form-text text-muted">One number per line, no dashes.</small>
    </div>

    <div class="form-check mb-4">
      <input type="hidden" name="sms_alert_customer_creation" value="0">
      <input type="checkbox" class="form-check-input" id="smsCustomer" name="sms_alert_customer_creation" value="1" {{ old('sms_alert_customer_creation', $org->sms_alert_customer_creation) ? 'checked' : '' }}>
      <label class="form-check-label" for="smsCustomer">Send SMS on Customer Creation</label>
    </div>

    <button class="btn btn-primary">Save Settings</button>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
  // Defaults from backend
  const defaultOptions    = @json($defaultOptions);
  const defaultQuantities = @json($defaultQuantities);

  const tiersContainer = document.getElementById('discount-tiers-container');
  const addTierBtn     = document.getElementById('add-tier');

  // ----- Helpers to avoid overwriting -----
  function nextTierIndexFromDOM() {
    const idxs = Array.from(tiersContainer.querySelectorAll('.tier'))
      .map(el => parseInt(el.getAttribute('data-tier-index'), 10))
      .filter(Number.isFinite);
    return idxs.length ? Math.max(...idxs) + 1 : 0;
  }

  function nextPricingIndexFromTier(tierEl) {
    const idxs = Array.from(tierEl.querySelectorAll('tr[data-pricing-index]'))
      .map(tr => parseInt(tr.getAttribute('data-pricing-index'), 10))
      .filter(Number.isFinite);
    return idxs.length ? Math.max(...idxs) + 1 : 0;
  }

  // ----- Price-per-gram -----
  function recalcRow(row) {
    const qty   = parseFloat(row.querySelector('.min_quantity_field')?.value) || 0;
    const price = parseFloat(row.querySelector('.price-field')?.value) || 0;
    const ppg   = qty > 0 ? (price / qty).toFixed(4) : '0.0000';
    const out   = row.querySelector('.price-per-gram');
    if (out) out.value = ppg;
  }

  function recalcAll() {
    tiersContainer.querySelectorAll('tr.pricing-row').forEach(recalcRow);
  }

  // ----- Add Tier -----
  addTierBtn.addEventListener('click', function(){
    const tIndex = nextTierIndexFromDOM();

    // Build default pricing rows
    let rowsHtml = '';
    for (let p = 0; p < defaultOptions.length; p++) {
      const opt = defaultOptions[p] || '';
      const dq  = typeof defaultQuantities[p] !== 'undefined' ? defaultQuantities[p] : '';
      rowsHtml += `
        <tr class="pricing-row" data-pricing-index="${p}">
          <td>
            <input type="text" name="discount_tiers[${tIndex}][pricing][${p}][name]" class="form-control" value="${opt}" placeholder="Option Name">
          </td>
          <td>
            <input type="number" step="0.01" name="discount_tiers[${tIndex}][pricing][${p}][min_quantity]" class="form-control min_quantity_field" value="${dq}" placeholder="Min Quantity">
          </td>
          <td>
            <input type="number" step="0.01" name="discount_tiers[${tIndex}][pricing][${p}][price]" class="form-control price-field" placeholder="Price">
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

    const tierDiv = document.createElement('div');
    tierDiv.className = 'tier card mb-4 p-3';
    tierDiv.setAttribute('data-tier-index', tIndex);
    tierDiv.innerHTML = `
      <div class="form-group">
        <label>Tier Name:</label>
        <input type="text" name="discount_tiers[${tIndex}][name]" class="form-control" placeholder="Tier Name">
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
            ${rowsHtml}
          </tbody>
        </table>

        <button type="button" class="btn btn-secondary add-pricing-option">Add Pricing Option</button>
      </div>

      <button type="button" class="btn btn-danger remove-tier mt-3">Remove Tier</button>
    `;

    tiersContainer.appendChild(tierDiv);
  });

  // ----- Delegated events inside tiers container -----
  tiersContainer.addEventListener('click', function(e){
    // Remove Tier
    if (e.target.classList.contains('remove-tier')) {
      e.preventDefault();
      const tier = e.target.closest('.tier');
      if (tier) tier.remove();
      return;
    }

    // Add Pricing Option
    if (e.target.classList.contains('add-pricing-option')) {
      e.preventDefault();
      const tier = e.target.closest('.tier');
      if (!tier) return;
      const tIndex = parseInt(tier.getAttribute('data-tier-index'), 10);
      const tbody  = tier.querySelector('tbody');
      const nextP  = nextPricingIndexFromTier(tier);
      const dq     = (nextP < defaultQuantities.length) ? defaultQuantities[nextP] : '';

      const row = document.createElement('tr');
      row.className = 'pricing-row';
      row.setAttribute('data-pricing-index', nextP);
      row.innerHTML = `
        <td>
          <input type="text" name="discount_tiers[${tIndex}][pricing][${nextP}][name]" class="form-control" placeholder="Option Name">
        </td>
        <td>
          <input type="number" step="0.01" name="discount_tiers[${tIndex}][pricing][${nextP}][min_quantity]" class="form-control min_quantity_field" value="${dq}" placeholder="Min Quantity">
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
      `;
      tbody.appendChild(row);
      return;
    }

    // Remove Pricing Row
    if (e.target.classList.contains('remove-pricing-option')) {
      e.preventDefault();
      const row = e.target.closest('tr');
      if (row) row.remove();
      return;
    }
  });

  // Live PPG recalculation
  tiersContainer.addEventListener('input', function(e){
    if (e.target.classList.contains('min_quantity_field') || e.target.classList.contains('price-field')) {
      const row = e.target.closest('tr.pricing-row');
      if (row) recalcRow(row);
    }
  });

  // Initial pass
  recalcAll();
})();
</script>
@endpush
