<!DOCTYPE html>
<html>
<head>
  <title>Flower Label</title>
  <style>
    body { margin:0; padding:0; font-family:sans-serif; width:4in; height:2in; }
    .label { display:flex; width:100%; height:100%; }
    .col { width:50%; padding:0.05in; box-sizing:border-box; }
    .col + .col { border-left:1px solid #000; }
    h1 { font-size:12pt; margin:0 0 0.03in; font-weight:bold; }
    p { margin:0 0 0.03in; font-size:8pt; line-height:1.1; }
    .bold { font-weight:bold; }
  </style>
</head>
<body onload="window.print()">

@php use Carbon\Carbon; @endphp

<div class="label">
  <div class="col">
    <h1>{{ $inventory->name }}</h1>
    <p class="bold">
      {{ Carbon::parse($sale->created_at)->format('m/d/Y, h:i A') }}
    </p>
    <p>{{ $organization->name }}</p>
    <p>{{ $organization->license_number ?? 'OLCC #' }}</p>
    <p>Sale ID: {{ $sale->id }}</p>
    <p>Pkg: {{ $inventory->Label }}</p>
    <p>Batch #: {{ $batchNumber }}</p>
    <p>Harvested: {{ $harvested }}</p>
    <p>Net Wt: {{ number_format($item->quantity, 2) }} g</p>
  </div>
  <div class="col">
    <p>CBD: {{ number_format($cbdPct, 2) }}%</p>
    <p>THC: {{ number_format($thcPct, 2) }}%</p>
    <p>Tested by: {{ $testedByName }}</p>
    <p>Facility Lic: {{ $testedByLicense }}</p>
    <p>Test Date: {{ $testDate }}</p>
    <p style="font-size:6pt;">
      For use only by adults 21+; keep out of reach of children.
    </p>
    <p style="font-size:5pt;">
      Not FDA approved. Store in cool, dry place away from sunlight.
    </p>
    <p class="bold" style="font-size:7pt;">BE CAUTIOUS</p>
  </div>
</div>

</body>
</html>
