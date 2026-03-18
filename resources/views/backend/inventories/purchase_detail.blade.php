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
                    <div class="ibox-content p-xl">
					<div class="row">
                                <div class="col-sm-6">
                                    <h5>From:</h5>
                                    <address>
                                        <strong>{{$supplier->company_name}}</strong><br>
                                        {{$supplier->address}}<br>
                                        <abbr title="Phone">P:</abbr> {{$supplier->phone}}<br>
                                        <abbr title="Email">E:</abbr> {{$supplier->email}}
                                    </address>
                                </div>

                                <div class="col-sm-6 text-right">
                                    <h4>Invoice No.</h4>
                                    <h4 class="text-navy"> {{ sprintf('%04u', $purchase->id)  }}</h4>
                                    
                                    <p>
                                        <span><strong>Date:</strong> <?php echo date('d M, Y' , strtotime($purchase->purchase_date)); ?></span><br/>
                                    </p>
                                </div>
                            </div>
                            <?php
                                $subtotal = 0;
                            ?>
							
                            <div class="table-responsive m-t">
                                <table class="table invoice-table">
                                    <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Qty</th>
                                        <th>Count Per Unit</th>
                                        <th>Total Unit Count</th>
                                        <th>Unit Price</th>
                                        <th>Total Amount </th>
                                       
                                    </tr>
                                    </thead>
                                    <tbody>
									@foreach($items as $k=>$item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->units }}</td>
                                        <td>{{ $item->units * $item->quantity }}</td>
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
                </div>
@endsection