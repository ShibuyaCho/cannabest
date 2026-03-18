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
                    <h2>Add Purchase Items</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="#">@lang('common.home')</a>
                        </li>
                        
                        <li class="active">
                            <strong>Add Purchase Items</strong>
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
                            <h5>@lang('common.add_new')</h5>
                            <div class="ibox-tools">
                                <a class="collapse-link">
                                    <i class="fa fa-chevron-up"></i>
                                </a>
                                
                                
                            </div>
                        </div>
                        <div class="ibox-content">
						<form action="{{ url('save_purchase_inventory') }}" class="form-horizontal" method="POST" enctype='multipart/form-data'>
                        {{ csrf_field() }}

                        <div class="col-sm-4 col-lg-4 form-group">
							<label>Purchase Date </label>
							<input type="text" class="form-control datepicker" id="name" name="purcahse_date" placeholder="Purchase Date" autocomplete="off" data-field_type="input">
						</div>
                        <div class="col-sm-4 col-lg-4 form-group">
							<label>Bill No</label>
							<input type="text" class="form-control" id="bill_no" name="bill_no" placeholder="Bill No" autocomplete="off" data-field_type="input">
						</div>
						<div class="col-sm-4 col-lg-4 form-group">
							<label>Supplier</label>
							 <select class="form-control" id="supplier_id" name="supplier_id"> 
											@foreach($suppliers as $cat)
											<option value="{{$cat->id}}">  {{$cat->company_name}} </option>
											@endforeach
									</select>
                        </div>
						<div class="col-sm-12 col-lg-12 form-group">
							<label>Product</label>
							 <select class="form-control chosen-select" id="product_id" name="product_id"> 
								 <option value="">Select Product</option>
											@foreach($products as $cat)
											<option value="{{$cat->id}}">  {{$cat->name}} </option>
											@endforeach
									</select>
						</div>
						

                         <table class="table">
                    <thead>

                        
                        <tr>
                            <th width="15%">Product Name</th>
                            <th  width="10%">Quantity</th>
                            <th  width="15%">Unit Cost</th>
                            <th  width="15%">Count Per Unit</th>
                            <th  width="15%">Total Unit Count</th>
							<th  width="15%">Sales Cost</th>
							<th  width="15%">Total Cost</th>
                        
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
									<th colspan=6> Tax </th>
									<th> <input type="text" value="0" min=0 name="tax" class="form-control" id="tax" style="" /> </th>
								</tr>
								
								<tr>
									
									<th colspan=6> Total Discount </th>
									<th> <input type="text" value="0" min=0 name="discount" class="form-control" id="discount" style="" /> </th>
								</tr>
								
								
								<tr>
									
									<th colspan=6> Total Price </th>
									<th> <input type="text" readonly name="final_price" class="form-control" id="final_price" style="" /> </th>
								</tr>
                            </thead>
                            
				</table>
				
					<div class="col-sm-12 col-lg-12 form-group">
							<label>Notes</label>
							 <textarea name="note" class="form-control"></textarea>
						</div>
						
						<input type="hidden" id="p_ids">
                        
            
					
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
			product_id = $('#product_id').val();
			var form_data = {
				product_id : product_id
			};
			
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
				p_ids.push(product_id);
				$("#p_ids").val(p_ids.join());
					$.ajax({
						type: 'POST',
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						url: '<?php echo url("add_product_ajax"); ?>',
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
			console.log(price); 
			$("#toatlprice_"+product_id).val( price * total_qty);
			$("#total_units_"+product_id).text(units * total_qty);
			get_total_price();
		});
		$("body").on("keyup" , ".change_qty , .change_price, .units" , function() {
			var product_id = $(this).attr('data-id');
			var total_qty = parseInt($("#qty_"+product_id).val());
			var price = Number($("#price_"+product_id).val());
			var units = Number($("#units_"+product_id).val());
			console.log(price); 
			$("#toatlprice_"+product_id).val(price * total_qty);
			$("#total_units_"+product_id).text(units * total_qty);
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
