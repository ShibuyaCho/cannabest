{{-- resources/views/inventories/print_label.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <title>Print Label</title>
  <style>
    @media print { body { margin:0; padding:0; } }
    body { font-family: Arial, sans-serif; margin:0; padding:0; }
    .label { display:flex; width:300mm; height:100mm; padding:10mm; box-sizing:border-box; font-size:12pt; }
    .label-section { flex:1; padding:0 8px; box-sizing:border-box; }
    .bold{ font-weight:bold; } .small{ font-size:10pt; } .bottom{ margin-top:20px; }
    .label div + div { margin-top:6px; }
  </style>
</head>
<body onload="window.print()">
@php
    // ---------- helpers ----------
    if (!function_exists('norm_pct')) {
        function norm_pct($v) {
            if ($v === null || $v === '') return null;
            if (is_string($v)) {
                // strip % and LOQ symbols/spaces
                $raw = trim($v);
                if (preg_match('/<\s*LOQ/i', $raw)) return null;
                $raw = str_replace(['%','≥','>','<','~'], '', $raw);
                if ($raw === '') return null;
                $v = (float)$raw;
            }
            // Heuristics:
            // 0–1.2   => fraction -> %
            // >100    => mg/g -> % (≈ mg/g / 10)
            // else    => already %
            if ($v <= 1.2)     $v = $v * 100;
            elseif ($v > 100)  $v = $v / 10;
            return round($v, 2);
        }
    }
    if (!function_exists('key_pick')) {
        function key_pick($arr, array $cands) {
            if (!is_array($arr)) return null;
            // build case/format-insensitive map
            $map = [];
            foreach ($arr as $k=>$val) {
                $kk = strtolower(preg_replace('/[^a-z0-9]/i','', (string)$k));
                $map[$kk] = $val;
            }
            foreach ($cands as $k) {
                $kk = strtolower(preg_replace('/[^a-z0-9]/i','', $k));
                if (array_key_exists($kk, $map)) return $map[$kk];
            }
            return null;
        }
    }
    if (!function_exists('first_nonnull')) {
        function first_nonnull(...$vals) {
            foreach ($vals as $v) if (!is_null($v) && $v !== '') return $v;
            return null;
        }
    }

    // ---------- sources ----------
    $org = optional(auth()->user())->organization;
    $orgName  = $org->name           ?? 'Business Name';
    $orgLic   = $org->license_number ?? 'License #';
    $orgPhone = $org->phone          ?? 'Phone #';

    $categoryName = $inventory->categoryDetail->name
                 ?? optional($inventory->category)->name
                 ?? 'N/A';

    $pkg = $inventory->metrc_package ?? null;
    $pkgArr = $pkg ? json_decode(json_encode($pkg), true) : [];
    $payload = null;
    if ($pkg && is_string($pkg->payload ?? null)) {
        $decoded = json_decode($pkg->payload, true);
        $payload = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
    $payload = is_array($payload) ? $payload : [];

    $lab = null;
    if (method_exists($inventory, 'getAttribute')) {
        $labsCol = $inventory->metrc_full_labs ?? null;
        if ($labsCol && count($labsCol)) {
            $lab = json_decode(json_encode($labsCol->first()), true);
        }
    }
    $lab = is_array($lab) ? $lab : [];

    $invArr = json_decode(json_encode($inventory), true);

    // ---------- potency extraction ----------
    $thcPercentKeys = [
        'TotalThcPercent','Total THC Percent','THC Total %','TotalTHCPercent','THCPercent'
    ];
    $cbdPercentKeys = [
        'TotalCbdPercent','Total CBD Percent','CBD Total %','TotalCBDPercent','CBDPercent'
    ];

    // Try direct "Total … Percent"
    $thcPct = first_nonnull(
        norm_pct(key_pick($lab, $thcPercentKeys)),
        norm_pct(key_pick($pkgArr, $thcPercentKeys)),
        norm_pct(key_pick($payload, $thcPercentKeys)),
        norm_pct($invArr['thc_percent'] ?? null)
    );

    $cbdPct = first_nonnull(
        norm_pct(key_pick($lab, $cbdPercentKeys)),
        norm_pct(key_pick($pkgArr, $cbdPercentKeys)),
        norm_pct(key_pick($payload, $cbdPercentKeys)),
        norm_pct($invArr['cbd_percent'] ?? null)
    );

    // If still missing, compute from acids + neutrals
    if ($thcPct === null) {
        $d9Keys  = ['Delta9THC','Delta-9 THC','D9THC','THC','THC (Delta-9)'];
        $thcaKeys= ['THCA','THCA %','TotalTHCA','THCAPercent'];
        $d9  = norm_pct(first_nonnull(key_pick($lab,$d9Keys),  key_pick($pkgArr,$d9Keys),  key_pick($payload,$d9Keys)));
        $tha = norm_pct(first_nonnull(key_pick($lab,$thcaKeys),key_pick($pkgArr,$thcaKeys),key_pick($payload,$thcaKeys)));
        if ($d9 !== null || $tha !== null) {
            $thcPct = round(($d9 ?? 0) + 0.877 * ($tha ?? 0), 2);
        }
    }

    if ($cbdPct === null) {
        $cbdKeys  = ['CBD','CBD %','CBDPercent','TotalCBD'];
        $cbdaKeys = ['CBDA','CBDA %','TotalCBDA','CBDAPercent'];
        $cbd  = norm_pct(first_nonnull(key_pick($lab,$cbdKeys),  key_pick($pkgArr,$cbdKeys),  key_pick($payload,$cbdKeys)));
        $cbda = norm_pct(first_nonnull(key_pick($lab,$cbdaKeys), key_pick($pkgArr,$cbdaKeys), key_pick($payload,$cbdaKeys)));
        if ($cbd !== null || $cbda !== null) {
            $cbdPct = round(($cbd ?? 0) + 0.877 * ($cbda ?? 0), 2);
        }
    }

    // Final fallbacks
    $thcOut = number_format($thcPct ?? 0, 2) . '%';
    $cbdOut = $cbdPct === null ? '<LOQ%' : (number_format($cbdPct, 2) . '%');

  
    $reqWeight = request()->query('weight');
    if (is_numeric($reqWeight)) {
        $netWeight = (float)$reqWeight;
    } elseif (isset($inventory->weight) && is_numeric($inventory->weight)) {
        $netWeight = (float)$inventory->weight;
    } else {
        $pkgWeight = (float)(data_get($pkgArr, 'Weight', 0));
        $pkgQty    = (float)(data_get($pkgArr, 'Quantity', 1)) ?: 1.0;
        $netWeight = $pkgQty > 0 ? round($pkgWeight / $pkgQty, 2) : 1.00;
        if ($netWeight <= 0) $netWeight = 1.00;
    }

    // ---------- producer / package ----------
    $producerName = data_get($inventory, 'producer.name') ?? $orgName;
    $producerLic  = data_get($inventory, 'producer.license_number') ?? $orgLic;
    $packageLabel = $inventory->Label ?: (data_get($pkgArr,'Label') ?? 'N/A');
@endphp

<div class="label">
  <!-- Left -->
  <div class="label-section">
    <div class="bold">(Usable Marijuana): {{ $inventory->name }}</div>
    <div>{{ $categoryName }}</div>
    <div>{{ now()->format('m/d/Y , h:i A') }}</div>

    <div>{{ $orgName }}</div>
    <div>{{ $orgLic }}, {{ $orgPhone }}</div>

    <div>{{ $packageLabel }}</div>

    <div class="bottom bold">Net Weight: {{ number_format($netWeight, 2) }} g</div>
  </div>

  <!-- Middle -->
  <div class="label-section">
    <div>CBD: {{ $cbdOut }}, THC: {{ $thcOut }}</div>
    <div>
      Produced and Packaged<br>
      By: {{ $producerName }} - {{ $producerLic }}
    </div>
  </div>

  <!-- Right -->
  <div class="label-section">
    <div>For use only by adults 21 and older. Keep out of reach of children. Do not drive a motor vehicle while under the influence of marijuana.</div>
    <div class="bottom bold">BE CAUTIOUS</div>
  </div>
</div>
</body>
</html>
