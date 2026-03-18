@extends('layouts.app')

@section('content')
<?php $currency =  setting_by_key("currency"); ?>
<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Purchase Invoice </h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                     
                        <li class="active">
                            <strong>Purchase Invoice </strong>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-2">

                </div>
            </div>

   

<div class="wrapper wrapper-content animated fadeInRight">
     <a href="javascript:void(0);" class="btn btn-sm btn-primary" style="float:right; margin-bottom:20px" onclick="printDiv('printableArea')" > Print Invoice </a>

                    <div class="ibox-content p-xl" id="printableArea">
					<div class="row">
                                <div class="col-sm-6">
                                    <h5>Bill To</h5>
                                    <address>
                                        <strong>{{$billing_customer->name}}</strong><br>
                                        <strong title="Phone">Phone: </strong> {{$billing_customer->phone}}<br>
                                        <strong title="Email">Email: </strong> {{$billing_customer->email}}<br>
                                        <strong title="Email">Address: </strong> {{$purchase->bill_address}}<br>
                                        <strong title="Email">City,State,Zip: </strong> {{$purchase->bill_city}}, {{$purchase->bill_state}}, {{$purchase->bill_zip}}<br>
                                        <strong title="Email">Client #: </strong> {{$billing_customer->id}}<br>
                                        <strong title="Email">Country: </strong> {{$purchase->bill_country}}
                                    </address>
                                </div>

                                <div class="col-sm-6 text-right print-right">
                                    
                                     <h5>Ship To </h5>
                                     <address>
                                        <strong>{{$billing_customer->name}}</strong><br>
                                        <strong title="Phone">Phone: </strong> {{$billing_customer->phone}}<br>
                                        <strong title="Email">Email: </strong> {{$billing_customer->email}}<br>
                                         <strong title="Email">Address: </strong> {{$purchase->ship_address}}<br>
                                        <strong title="Email">City,State,Zip: </strong> {{$purchase->ship_city}}, {{$purchase->ship_state}}, {{$purchase->ship_zip}}<br>
                                        <strong title="Email">Country: </strong> {{$purchase->ship_country}}
                                    </address>

                                    <h4 class="text-navy"> Invoice #:  {{ sprintf('%04u', $purchase->id)  }}</h4>
                                    
                                    <p>
                                        <span><strong>Date:</strong> <?php echo date('d M, Y' , strtotime($purchase->invoice_date)); ?></span><br/>
                                    </p>
                                </div>
                            </div>
                            <?php
                                $subtotal = 0;
                            ?>
							
                            <div class="table-responsive m-t">

                                <table class="table invoice-table">
                                    <thead>
                                    <tr class="tableHead">
                                        <th>P.O. #</th>
                                        <th>Sales Rep. Name</th>
                                        <th>Shiping Date</th>
                                        <th>Terms</th>
                                        <th>Due Date </th>
                                       
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>{{ $purchase->po_number }}</td>
                                        <td></td>
                                        <td> <?php echo date('d M, Y' , strtotime($purchase->ship_date)); ?></td>
                                        <td> <?php echo $purchase->terms; ?></td>
                                        <td> <?php echo date('d M, Y' , strtotime($purchase->due_date)); ?></td>
                                    </tr>
								
                                    </tbody>
                                </table>

                                <hr>

                                <table class="table invoice-table">
                                    <thead >
                                    <tr class="tableHead">
                                        <th>Product Name</th>
                                        <th>Qty</th>
                                        <th>Count Per Unit</th>
                                        <th>Unit Price</th>
                                        <th>Total Amount </th>
                                       
                                    </tr>
                                    </thead>
                                    <tbody>
									@foreach($items as $k=>$item)
                                    <tr>
                                        <td>@if(!empty($item->product->name)) {{ $item->product->name }} @else {{ $item->product_name }} @endif</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->units }}</td>
                                        <td>{{$currency}}{{$item->unit_price}}</td>
                                        <td>{{$currency}}{{$item->unit_price * $item->quantity}}</td>
                                        <?php $subtotal += $item->unit_price * $item->quantity; ?>
                                    </tr>
									@endforeach
                                    
                                    </tbody>
                                </table>
                            </div><!-- /table-responsive -->

                            <table class="table invoice-total">
                                <tbody>
                                <tr>
                                    <td><strong>Sub Total :</strong></td>
                                    <td>{{$currency}}{{$subtotal}}</td>
                                </tr>
								
								<tr>
                                    <td><strong>TAX :</strong></td>
                                    <td>{{$currency}}{{$purchase->tax}}</td>
                                </tr>
								
								
                                <tr>
                                    <td><strong>DISCOUNT :</strong></td>
                                    <td>{{$currency}}{{$purchase->discount}}</td>
                                </tr>
                                <tr>
                                    <td><strong>TOTAL :</strong></td>
                                    <td>{{$currency}}{{$subtotal + $purchase->tax - $purchase->discount}}</td>
                                </tr>
                                </tbody>
                            </table>
							
                            <div class="well m-t"><strong>Notes</strong>
                               {{$purchase->note}}
                            </div>
                        </div>

                       
                        <br><br>
                </div>

     <style>
        @media print {
           .print-right { 
               padding-top:-100px !important;
           }
            .tableHead { 
                background-color: blue !important;
                color: red !important;
            }
        }  
        </style>

<script> 

function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}

</script>

@endsection