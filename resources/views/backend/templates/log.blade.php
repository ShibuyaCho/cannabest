@extends( 'admin.layouts.app' )@section( 'content' )

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>


<div class="app-title">

	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i>
		</li>
		<li class="breadcrumb-item"><a href="{{url('admin/dashboard')}}">Dashboard</a>
		</li>
		<li class="breadcrumb-item">Email Templates Previous Versions</li>
	</ul>
</div>


<div class="row">
	<div class="col-md-12">
		  @include('admin.layouts.alerts')
		<div class="tile">
			<h3 class="tile-title"> Email Templates Previous Versions
				{{--<a href="javascript:void(0);" class="btn btn-sm btn-primary pull-right" id="AddNew" data-tooltip="tooltip" title="Click to Add Email Template"><i class="fa fa-plus"></i> Add New</a>--}}
			</h3>
			<hr>
			<div class="tile-body">
				<div class="table-responsive">
				<table class="table table-hover" id="sampleTable">
					<thead>
						<tr>
							<th width="30">ID</th>
							<th>Subject</th>
							<th>Updated At</th>
							<th>Updated By</th>
						</tr>
					</thead>
					<tbody>
						@foreach($templates as $key=>$row)
						<tr>
							<td> {{$key + 1}} </td>
							<td> {{$row->subject}}</td>
							<td> {{date("d M Y", strtotime($row->updated_at))}} </td>
							<td> {{$row->firstname}} {{$row->lastname}}</td>
							{{--<td> @if($row->status == 1) Published @else Unpublished @endif </td>--}}
							

                        </tr>
                        @endforeach
                    </tbody>
				</table>
					</div>
			
            
            </div>
        </div>
    </div>
</div>

<script src="{{url('backend/sweetalerts/sweetalert2.all.js')}}"></script>


<div class="modal fade" id="ExpiryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Add Email Template</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<form action="{{url('admin/email_templates/store')}}" id="form_template" method="post" enctype="multipart/form-data">
				{{csrf_field()}}
				<div class="modal-body">
					<div class="form-group">
						<label for="">Tilte</label>
						<input type="text" id="title" required name="title"  class="form-control">
					</div>

					<div class="form-group">
						<label for="">Short Code</label>
						<input type="text" id="short_code" required name="short_code" readonly  class="form-control">
					</div>
					<div class="form-group">
						<label for="">Subject</label>
						<input type="text" id="subject" required name="subject"  class="form-control">
					</div>

					<div class="form-group">
						<label for="">Status</label>
						<select class="form-control" name="status" id="status">
								<option value="1">Published</option>
								<option value="0">Unpublished</option>
						</select>
					</div>
					<div class="form-group">
						<label for="">Message</label>
						<textarea rows="5" id="message" required name="message"  class="form-control summernote"></textarea>
					</div>

							{{-- <div class="form-group">
								<label for="">Featured</label>
								<select class="form-control" name="featured" id="featured"> 
										<option value="0">No</option>
										<option value="1">Yes</option>
								</select>
							</div> --}}


							
							
				</div>
				<div class="modal-footer"><div style="color:red" id="dates_error"> </div> 
					<input type="hidden" name="id" id="id">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-sm btn-primary" id="AddUpdateReward">Save</button>
					<button type="button" class="btn btn-sm btn-primary" style="display: none;" id="previewButton">Preview</button>
				</div>
			</form>
		</div>
	</div>

</div>


<style>
    .sweet-alert h2 {
        font-size: 1.3rem !important;
    }
    
    .sweet-alert .sa-icon {
        margin: 30px auto 35px !important;
    }

	.toggle-handle {
		position: relative;
		margin: 0 auto;
		padding-top: 0;
		padding-bottom: 0;
		height: 100%;
		width: 0;
		border-width: 0 1px;
		background: #ddd !important;
	}
</style>

<script type="text/javascript" src="{{url('backend/js/plugins/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{url('backend/js/plugins/dataTables.bootstrap.min.js')}}"></script>

    <script type="text/javascript">
	$('#sampleTable').DataTable({
        "order": [[ 0, "asc" ]],
        'pageLength' :100,
    });
	$('#sampleTable').on( 'page.dt , order.dt , search.dt, length.dt', function () {
		 setTimeout(() => {
			 $('.FeatureCategory').bootstrapToggle();
		 }, 1);
		 
	});

	</script>



@endsection
