@extends('layouts.app')

@section('content')
<?php /* ALTER TABLE `booking_types` CHANGE `type` `type` ENUM('fixed','hourly','daily','weekly','monthly') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;  */ ?>


<link href="assets/css/plugins/dataTables/datatables.min.css" rel="stylesheet">

 <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Booking Types </h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                     
                        <li class="active">
                            <strong>Booking Types</strong>
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
                        <h5>Booking Types  </h5>
                        <div class="ibox-tools">
						<a href="javascript:void(0);" id="AddNew" class="btn btn-primary btn-xs">@lang('common.add_new')</a>
						
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
							
                          
                           
                        </div>
                    </div>
                    <div class="ibox-content">

                        <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" >
					
					 <thead>
                        <tr>
                             <th>#</th>
                           
                            <th>@lang('common.name')</th>
                           
                             <th>Fixed Price</th>
                             <th>Fixed Hours</th>
                             <th>Type </th>
                             <th> Price</th>
                            
                             <th>Available </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($types as $key => $type)
                        <tr class="gradeX">
                             <td>{{ $types->firstItem() + $key }}</td>
                            <td>{{ $type->name }}</td>
                            
                            <td>@if($type->type == "fixed") ${{ $type->price }} @endif</td>
                            <td>@if($type->type == "fixed")  {{ $type->hours }} @endif</td>
                             <td> {{ ucfirst($type->type) }}</td>
                            <td>  ${{ $type->hourly_price }} </td>
                           
                            <td>{{ $type->available }}</td>
                           
                            <td>
                                
                                <a href="javascript:void(0);" data-id="<?php echo $type->id; ?>" class="btn btn-sm btn-info EditCategory"><i class="fa fa-pencil"></i></a>
									<a href="javascript:void(0);" data-id="<?php echo $type->id; ?>" class="btn btn-sm btn-danger delete"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    @empty
                       <tr> 
						  <td colspan="8">@lang('common.no_record_found') </td></tr>
                    @endforelse
						<tr> 
						  <td colspan="8">
						{!! $types->render() !!}
						</td>
								</tr>
                    </tbody>
            
                    </table>
                        </div>

                    </div>
                </div>
            </div>
            </div>
           
        </div>
       
      

        
<div class="modal fade" id="ExpiryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Add Booking Type</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form action="{{url('booking_types/save')}}" method="post" enctype="multipart/form-data">
				{{csrf_field()}}
				<div class="modal-body">
							<div class="form-group">
								<label for="">Name</label>
								<input type="text" id="name" required name="name"  class="form-control ">
                            </div>
                            
                          
                            
                            <div class="form-group">
								
                                <label for="">Type</label>
                                <select name="type" id="type" class="form-control hourly_type"> 
                                    <option value="fixed"> Fixed </option>
                                    <option value="hourly"> Hourly </option>
                                    <option value="daily"> Daily </option>
                                    <option value="weekly"> Weekly </option>
                                    <option value="monthly"> Monthly </option>
                                </select>
                                
                            </div>
                            <div id="FixedType">
                            <div class="form-group">
								<label for="">Fixed Rate</label>
								<input type="number" id="price" required name="price"  class="form-control ">
                            </div>

                             <div class="form-group">
								<label for="">Total Hours</label>
								<input type="number" id="hours" required name="hours"  class="form-control ">
                            </div>
                            </div>
                             <div id="HourlyType">
                                <div class="form-group">
                                    <label for="" id="rate_hour"> Hourly Rate</label>
                                    <input type="number" id="hourly_price" name="hourly_price"  class="form-control ">
                                </div>
                             </div>

                             <div id="HourlyType">
                                <div class="form-group">
                                    <label for=""> Available</label>
                                    <input type="number" min="1" id="available" required name="available"  class="form-control ">
                                </div>
                             </div>
	
							
				</div>
				<div class="modal-footer"><div style="color:red" id="dates_error"> </div> 
					<input type="hidden" name="id" id="id">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-sm btn-primary" id="AddUpdateReward">Add</button>
				</div>
			</form>
		</div>
	</div>

</div>

    

<script> 
//  $("#FixedType").hide();
  $("#HourlyType").hide();
$("body").on("change" , ".hourly_type", function() { 
    var type = $(this).val();
    // var text = $(this).text();
    $("#type").val(type);
    if(type == "fixed") { 
        $("#FixedType").show();
        $("#HourlyType").hide();
        return false;
    }
    var rate_hour = type.charAt(0).toUpperCase() + type.substr(1);
    $("#rate_hour").text(rate_hour + " Rate");
    $("#FixedType").hide();
    $("#HourlyType").show();



});

function readURL(input) {

  if (input.files && input.files[0]) {
    var reader = new FileReader();

    reader.onload = function(e) {
      $('#preview').show();
      $('#preview img').attr('src', e.target.result);
    }

    reader.readAsDataURL(input.files[0]);
  }
}

$("#file").change(function() {
  readURL(this);
});
			$("body").on("click" , "#AddNew", function() { 
				$("#id").val('');
				$("#name,#description,#price").val('');
			
				   $("#AddUpdateReward").html("Add");
				$("#ExpiryModal").modal("show");
			});
			
			$("body").on("click" , ".EditCategory", function() { 
				var id = $(this).attr("data-id");
				$("#id").val(id);
				$("#AddUpdateReward").html("Update");
				$("#exampleModalLabel").html("Update");
				$("#dates_error").html("");
				var form_data = {
					id:id
				};
				$.ajax({
					url: "<?php echo url('booking_types/get'); ?>", // Url to which the request is send
					type: "POST",
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},           
					data: form_data,
					success: function(res) 
					{
						var obj = JSON.parse(res);
						$("#name").val(obj['name']);
						$("#description").val(obj['description']);
						$("#price").val(obj['price']);
						$("#hours").val(obj['hours']);
						$("#type").val(obj['type']);
						$("#available").val(obj['available']);
						$("#hourly_price").val(obj['hourly_price']);
						// if( == "fixed") { 
                        //     $("#fixed").trigger("click");
                        // }
                        // if(obj['type'] == "hourly") { 
                        //     $("#hourly").trigger("click");
                        // }
						$("#ExpiryModal").modal("show");
						
						  
					}
						});
				});
							
				
				
			
			
    $( "body" ).on( "click", ".delete", function () {
        var task_id = $( this ).attr( "data-id" );
        var form_data = {
            id: task_id
        };

        // swal( {
        //     title: "Are you sure",
        //     html: 'you want to delete?',
        //     type: 'info',
        //     showCancelButton: true,
        //     confirmButtonColor: '#26A669',
        //     cancelButtonColor: '#C82333',
        //     confirmButtonText: "Yes"
        // } ).then( ( result ) => {
        //     if ( result.value == true ) {
                $.ajax( {
                    headers: {
                        'X-CSRF-TOKEN': $( 'meta[name="csrf-token"]' ).attr( 'content' )
                    },
                    url: '<?php echo url("booking_types/delete"); ?>',
                    type: "POST",

                    data: form_data,
                    success: function ( res ) {
                        location.reload();
                    }
                } );



        //         return true;
        //     }
        // } )


    } );




</script>


    

@endsection
