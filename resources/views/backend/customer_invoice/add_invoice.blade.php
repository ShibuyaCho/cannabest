@extends('layouts.app')

@section('content')

<link href="{{url('assets/css/plugins/chosen/chosen.css')}}" rel="stylesheet">
<link href="{{url('assets/css/plugins/datapicker/datepicker3.css')}}" rel="stylesheet">

 <script src="{{url('assets/js/plugins/chosen/chosen.jquery.js')}}"></script>
    <script src="{{url('assets/js/plugins/datapicker/bootstrap-datepicker.js')}}"></script>



<style> 
    .form-group { 
            padding-left: 30px;
     }
</style> 

<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Add Customer Invoice</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="#">@lang('common.home')</a>
                        </li>
                        
                        <li class="active">
                            <strong>Add Customer Invoice</strong>
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
                            <h5>Add Customer Invoice </h5>
                            <div class="ibox-tools">
                                <a class="collapse-link">
                                    <i class="fa fa-chevron-up"></i>
                                </a>
                                
                                
                            </div>
                        </div>
                        <div class="ibox-content">
						<form action="{{ url('save_customer_invoice') }}" class="form-horizontal" method="POST" enctype='multipart/form-data'>
                        {{ csrf_field() }}

						<div class="col-sm-4 col-lg-4 form-group">
							<label>Invoice Date </label>
							<input type="text" class="form-control datepicker" id="name" name="invoice_date" placeholder="Purchase Date" autocomplete="off" data-field_type="input">
						</div>
                        <div class="col-sm-4 col-lg-4 form-group">
							<label>PO #</label>
							<input type="text" class="form-control" id="name" name="po_number" placeholder="PO #" autocomplete="off" data-field_type="input">
						</div>


						<div class="col-sm-4 col-lg-4 form-group">
							<label>Ship Date</label>
							<input type="text" class="form-control datepicker" id="name" name="ship_date" placeholder="Ship Date" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-4 col-lg-4 form-group">
							<label>Due Date</label>
							<input type="text" class="form-control datepicker" id="name" name="due_date" placeholder="Due Date" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-8 col-lg-8 form-group">
							<label>Terms </label>
							<input type="text" class="form-control" id="name" name="terms" placeholder="Terms" autocomplete="off" data-field_type="input">
						</div>

						<div class="col-sm-12 col-lg-12 form-group"><h2> <strong>Bill To</strong></h2></div>
						<div class="col-sm-4 col-lg-4 form-group">
							<label>Customer</label>
							 <select class="form-control" id="bill_customer" name="bill_customer"> 
								  <option value="">Select Customer</option>
											@foreach($customers as $cat)
											<option data-address="{{$cat->address}}" data-city="{{$cat->city}}" data-state="{{$cat->state}}" data-zip="{{$cat->zip}}" value="{{$cat->id}}">  {{$cat->name}} </option>
											@endforeach
							</select>
						</div>

						<script> 
							$("body").on("change", "#bill_customer" , function() { 
								
								$("#bill_address").val($(this).find(':selected').data('address'));
								$("#bill_city").val($(this).find(':selected').data('city'));
								$("#bill_state").val($(this).find(':selected').data('state'));
								$("#bill_zip").val($(this).find(':selected').data('zip'));
							});
						</script>

						<div class="col-sm-12 col-lg-4 form-group">
							<label>Address </label>
							<input type="text" class="form-control" id="bill_address" name="bill_address" placeholder="Address" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>City </label>
							<input type="text" class="form-control" id="bill_city" name="bill_city" placeholder="city" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>State </label>
							<input type="text" class="form-control" id="bill_state" name="bill_state" placeholder="State" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>Zip </label>
							<input type="text" class="form-control" id="bill_zip" name="bill_zip" placeholder="Zip" autocomplete="off" data-field_type="input">
						</div>

						<div class="col-sm-12 col-lg-4 form-group">
							<label>Country </label>
							<input type="text" class="form-control" id="bill_country" name="bill_country" placeholder="Country" autocomplete="off" data-field_type="input">
						</div>

						<div class="col-sm-12 col-lg-12 form-group"><h2> <strong>Ship To</strong> </h2></div>
						<div class="col-sm-4 col-lg-4 form-group">
							<label>Customer</label>
							 <select class="form-control" id="ship_customer" name="ship_customer"> 
											 <option value="">Select Customer</option>
											@foreach($customers as $cat)
											<option data-address="{{$cat->address}}" data-city="{{$cat->city}}" data-state="{{$cat->state}}" data-zip="{{$cat->zip}}" value="{{$cat->id}}">  {{$cat->name}} </option>
											@endforeach
									</select>
						</div>

						<script> 
							$("body").on("change", "#ship_customer" , function() { 
								$("#ship_address").val($(this).find(':selected').data('address'));
								$("#ship_city").val($(this).find(':selected').data('city'));
								$("#ship_state").val($(this).find(':selected').data('state'));
								$("#ship_zip").val($(this).find(':selected').data('zip'));
							});
						</script>

						<div class="col-sm-12 col-lg-4 form-group">
							<label>Address </label>
							<input type="text" class="form-control" id="ship_address" name="ship_address" placeholder="Address" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>City </label>
							<input type="text" class="form-control" id="ship_city" name="ship_city" placeholder="City" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>State </label>
							<input type="text" class="form-control" id="ship_state" name="ship_state" placeholder="State" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>Zip </label>
							<input type="text" class="form-control" id="ship_zip" name="ship_zip" placeholder="Zip" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-12 col-lg-4 form-group">
							<label>Country </label>
							<input type="text" class="form-control" id="ship_country" name="ship_country" placeholder="Country" autocomplete="off" data-field_type="input">
						</div>

						
						
						
						<div class="col-sm-12 col-lg-12 form-group">
							<label>Product</label>
							 <select class="form-control chosen-select" id="product_id" name="product_id"> 
								 <option value="">Select Product</option>
								 <option value="0">Custom Type Product</option>
											@foreach($products as $cat)
											<option value="{{$cat->id}}">  {{$cat->name}} </option>
											@endforeach
									</select>
						</div>
						

                         <table class="table">
                    <thead>

                        
                        <tr>
                            <th width="20%">Product Name</th>
                            <th  width="15%">Quantity</th>
                            <th  width="15%">Unit Price</th>
                            <th  width="15%">Count Per Unit</th>
							<th  width="20%">Total Cost</th>
                        
                        </tr>
                    </thead>
                    
                         <tbody id="new_product">
                                
                         </tbody>
                            
                   

                    <thead id="sale_footer" >
								<tr>
									<th> </th>
									<th> </th>
									<th> </th>
									<th> </th>
									<th> </th>
								</tr>
								
								<tr>
									<th colspan="4"> Tax </th>
									<th> <input type="text" value="0" min=0 name="tax" class="form-control" id="tax" style="" /> </th>
								</tr>
								
								<tr>
									
									<th colspan="4"> Total Discount </th>
									<th> <input type="text" value="0" min=0 name="discount" class="form-control" id="discount" style="" /> </th>
								</tr>
								
								
								<tr>
									
									<th colspan="4"> Total Price </th>
									<th> <input type="text" readonly name="final_price" class="form-control" id="final_price" style="" /> </th>
								</tr>
                            </thead>
                            
				</table>
				
					<div class="col-sm-12 col-lg-12 form-group">
							<label>Notes</label>
							 <textarea name="note" class="form-control"></textarea>
						</div>
						
