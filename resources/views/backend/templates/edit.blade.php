@extends( 'layouts.app' )@section( 'content' )

{{-- <link href="{{ asset('assets/summernote/summernote.css')}}" rel="stylesheet">
<script src="{{ asset('assets/summernote/summernote.js') }}"></script> --}}
<script src="https://cdn.ckeditor.com/4.7.0/full-all/ckeditor.js"></script>

<link href="{{url('assets/css/plugins/chosen/chosen.css')}}" rel="stylesheet">
 <script src="{{url('assets/js/plugins/chosen/chosen.jquery.js')}}"></script>

<script> 
$(function() { 

	//  CKEDITOR.replace( 'summernote' , {
	// 	allowedContent: true,
	// 	toolbar : [
	// 		{ name: 'document', items: [ 'Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates' ] },
	// 		{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
	// 		{ name: 'editing', items: [ 'Find', 'Replace', '-', 'SelectAll', '-', 'Scayt' ] },
	// 		{ name: 'forms', items: [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
			
	// 		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat' ] },
	// 		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language' ] },
	// 		{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
	// 		{ name: 'insert', items: [ 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },
		
	// 		{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
	// 		{ name: 'colors', items: [ 'TextColor', 'BGColor' ] },
	// 		{ name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
	// 		{ name: 'about', items: [ 'About' ] }
	// 	],
	// 	extraAllowedContent: 'style;*[id,rel](*){*}'
	// });


var editorInstance = CKEDITOR.replace( document.getElementById( "summernote" ), {
  language_list: [ "en:English"],
  disableNativeSpellChecker: true,
  allowedContent: true,
  removeButtons: "Replace,Find,Redo,Undo,Copy,NewPage,Save,Print,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Blockquote,CreateDiv,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,ShowBlocks,About",
  
  extraPlugins: "colorbutton,print,font,autolink,justify",
  removePlugins:
    "maximize,image,stylescombo,scayt,wsc,elementspath,blockquote,specialchar,resize",
  title: this.title,
  height:500,
  qtBorder:"0",
  startupShowBorder:false,
  readOnly: false,
  resize_enabled: false,
  autoGrow_minHeight: 200,
  autoGrow_bottomSpace: 50,
  autoGrow_onStartup: true,
  toolbarStartupExpanded: false,
  toolbarGroups: [
    { name: "others" },
    { name: "clipboard", groups: ["clipboard", "undo"] },
    { name: "editing", groups: ["find", "selection", "spellchecker"] },
     { name: "links" },
    { name: "insert" },
    { name: "forms" },
    { name: "tools" },
    { name: "styles" },
    { name: "basicstyles", groups: ["basicstyles", "cleanup"] },
    {
      name: "paragraph",
      groups: ["list", "indent", "blocks", "align", "bidi"]
    },
    { name: "colors" },
     { name: "document", groups: ["mode", "document", "doctools"] }
  ],
});


	

});
</script>
<style>
.note-editor {
	margin-top: 5px;
}
</style>


<div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-10">
                    <h2>Send Email</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="{{url('')}}">@lang('common.home')</a>
                        </li>
                        {{-- <li>
                             <a href="{{url('admin/email_templates')}}">Email Template</a>
                        </li> --}}
                        <li class="active">
                            <strong>Send Email</strong>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-2">

                </div>
            </div>
            
			
	<div class="wrapper wrapper-content animated fadeInRight">
<div class="row">
              
			 	
        <div class="col-md-12">
					<form  id="form_template" action="{{url('admin/email_templates/store')}}" method="post">
							{{ csrf_field() }}
						<input type="hidden" name="id" id="id" value="{{$template->id}}">
						<input type="hidden" name="short_code" id="short_code" value="{{$template->short_code}}">
						<input type="hidden" name="title" id="short_code" value="{{$template->title}}">
          <div class="tile">
						<h3 class="tiletitle">{{$template->Subject}}</h3>
			  <hr>
            		<div class="row">
 <div class="col-md-12">

    <button type="button" style="float:right"  onclick="CKEDITOR.tools.callFunction(102,this);return false;" class="btn btn-primary text-dark btn-sm float-right mb-3" id="previewButtonn">Preview</button>
                                        <br>
                                        
									 <div class="form-group">
                                         <label>Sent To</label>
                                         <select class="form-control chosen-select" name="sentto[]" multiple> 
                                            <option value="all">All</option>
                                            @foreach ($customers as $user)
                                                 <option value="{{$user->email}}">{{$user->name}}</option>
                                            @endforeach
                                            @foreach ($newsletters as $user)
                                                <option value="{{$user->email}}">{{$user->email}}</option>
                                            @endforeach
                                         </select>
										
									 </div>
									 <div class="form-group">
										 <label>Template Subject</label>
										 <input type='text' class="form-control" value="{{$template->subject}}"  name="subject" required>

									 </div>

								
									<div class="form-group">
										
										<label>Message</label>
										<textarea class="form-control summernote mt-3" rows="25" id="summernote" name="message">{{$template->message}}</textarea>
									</div>
									
										

                
                
 <div class="text-right">
 {{-- <a href="{{url('admin/email_templates')}}" class="btn btn-secondary">Cancel</a> --}}
              <button class="btn btn-primary" type="submit">Send</button>
	 			
			</div>
						 

					</div>
					</div>
					 </div>
				
           
           
		
			
			
		</form>
        </div>
			</div>
			</div>
			
<script>
	$(function () {
        $("body").on("click" , "#previewButton", function() {

            $.ajax({
                url: "<?php echo url('admin/email_template/save_preview'); ?>", // Url to which the request is send
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: $('#form_template').serialize(),
                success: function(res)
                {
                    var obj = res;
                    if(obj.hasOwnProperty('succes')){
                        var url = '{{url('admin/email_template/preview/')}}/'+obj.id
                        window.open(url, '_blank');
                    }


                }
            });
        });
    })


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

</script>
@endsection
