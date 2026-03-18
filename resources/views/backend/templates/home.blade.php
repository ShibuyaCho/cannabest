@extends( 'layouts.app' )@section( 'content' )

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<link href="{{url('assets/summernote/summernote.css')}}" rel="stylesheet">
<script src="{{url('assets/summernote/summernote.js')}}"></script>
<script>
    var toolbar =  [
        ['font', ['bold', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['view', ['fullscreen', 'codeview']]
    ];
  $(function() {
     $('.FeatureCategory').bootstrapToggle();
      $('.summernote').summernote({
          toolbar: toolbar,
          height: 250,
          popover: {
              // air: [
              // ['color', ['color']],
              // ['font', ['bold', 'underline', 'clear']]
              // ]
          },
          callbacks: {
              // Clear all formatting of the pasted text
              onPaste: function (e) {
                  var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                  e.preventDefault();
                  setTimeout( function(){
                      document.execCommand( 'insertText', false, bufferText );
                  }, 10 );
              }
          }
      });
  });

</script>



<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2> Email Templates</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                        <li>
                             <a href="{{url('admin/email_templates')}}">Email Templates</a>
                        </li>
                        <li class="active">
                            <strong> Email Templates</strong>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-2">

                </div>
            </div>
			
			

			<div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">

                @if(Session::has('success_message'))
                    <div class="alert alert-success">
                    <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                {!! ucfirst(Session::get('success_message')) !!}
                </div>
                @endif
        
                <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Email Template</h5>
                        {{-- <div class="ibox-tools">
						<a href="{{ url('categories/create') }}" class="btn btn-primary btn-xs">@lang('common.add_new')</a>
						
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
							
                          
                           
                        </div> --}}
                    </div>
                    <div class="ibox-content">

                        <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" >
					
					<thead>
						<tr>
							<th width="30">ID</th>
							<th>Title</th>
							<th>Subject</th>
							<th>Updated At</th>
							{{--<th>Status</th>--}}
							<th width="130" class="text-center">Actions</th>
						</tr>
					</thead>
					<tbody>
						@foreach($templates as $key=>$row)
						<tr>
							<td> {{$key + 1}} </td>
							<td>  {{$row->title}} </td>
							<td> <a href="{{url('admin/email_template/edit/'.$row->id)}}" data-id="<?php echo $row->id; ?>"> {{$row->subject}} </a></td>
							<td> {{date("d M Y", strtotime($row->updated_at))}} </td>
							{{--<td> @if($row->status == 1) Published @else Unpublished @endif </td>--}}
							
							<td>

								<div class="actions-btns">
									<a href="{{url('admin/email_template/edit/'.$row->id)}}" data-id="<?php echo $row->id; ?>" class="btn btn-sm btn-primary" data-tooltip="tooltip" title="Click to Edit"><i class="fa fa-pencil"></i></a>
									<!--<a href="javascript:void(0);" data-id="<?php //echo $row->id; ?>" class="btn btn-sm btn-danger delete" data-tooltip="tooltip" title="Click to Delete"><i class="fa fa-trash"></i></a>-->
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    
                    </table>
                        </div>

                    </div>
                </div>
            </div>
            </div>
           
        </div>
       
	  
		

	


@endsection
