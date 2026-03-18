@extends('layouts.app')

@section('content')

<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Min Quantity Alert</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">@lang('common.home')</a>
            </li>
            <li class="active">
                <strong>Update Min Quantity Alert</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">
        <!-- Optional section -->
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Update Min Quantity Alert <small></small></h5>
                </div>
                <div class="ibox-content">
                    <form action="{{ url('min_quantity_update') }}" method="POST">
                        {{ csrf_field() }}

                        <input type="text" id="Search" class="form-control" onkeyup="myFunction()" placeholder="Search for names.." autofocus>
                        <table class="table" id="myTable">
                            <thead>
                                <tr class="header">
                                    <th>#</th>
                                    <th>Inventory Name</th>
                                    <th>Store Quantity Alert</th>
                                    <th>StoreRoom Quantity Alert</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventories as $key => $inventory)
                                    <tr>
                                        <td>
                                            {{ $key + 1 }}
                                            <input type="hidden" name="inventory_id[]" value="{{ $inventory->id }}">
                                        </td>
                                        <td>{{ $inventory->name }}</td>
                                        <td>
                                            <input type="number" name="min_qty[]" value="{{ $inventory->min_qty }}" class="form-control">
                                        </td>
                                        <td>
                                            <input type="number" name="store_min[]" value="{{ $inventory->store_min }}" class="form-control">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">@lang('common.no_record_found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <input type="submit" value="Save" class="btn btn-primary">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="{{ url('assets/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">
<script src="{{ url('assets/js/plugins/sweetalert/sweetalert.min.js') }}"></script>

<script>
    // Search filter function to hide rows based on the inventory name
    function myFunction() {
        var input = document.getElementById("Search");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("myTable");
        var tr = table.getElementsByTagName("tr");

        for (var i = 0; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName("td")[1]; // Inventory Name column
            if (td) {
                var txtValue = td.textContent || td.innerText;
                tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
    }

    // Optional: validate quantity inputs if you add a data-max attribute
    $("body").on("keyup change", ".changeqty", function() {
        if (Number($(this).val()) > Number($(this).attr("data-max"))) {
            swal("Oops", "In Storeroom there are only " + $(this).attr("data-max") + " items", "error");
            $(this).val("");
        }
    });
</script>

@endsection
