{{-- TOUCH + MOUSE FRIENDLY TOP BAR (single-level dropdown) --}}
@php
  $isAuthed = Auth::check();
  $role     = $isAuthed ? Auth::user()->role_id : null;
  // staff only (exclude retail customer/wholesale customer roles 5 & 6)
  $metrcPollingAllowed = $isAuthed && !in_array($role, [5,6]);
@endphp

<div class="row border-bottom">
  <nav class="navbar navbar-static-top" role="navigation" aria-label="Main navigation"
       style="margin-bottom:0; background-color:#000; min-height:90px; height:auto; padding-top:0;">
    {{-- Left: logo acts as dropdown toggle when authed; plain link otherwise --}}
    <div class="navbar-header" style="padding-left:15px; position:relative; height:90px; display:flex; align-items:flex-start;">
      @if($isAuthed)
        <div class="dropdown topnav-hover-dd" style="padding-top:5px;">
          <a class="navbar-brand dropdown-toggle"
             href="#"
             role="button"
             data-toggle="dropdown"
             aria-haspopup="true"
             aria-expanded="false"
             aria-label="Open menu"
             style="height:auto; padding-top:0;">
            <img alt="Logo" src="{{ asset('uploads/THC.png') }}"
                 style="max-height:90px; width:auto; object-fit:cover; object-position:top; clip-path: inset(0px 0 25px 0);" />
          </a>

          {{-- Single-level dropdown. No nested submenus. --}}
          <ul class="dropdown-menu touchwide" role="menu" aria-label="Primary menu">
            @if($role === 4) {{-- Budtender --}}
              <li role="presentation" class="dropdown-header">Point of Sale</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('sales/create') }}"><i class="fas fa-cash-register"></i> Cashier</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('sales') }}"><i class="fa fa-th-large"></i> Sales</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ route('admin.drawers.index') }}"><i class="fas fa-cash-register"></i> Cash Drawers</a></li>

              <li role="presentation" class="dropdown-header">Inventory</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('update_inventory') }}"><i class="fa fa-database"></i> Inventory</a></li>

              <li role="presentation" class="dropdown-header">Account</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('settings/profile') }}"><i class="fa fa-user"></i> Profile</a></li>
              <li class="divider"></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            @elseif($role === 2 || $role === 3) {{-- Org Admin / Manager --}}
              <li role="presentation" class="dropdown-header">Overview</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ route('retail.dashboard') }}"><i class="fa fa-th-large"></i> Dashboard</a></li>

              <li role="presentation" class="dropdown-header">Point of Sale</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('sales/create') }}"><i class="fas fa-cash-register"></i> Cashier</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('sales') }}"><i class="fa fa-th-large"></i> Sales</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ route('admin.drawers.index') }}"><i class="fas fa-cash-register"></i> Cash Drawers</a></li>

              <li role="presentation" class="dropdown-header">Inventory</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('update_inventory') }}"><i class="fa fa-database"></i> Inventory</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('quantity_alerts') }}"><i class="fa fa-exclamation-triangle"></i> Quantity Alerts</a></li>

              <li role="presentation" class="dropdown-header">People</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('customers') }}"><i class="fa fa-id-card"></i> Customers</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('users') }}"><i class="fa fa-users"></i> Users</a></li>

              <li role="presentation" class="dropdown-header">Website</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('settings/homepage') }}"><i class="fa fa-cog"></i> Homepage Settings</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('sliders') }}"><i class="fa fa-images"></i> Sliders</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('pages') }}"><i class="fa fa-file-alt"></i> Pages</a></li>

              <li role="presentation" class="dropdown-header">Account</li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('settings/profile') }}"><i class="fa fa-user"></i> Profile</a></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ route('organizations.edit') }}"><i class="fa fa-gear"></i> Settings</a></li>
              <li class="divider"></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            @else
              {{-- Fallback for other roles; keep minimal --}}
              <li><a role="menuitem" class="dropdown-item" href="{{ url('settings/profile') }}"><i class="fa fa-user"></i> Profile</a></li>
              <li class="divider"></li>
              <li><a role="menuitem" class="dropdown-item" href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            @endif
          </ul>
        </div>
      @else
        <a class="navbar-brand" href="{{ url('/') }}" style="height:auto; padding-top:0;">
          <img alt="Logo" src="{{ asset('uploads/THC.png') }}"
               style="max-height:90px; width:auto; object-fit:cover; object-position:top; clip-path: inset(0px 0 25px 0);" />
        </a>
      @endif
    </div>

    {{-- Right: METRC pill, welcome, bell, clock --}}
   <!-- <ul class="nav navbar-top-links navbar-right"
        style="height:90px; display:flex; flex-direction:column; align-items:flex-end; justify-content:flex-start; padding-right:15px; padding-top:5px;">
      <li style="margin-bottom:5px; display:flex; align-items:center; gap:10px;">
        @if($metrcPollingAllowed)
          <button id="metrcSyncPill"
                  class="btn btn-link"
                  type="button"
                  style="display:flex; align-items:center; color:white; gap:6px; cursor:pointer; padding:0;"
                  title="Tap/Click to refresh now"
                  aria-live="polite">
            <i class="fa fa-cloud" aria-hidden="true"></i>
            <span>METRC</span>
            <span id="metrcSyncState" class="label" style="background:#6c757d; padding:2px 6px; border-radius:3px;">Checking…</span>
            <span id="metrcSyncProg" class="text-muted" style="font-size:12px;"></span>
          </button>
        @endif -->

        <span style="color:#fff;">
          Welcome, {{ $isAuthed ? Auth::user()->name : 'Guest' }}
        </span>

        @if($isAuthed && !in_array($role, [5, 6]))
          <button type="button" class="btn" data-toggle="modal" data-target="#inventoryModal" style="padding:0; position:relative;">
            <i class="fa fa-bell" style="color:#fff; font-size:18px;"></i>
            @if(isset($lowInventoryItems) && $lowInventoryItems->count() > 0)
              <span class="badge badge-danger">{{ $lowInventoryItems->count() }}</span>
            @endif
          </button>
        @endif
      </li>

      @if($isAuthed && !in_array($role, [5, 6]))
        <li style="display:flex; align-items:center;">
          <span id="clockDisplay" style="color:#fff; margin-right:10px;"></span>
          <button id="clockButton" class="btn btn-primary" onclick="toggleClock()">Clock In</button>
        </li>
      @endif
    </ul>
  </nav>
