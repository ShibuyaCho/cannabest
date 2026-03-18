@extends('layouts.app')
@extends('layouts.app')

@section('content')

<link href="assets/css/plugins/dataTables/datatables.min.css" rel="stylesheet">
<link href="{{url('assets/css/plugins/sweetalert/sweetalert.css')}}" rel="stylesheet">
<script src="{{url('assets/js/plugins/sweetalert/sweetalert.min.js')}}"></script>

<link href="{{url('assets/css/plugins/datapicker/datepicker3.css')}}" rel="stylesheet">
    <script src="{{url('assets/js/plugins/datapicker/bootstrap-datepicker.js')}}"></script>

 <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Bookings</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                     
                        <li class="active">
                            <strong>Bookings</strong>
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
						</div>
						
                    </form>


            <div class="row">
                <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Bookings </h5>
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
                              <th>Bookings</th>
                            <th>Total Amount</th>
                            <th>Customer Name</th>
                            <th>Booking date</th>
                           
                            <th>Phone</th>
                            <th>Payment Status</th>
                            <th>Comments</th>
                           
                            <th></th>
                          
                           
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($bookings as $key => $row)
                        <?php $items = json_decode($row->bookings);
                        $total_amount = 0;
                            $type_html = "";
                            foreach($items as $item) { 
                                
                                $type_name = ""; 
                                if(!empty($item->type_id)) { 
                                    $type = get_booking_type($item->type_id);
                                    if(!empty($type)) $type_name = $type->name; 
                                } 
                                $type_html .= $type_name . ": ($" . $item->hours * $item->qty * $item->price .  ")  $item->qty Quantity,  $item->hours hours<br>"; 
                                $total_amount += $item->hours * $item->qty * $item->price;
                            }
                        ?>
                        <tr class="gradeX">
                             <td>{{ $bookings->firstItem() + $key }}</td>
                             <td>{!! $type_html !!}</td>
                             <td>${{ $total_amount }}</td>
                             <td>{{ $row->name }}</td>
                             <td>{{ date("d M Y", strtotime($row->booking_date)) }} <br> {{date("h:ia", strtotime($row->booking_time))}}</td>
                             <td>{{ $row->phone }}</td>
                             @if($row->payment_status == "Pending")
                             <td> <a class="btn btn-warning">{{ $row->payment_status }}</a></td>
                             @endif
                             @if($row->payment_status == "Cancelled")
                             <td ><a class="btn btn-danger"> {{ $row->payment_status }}</a></td>
                             @endif
                             @if($row->payment_status == "Paid")
                             <td ><a class="btn btn-success"> {{ $row->payment_status }}</a></td>
                             @endif
                             <td>{!! $row->comments !!}</td>
                            
                             <td> 
                             @if($row->payment_status != "Cancelled")
                                @if(date("Y-m-d H:i:s") < date("Y-m-d H:i:00" , strtotime($row->booking_date . " " . $row->booking_time)))
                                    <a href="javascript:void(0);" data-id="{{$row->id}}" class="btn btn-danger btn-sm CancelBooking">Cancel</a> 
                                @endif
                             @endif
                            </td>
                            
                        </tr>
                    @empty
                       <tr> 
						  <td colspan="9">@lang('common.no_record_found') </td></tr>
                    @endforelse
						<tr> 
						  <td colspan="9">
						{!! $bookings->render() !!}
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
                    url: '<?php echo url("cancel_booking"); ?>',
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


      </script>
    

@endsection
