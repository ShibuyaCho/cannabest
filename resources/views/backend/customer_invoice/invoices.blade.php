@extends('layouts.app')

@section('content')


<link href="{{url('assets/css/plugins/datapicker/datepicker3.css')}}" rel="stylesheet">
<script src="{{url('assets/js/plugins/datapicker/bootstrap-datepicker.js')}}"></script>

<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Customer Invoices</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="#">@lang('common.home')</a>
                        </li>
                        
                        <li class="active">
                            <strong>Customer Invoices</strong>
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
                        <h5> Customer Invoices <small></small></h5>
                        
                    </div>
                    <div class="ibox-content">
                         <form action="" method="GET">
            
                        <div class="col-sm-6 col-lg-3 form-group">
							
							<input type="text" class="form-control datepicker"  value="{{$start_date}}"  name="start_date" name="purcahse_date" placeholder="Start Date" autocomplete="off" data-field_type="input">
						</div>
                        <div class="col-sm-6 col-lg-3 form-group">
							
							<input type="text" class="form-control datepicker" value="{{$end_date}}"  name="end_date" name="purcahse_date" placeholder="End Date" autocomplete="off" data-field_type="input">
						</div>
                        <div class="col-sm-6 col-lg-3 form-group">
							
							<input type="text" class="form-control" id="name"  value="{{$q}}" name="q" placeholder="Keywords" autocomplete="off" data-field_type="input">
						</div>
                        <div class="col-sm-6 col-lg-3 form-group">
							
							<input type="submit" class="btn btn-primary"  value="Search">
						</div>
						
                    </form>
              

                <table class="table" id="myTable">
                    <thead>
                        <tr class="header">
                            <th>Po #</th>
                            <th>Bill to Customer </th>
                            <th>Shipping Customer </th>
                            <th>Invoice Date</th>
                            <th>Total Amount </th>
							
                            <th>Options</th>
							
                        </tr>
                    </thead>
                    <tbody>
                    @if (!empty($items))
                        @forelse ($items as $key => $inv)
                            <?php  $billing_customer = $inv->billing_customer; ?>
                            <?php  $shipping_customer = $inv->shipping_customer; ?>
                            <tr>
                               <td>{{ $inv->po_number }}</td>
                               <td>@if(!empty($shipping_customer)) {{ $shipping_customer->name }} @endif</td>
                               <td>@if(!empty($billing_customer)) {{ $billing_customer->name }} @endif</td>
                               <td>{{ date("d M Y", strtotime($inv->invoice_date)) }}</td>
                               <td>${{ $inv->total_amount }}</td>
							<td>
                                <a href="{{url('invoice_detail/' . $inv->id)}}"><i class="fa fa-eye"></i></a>
                                <a href="{{url('edit_customer_invoice/' . $inv->id)}}"><i class="fa fa-edit"></i></a>
                            </td>
                               
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
                {!! $items->render() !!}
          
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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



$('.datepicker').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: "dd-mm-yyyy"
            });


</script>

@endsection