</div>

{{-- Low Inventory Modal (unchanged) --}}
<div class="modal fade" id="inventoryModal" tabindex="-1" role="dialog" aria-labelledby="inventoryModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="inventoryModalLabel">Low Inventory Alerts</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        @if(isset($lowInventoryItems) && $lowInventoryItems->isNotEmpty())
          @foreach($lowInventoryItems as $item)
            <div class="alert alert-warning">
              <strong>{{ str_replace(':gls:', 'Green Leaf Special', $item->name) }}</strong> is low.
              <span class="text-muted">Current: {{ $item->storeQty }} / Min: {{ $item->min_qty }}</span>
            </div>
          @endforeach
        @else
          <p>No low inventory alerts!</p>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
  .navbar-static-top { background-color:#000; padding:0; }
  .navbar-header { height:90px; display:flex; align-items:flex-start; }
  .navbar-brand { padding:0; height:auto; display:flex; align-items:center; }
  .navbar-brand img { max-height:120px; width:auto; object-fit:cover; object-position:top; clip-path: inset(10px 0 10px 0); }

  /* Touch-friendly dropdown sizing */
  .dropdown-menu.touchwide { min-width: 320px; padding:8px 0; }
  .dropdown-menu.touchwide .dropdown-header { padding:8px 16px; font-size:12px; text-transform:uppercase; color:#6c757d; }
  .dropdown-menu.touchwide > li > a.dropdown-item {
    padding: 12px 16px;
    font-size: 15px;
    line-height: 1.2;
  }
  .dropdown-menu.touchwide > li > a.dropdown-item i { width: 18px; text-align:center; margin-right:8px; }

  /* Make toggle obvious on dark bg */
  .dropdown-toggle::after { color:#fff; }

  /* Hover-to-open only when a mouse is present; JS toggles .dd-hover-enabled */
  .dd-hover-enabled .topnav-hover-dd:hover { /* no extra styles needed; JS will open */ }

  /* Buttons */
  .btn-primary { background-color:rgb(10,143,54); border-color:rgb(10,172,51); }
  .btn-primary:hover { background-color:rgb(5,107,22); border-color:rgb(5,56,7); }
  .badge-danger { position:absolute; top:-5px; right:-5px; }
</style>

{{-- Dual-mode dropdown behavior (hover for mouse, click/tap for touch) --}}
<script>
(function() {
  // Bootstrap 3 toggles .open on li.dropdown
  var ddWrap = document.querySelector('.topnav-hover-dd');
  if(!ddWrap) return;

  // Enable hover when a fine pointer exists
  var hasHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  if (hasHover) document.documentElement.classList.add('dd-hover-enabled');

  var hoverTimer = null;

  function openDD() {
    if (!ddWrap.classList.contains('open')) {
      ddWrap.classList.add('open');
      ddWrap.querySelector('.dropdown-toggle')?.setAttribute('aria-expanded','true');
    }
  }
  function closeDD() {
    if (ddWrap.classList.contains('open')) {
      ddWrap.classList.remove('open');
      ddWrap.querySelector('.dropdown-toggle')?.setAttribute('aria-expanded','false');
    }
  }

  // Hover open/close for mouse users
  if (hasHover) {
    ddWrap.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimer);
      openDD();
    });
    ddWrap.addEventListener('mouseleave', function() {
      hoverTimer = setTimeout(closeDD, 180);
    });
  }

  // Always allow click/tap toggle
  var toggler = ddWrap.querySelector('.dropdown-toggle');
  if (toggler) {
    toggler.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      ddWrap.classList.toggle('open');
      toggler.setAttribute('aria-expanded', ddWrap.classList.contains('open') ? 'true' : 'false');
    });
  }

  // Close when clicking anywhere else
  document.addEventListener('click', function(e) {
    if (!ddWrap.contains(e.target)) closeDD();
  });

  // Esc to close
  document.addEventListener('keyup', function(e) {
    if (e.key === 'Escape') closeDD();
  });

  // Keep menu open when tapping inside (Bootstrap sometimes closes on any click)
  var menu = ddWrap.querySelector('.dropdown-menu');
  if (menu) {
    menu.addEventListener('click', function(e) {
      // clicks on links should close (default) – if you want to keep open, uncomment next line
      // e.stopPropagation();
    });
  }
})();
</script>