<input type="hidden" id="p_ids">

	<input type="hidden" value="1" id="counter" id="counter">
                        
            
					
                                <div class="form-group">
                                    <div class="col-sm-4">
                                        
										 <a class="btn btn-white" href="{{ url('products') }}">@lang('common.cancel')</a>
                                        <button class="btn btn-primary" type="submit">@lang('common.save')</button>
                                    </div>
                                </div>
								
								
                            </form>
                        </div>
                    </div>
                </div>
                </div>
				</div>
				


<script type="text/javascript">
    $(document).ready(function(){
		var p_ids = [];
		$("#sale_footer").hide();
		$("body").on("change" , "#product_id" , function() {
			$("#sale_footer").show();
			var product_id = $('#product_id').val();
			 var counter = $('#counter').val();
			 var counter_product = "c" + $('#counter').val();
			
			var form_data = {
				product_id : product_id,
				counter : counter_product
			};

			$("#counter").val(Number(counter) + 1);

			if(product_id == "" || product_id == 0) { 
				product_id = counter_product;
			}
			
			var total_qty = parseInt($("#qty_"+product_id).val());
			var price = parseInt($("#price_"+product_id).val());
			var units = parseInt($("#units_"+product_id).val());

			
			if(total_qty > 0) { 
				var quantity = total_qty + 1;
				 $("#qty_"+product_id).val(quantity);
				 $("#toatlprice_"+product_id).val(quantity * price);
				 $("#product_id").val('');
				 get_total_price();
			} else { 
				if(product_id == "" || product_id == 0) { 
				p_ids.push(counter_product);
				$("#p_ids").val(p_ids.join());
				} else { 
						p_ids.push(product_id);
						$("#p_ids").val(p_ids.join());
				}

				console.log(p_ids);

			
					$.ajax({
						type: 'POST',
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						url: '<?php echo url("get_customer_product_ajax"); ?>',
						data:form_data,
                    	success: function (msg) {
					   $("#new_product").append(msg);
					   $("#product_id").val('').trigger("chosen:updated");

					   get_total_price();
					}
				});
			}
			
			
			
		});
		
		$("body").on("change" , ".change_qty , .change_price , .units" , function() {
			var product_id = $(this).attr('data-id');
			var total_qty = parseInt($("#qty_"+product_id).val());
			var price = Number($("#price_"+product_id).val());
			var units = Number($("#units_"+product_id).val());
			
			$("#toatlprice_"+product_id).val( price * total_qty);
			get_total_price();
		});
		$("body").on("keyup" , ".change_qty , .change_price, .units" , function() {
			var product_id = $(this).attr('data-id');
			var total_qty = parseInt($("#qty_"+product_id).val());
			var price = Number($("#price_"+product_id).val());
			var units = Number($("#units_"+product_id).val());
		
			$("#toatlprice_"+product_id).val(price * total_qty);
			get_total_price();
		});
		
		function get_total_price() { 
			   var pro_ids = $("#p_ids").val();
				var final_amount = 0;
				  pro_ids = pro_ids.split(',');
				$.each(pro_ids, function( index, value ) {
					var t_price =  parseFloat($("#toatlprice_"+value).val());
					final_amount = parseFloat(final_amount + t_price);
				});

				// var counter = $("#counter").val();
				 
				// for(var i=1; i < counter; i++) {  console.log(i);
				// 	var t_price =  parseFloat($("#toatlprice_"+i).val());
				//  	final_amount = parseFloat(final_amount + t_price);
				// }
				
				var tax =  parseFloat($("#tax").val());
				var discount =  parseFloat($("#discount").val());
				final_amount = parseFloat(Number(final_amount) + tax - discount);
				if(final_amount == "NaN") { 
					final_amount = 0;
				}
				$("#final_price").val(final_amount);
		}
		
		$("body").on("change" , "#tax , #discount" , function() {
			get_total_price();
		});
		$("#check_n").hide();
		$("body").on("change" , "#payment_method" , function() {
			var payment_value = $("#payment_method").val();
			if(payment_value == "cheque") { 
				$("#check_n").show();
			} else { 
				$("#check_n").hide();
			}
		});
		
	});



	var config = {
                '.chosen-select'           : {},
                '.chosen-select-deselect'  : {allow_single_deselect:true},
                '.chosen-select-no-single' : {disable_search_threshold:10},
                '.chosen-select-no-results': {no_results_text:'Oops, nothing found!'},
                '.chosen-select-width'     : {width:"95%"}
                }
            for (var selector in config) {
                $(selector).chosen(config[selector]);
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
