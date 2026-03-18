@extends( 'admin.layouts.app' )@section( 'content' )

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<link href="{{url('assets/summernote/summernote.css')}}" rel="stylesheet">
<script src="{{url('assets/summernote/summernote.js')}}"></script>

<link href='http://fonts.googleapis.com/css?family=Signika:600,400,300' rel='stylesheet' type='text/css'>
<link href="{{url('assets/editor/codemirror.css')}}" rel="stylesheet">
<script src="{{url('assets/editor//codemirror.js')}}"></script>
<script src="{{url('assets/editor//matchbrackets.js')}}"></script>
<script src="{{url('assets/editor//htmlmixed.js')}}"></script>
<script src="{{url('assets/editor//xml.js')}}"></script>
<script src="{{url('assets/editor//javascript.js')}}"></script>
<script src="{{url('assets/editor//css.js')}}"></script>
<script src="{{url('assets/editor//clike.js')}}"></script>
<script src="{{url('assets/editor//php.js')}}"></script>


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

<div class="app-title">

	<ul class="app-breadcrumb breadcrumb">
		<li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i>
		</li>
		<li class="breadcrumb-item"><a href="{{url('admin/dashboard')}}">Dashboard</a>
		</li>
		<li class="breadcrumb-item">Email Templates</li>
	</ul>
</div>


<div class="row">
	<div class="col-md-12">
		  @include('admin.layouts.alerts')
		<div class="tile">
			<h3 class="tile-title"> Email Templates </h3>
			<hr>
			<div class="tile-body">
				<form action="{{url('admin/email/save')}}" method="post" enctype="multipart/form-data">
				{{csrf_field()}}
				<div class="modal-body">

					<?php 

				// if(!empty($_GET['file'])) {
				// 	$real_file = $_GET['file']; 
				// } else { 
				// 	$real_file = "";
				// }
				
				
				// $content = "";
				// if(!empty($real_file)) { 
				// $content = file_get_contents($real_file , FILE_USE_INCLUDE_PATH);
				// $content = htmlspecialchars( $real_file );
				
				// }
				
				?>

				
					<div class="form-group">
						<label for=""></label>
						 <textarea id="message" name="message" class="form-control summernotem" rows="20">{{$data}}</textarea>
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
					<input type="hidden" value="{{$template->id}}" name="id" id="id">
					<input type="hidden" value="{{$template->file_path}}" name="file" id="id">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-sm btn-primary" id="AddUpdateReward">Update File</button>
				</div>
			</form>
            
            </div>
        </div>
    </div>
</div>
<script> 
      var editor = CodeMirror.fromTextArea(document.getElementById("message"), {
        lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 4,
        indentWithTabs: true,
        enterMode: "keep",
        tabMode: "shift"
      });
   
   </script>


@endsection