{{-- METRC pill polling (kept from your original, touch-safe) --}}
@if($metrcPollingAllowed)
<script>
(function($){
  var pollTimer = null, relTimer = null, lastSeenISO = null;
  function timeAgo(dt){
    var s = Math.floor((Date.now()-dt.getTime())/1000);
    if (s < 60) return s + 's ago';
    var m = Math.floor(s/60); if (m < 60) return m + 'm ago';
    var h = Math.floor(m/60); if (h < 24) return h + 'h ' + (m%60) + 'm ago';
    var d = Math.floor(h/24); return d + 'd ' + (h%24) + 'h ago';
  }
  function formatDateTime(dt){
    try { return dt.toLocaleString(undefined,{weekday:'short',month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}); }
    catch(e){ return dt.toISOString(); }
  }
  function schedule(ms){ if(pollTimer) clearTimeout(pollTimer); pollTimer = setTimeout(poll, ms); }
  function stopRel(){ if(relTimer) { clearInterval(relTimer); relTimer=null; } }

  function renderRunning(){
    stopRel(); lastSeenISO = null;
    var $state = $('#metrcSyncState'), $prog = $('#metrcSyncProg');
    $state.html('<i class="fa fa-spinner fa-spin"></i> sync in progress').css('background','#17a2b8');
    $prog.text('');
  }
  function renderIdle(iso){
    stopRel();
    var $state = $('#metrcSyncState'), $prog = $('#metrcSyncProg');
    if(iso && !isNaN(Date.parse(iso))){
      lastSeenISO = iso;
      var dt = new Date(iso);
      $state.text('Last sync: ' + formatDateTime(dt) + ' (' + timeAgo(dt) + ')').css('background','#c2d5e6');
      $prog.text('');
      relTimer = setInterval(function(){
        if(!lastSeenISO) return;
        var dt2 = new Date(lastSeenISO);
        $state.text('Last sync: ' + formatDateTime(dt2) + ' (' + timeAgo(dt2) + ')');
      }, 30000);
    } else {
      lastSeenISO = null;
      $state.text('Last sync: unknown').css('background','#c7dbec');
      $prog.text('');
    }
  }
  function handleStatus(d){
    if(d && d.running){ renderRunning(); schedule(1200); }
    else { renderIdle(d && d.last_sync_at || null); schedule(60000); }
  }


})(window.jQuery || {});
</script>
@else
<script>
  (function(){ var pill=document.getElementById('metrcSyncPill'); if(pill) pill.remove(); })();
</script>
@endif
