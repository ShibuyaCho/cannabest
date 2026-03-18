@extends('layouts.app')

@section('content')

<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Inventories</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index.html">@lang('common.home')</a>
                        </li>
                        
                        <li class="active">
                            <strong>Update Inventory </strong>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-2">

                </div>
            </div>
<div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Update Inventory <small></small></h5>
                        
                    </div>
                    <div class="ibox-content">
                    <form action="{{url('adjust_werehouse_quantity')}}" method="POST">
                        {{csrf_field()}}

                <input type="text" id="Search" class="form-control"  onkeyup="myFunction()" placeholder="Search for names.." autofocus>
                <table class="table" id="myTable">
                    <thead>
                        <tr class="header">
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Storeroom Qty</th>
                            <th>Store Qty</th>
                           <?php /*  <th>Supplier</th> */ ?>
                            <th>Add/Subtract </th>
                            <th width="12%">Adjust Quantity</th>
                            <th width="30%">Comments</th>
							
							
                        </tr>
                    </thead>
                    <tbody>
                    @if (!empty($products))
                        @forelse ($products as $key => $product)
                            <tr>
                               <td>{{ $key + 1 }}</td>
                               <td>{{ $product->name }}</td>
                               <td>@if($product->warehouse >= 0) {{ $product->warehouse }} @else 0 @endif</td>
                               <td>@if($product->quantity >= 0) {{ $product->quantity }} @else 0 @endif</td>
                               <input type="hidden" name="product_id[]" value="<?php echo $product->id; ?>" class="form-control">
                               <?php /* <td>
                                   <select name="supplier_id[]" class="form-control">
                                        @foreach($suppliers as $supplier)
                                         <option value="{{$supplier->id}}">{{$supplier->name}}</option>
                                         @endforeach
                                    </select>
                                </td> */ ?>
                                <td><select name="type[]" class="form-control"><option value="add">Add</option><option value="sub">Subtract </option></select></td>
                               
                               <td><input type="number" data-max="{{$product->quantity}}" name="quantity[]"  value="0" class="form-control changeqty"></td>
                               <td><input type="text"  name="comments[]"  value="" class="form-control"></td>
                            </tr>
                        @empty
                           <tr> 
						  <td colspan="5">
								 @lang('common.no_record_found')
									
                                </td>
								</tr>
                        @endforelse
                    @endif
                    </tbody>
                </table>
                
                <input type="submit" value="Save" class="btn btn-primary ">
            </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link href="{{url('assets/css/plugins/sweetalert/sweetalert.css')}}" rel="stylesheet">
<script src="{{url('assets/js/plugins/sweetalert/sweetalert.min.js')}}"></script>

    <script>

// $("body").on("keyup", ".changeqty", function() {
//     if( Number($(this).val()) > Number($(this).attr("data-max"))) { 
//         swal("Oops" , "In Storeroom there is only " + $(this).attr("data-max") + " items" , "error");
//         $(this).val("");
//     }
// });

// $("body").on("change", ".changeqty", function() {
//     if( Number($(this).val()) > Number($(this).attr("data-max"))) { 
//         swal("Oops" , "In Storeroom there is only " + $(this).attr("data-max") + " items" , "error");
//         $(this).val("");
//     }
// });

function myFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("Search");
  filter = input.value.toUpperCase();
  table = document.getElementById("myTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[1];
    
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}
</script>

@endsection
