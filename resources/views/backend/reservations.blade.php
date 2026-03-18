@extends('layouts.app')

@section('content')
<?php $hourly_reservation =  setting_by_key("hourly_reservation"); ?>
<link href="assets/css/plugins/dataTables/datatables.min.css" rel="stylesheet">
<link href="{{url('assets/css/plugins/sweetalert/sweetalert.css')}}" rel="stylesheet">
<script src="{{url('assets/js/plugins/sweetalert/sweetalert.min.js')}}"></script>

<link href="{{url('assets/css/plugins/datapicker/datepicker3.css')}}" rel="stylesheet">
    <script src="{{url('assets/js/plugins/datapicker/bootstrap-datepicker.js')}}"></script>

 <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Reservations</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                     
                        <li class="active">
                            <strong>Reservations</strong>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-2">

                </div>
            </div>
			
			
			<div class="wrapper wrapper-content animated fadeInRight">

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
							<a href="javascript:void(0)" data-toggle="modal" data-target="#myModal" class="btn btn-primary SettingModal">Reservation Setting</a>
						</div>
						
                    </form>
            <div class="row">
                <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Reservations </h5>
                        <div class="ibox-tools">
						
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
                            <th>Name</th>
                            <th>Phone</th>
                            <th>No. of Guests</th>
                            <th>Status</th>
                            <th>Booking Date/Time</th>
                            <th>Comments</th>
                            <th></th>
                          
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($reservations as $key => $row)
                        <tr class="gradeX">
                             <td>{{ $reservations->firstItem() + $key }}</td>
                             <td>{{ $row->name }}</td>
                             <td>{{ $row->phone }}</td>
                             <td>{{ $row->guests }}</td>

                             
                             
                             @if($row->status == "Cancelled")
                             <td ><a class="btn btn-danger"> {{ $row->status }}</a></td>
                             @else
                             @if(date("Y-m-d H:i:s") >= date("Y-m-d H:i:00" , strtotime($row->booking_date . " " . $row->booking_time)))
                             <td ><a class="btn btn-success"> Completed </a></td>
                             @else
                             <td ><a class="btn btn-success"> Booked </a></td>
                             @endif
                             @endif
                           
                             <td>{{ date("d M Y" , strtotime($row->booking_date)) }} - {{ date("h:ia" , strtotime($row->booking_time)) }}</td>
                               <td>{!! $row->comments !!}</td>

                                <td> @if($row->status != "Cancelled")
                                    @if(date("Y-m-d H:i:s") < date("Y-m-d H:i:00" , strtotime($row->booking_date . " " . $row->booking_time)))
                                    <a href="javascript:void(0);" data-id="{{$row->id}}" class="btn btn-danger btn-sm CancelBooking">Cancel</a> 
                                    @endif
                                    @endif</td>
                            
                        </tr>
                    @empty
                       <tr> 
						  <td colspan="8">@lang('common.no_record_found') </td></tr>
                    @endforelse
						<tr> 
						  <td colspan="8">
						{!! $reservations->render() !!}
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
       


        


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">Resercation Setting</h4>
            </div>
            <form role="form" action="<?php echo url("expenses/save"); ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    {!! csrf_field() !!} 
                    <div id="RMsg" style="color:green"></div>
                    <div class="form-group">
                        <label> Hourly Reservation </label>
                        <input class="form-control" required value="{{$hourly_reservation}}" type="number" id="hourly_reservation" name="title">
                        <input class="form-control" type="hidden" id="id" name="id">
                    </div>
                    
				
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary SaveReserationSetting">Update</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

      
     <script> 
$( "body" ).on( "click", ".CancelBooking", function () {
        var task_id = $( this ).attr( "data-id" );
        var form_data = {
            id: task_id
        };

        swal( {
            title: "Are you sure",
            html: 'you want to cancel booking?',
            type: 'info',
            showCancelButton: true,
            confirmButtonColor: '#26A669',
            cancelButtonColor: '#C82333',
            cancelButtonText: 'No',
            confirmButtonText: "Yes"
        } ).then( ( result ) => { console.log(result);
            if (result ) {
                $.ajax( {
                    headers: {
                        'X-CSRF-TOKEN': $( 'meta[name="csrf-token"]' ).attr( 'content' )
                    },
                    url: '<?php echo url("cancel_reservation"); ?>',
                    type: "POST",

                    data: form_data,
                    success: function ( res ) {
                        location.reload();
                    }
                } );



                return true;
            }
        } )


    } );


     $('.datepicker').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: "dd-mm-yyyy"
            });



//  $("body").on("click", ".SettingModal", function () {
//     $(".SettingModal").modal("show");
//  });

 $("body").on("click", ".SaveReserationSetting", function () {
        var id = $("#hourly_reservation").val();
        var form_data = {
            hourly_reservation: id
        };
        $.ajax({
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '<?php echo url("reservation_setting"); ?>',
            data: form_data,
            success: function (msg) {
                $('#RMsg').text("Successfully Update");
                setTimeout(() => {
                     location.reload();
                }, 2000);
              
            }
        });

    });


    </script>




@endsection
