<?php
/*
=====================================================
 DataLife Engine - by SoftNews Media Group 
-----------------------------------------------------
 http://dle-news.ru/
-----------------------------------------------------
 Copyright (c) 2004-2022 SoftNews Media Group
=====================================================
 This code is protected by copyright
=====================================================
 File: static.php
-----------------------------------------------------
 Use: WYSIWYG for static pages
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if (!isset($row['template'])) $row['template'] = "";
$p_name = urlencode($member_id['name']);

$row['id'] = isset($row['id']) ? $row['id'] : 0;

if( $config['allow_static_wysiwyg'] == 1 ) {

	$quick_icon = "'video',";

	if ( $user_group[$member_id['user_group']]['allow_image_upload'] OR $user_group[$member_id['user_group']]['allow_file_upload'] ) {
		$image_upload = "'dleupload',";
		$image_q_upload = ", 'imageUpload'";
		$quick_icon .= "'image',";
	} else {$image_upload = ""; $image_q_upload = "";}
	
	if($config['bbimages_in_wysiwyg']) {
		$implugin = 'dleimg';
	} else $implugin = 'insertImage';

echo <<<HTML
<script>
jQuery(function($){

      $('.wysiwygeditor').froalaEditor({
        dle_root: '',
        dle_upload_area : "template",
        dle_upload_user : "{$p_name}",
        dle_upload_news : "{$row['id']}",
        width: '100%',
        height: '400',
        language: '{$lang['wysiwyg_language']}',
		body_class: dle_theme,
        htmlRemoveTags: [],
		htmlAllowedAttrs: ['.*'],
		quickInsertButtons: [{$quick_icon}'table', 'ul', 'ol', 'hr'],
        imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'webp', 'avif'],
        imageDefaultWidth: 0,
        imageInsertButtons: ['imageBack', '|', 'imageByURL'{$image_q_upload}],
		imageUploadURL: 'engine/ajax/controller.php?mod=upload',
		imageUploadParam: 'qqfile',
		imageUploadParams: { "subaction" : "upload", "news_id" : "{$row['id']}", "area" : "template", "author" : "{$p_name}", "mode" : "quickload", "user_hash" : "{$dle_login_hash}"},
        imageMaxSize: {$config['max_up_size']} * 1024,
		
        toolbarButtonsXS: ['bold', 'italic', 'underline', 'strikeThrough', 'align', 'color', 'insertLink', 'emoticons', '{$implugin}', {$image_upload}'insertVideo', 'paragraphFormat', 'paragraphStyle', 'dlehide', 'dlequote', 'dlespoiler'],

        toolbarButtonsSM: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'color', 'insertLink', '|', 'emoticons', '{$implugin}',{$image_upload}'insertVideo', 'dleaudio', '|', 'paragraphFormat', 'paragraphStyle', '|', 'formatOL', 'formatUL', '|', 'dlehide', 'dlequote', 'dlespoiler'],

        toolbarButtonsMD: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'indent', 'outdent', '|', 'subscript', 'superscript', '|', 'insertTable', 'formatOL', 'formatUL', 'insertHR', '|', 'undo', 'redo', 'dletypo', 'clearFormatting', 'selectAll', '|', 'fullscreen', '-', 
                         'fontFamily', 'fontSize', '|', 'color', 'paragraphFormat', 'paragraphStyle', '|', 'insertLink', 'dleleech', '|', 'emoticons', '{$implugin}',{$image_upload}'|', 'insertVideo', 'dleaudio', 'dlemedia','|', 'dlehide', 'dlequote', 'dlespoiler','dlecode','page_dropdown', 'html'],

        toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'indent', 'outdent', '|', 'subscript', 'superscript', '|', 'insertTable', 'formatOL', 'formatUL', 'insertHR', '|', 'undo', 'redo', 'dletypo', 'clearFormatting', 'selectAll', '|', 'fullscreen', '-', 
                         'fontFamily', 'fontSize', '|', 'color', 'paragraphFormat', 'paragraphStyle', '|', 'insertLink', 'dleleech', '|', 'emoticons', '{$implugin}',{$image_upload}'|', 'insertVideo', 'dleaudio', 'dlemedia','|', 'dlehide', 'dlequote', 'dlespoiler','dlecode','page_dropdown', 'html']

      }).on('froalaEditor.image.inserted froalaEditor.image.replaced', function (e, editor, \$img, response) {
	  
			if( response ) {
			
			    response = JSON.parse(response);
			  
			    \$img.removeAttr("data-returnbox").removeAttr("data-success").removeAttr("data-xfvalue").removeAttr("data-flink");

				if(response.flink) {
				  if(\$img.parent().hasClass("highslide")) {
		
					\$img.parent().attr('href', response.flink);
		
				  } else {
		
					\$img.wrap( '<a href="'+response.flink+'" class="highslide"></a>' );
					
				  }
				}
			  
			}
			
		});
});
</script>
    <div class="editor-panel"><textarea name="template" id="template" class="wysiwygeditor" style="width:98%;height:300px;">{$row['template']}</textarea></div>
HTML;


} else {

	if($config['bbimages_in_wysiwyg']) {
		$implugin = 'dleimage';
	} else $implugin = 'image';

	$image_upload = array();
	
	if ( $user_group[$member_id['user_group']]['allow_image_upload'] ) {

		$image_upload[0] = "dleupload ";

		$image_upload[1] = <<<HTML
function dle_image_upload_handler (blobInfo, success, failure, progress) {
  var xhr, formData;

  xhr = new XMLHttpRequest();
  xhr.withCredentials = false;
  xhr.open('POST', 'engine/ajax/controller.php?mod=upload');
  
  xhr.upload.onprogress = function (e) {
    progress(e.loaded / e.total * 100);
  };

  xhr.onload = function() {
    var json;

    if (xhr.status === 403) {
      failure('HTTP Error: ' + xhr.status, { remove: true });
      return;
    }

    if (xhr.status < 200 || xhr.status >= 300) {
      failure('HTTP Error: ' + xhr.status);
      return;
    }

    json = JSON.parse(xhr.responseText);

    if (!json || typeof json.link != 'string') {

		if(typeof json.error == 'string') {
			failure(json.error);
		} else {
			failure('Invalid JSON: ' + xhr.responseText);	
		}
		
		var editor = tinymce.activeEditor;
		var node = editor.selection.getEnd();
		editor.selection.select(node);
		editor.selection.setContent('');
		
      return;
    }

	if( json.flink ) {
		
		var editor = tinymce.activeEditor;
		var node = editor.selection.getEnd();
		editor.selection.select(node);
		editor.selection.setContent('<a href="'+json.flink+'" class="highslide"><img src="'+json.link+'" style="display: block; margin-left: auto; margin-right: auto;"></a>&nbsp;');
		editor.notificationManager.close();

	} else {
		success(json.link);
	}
	
  };

  xhr.onerror = function () {
    failure('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
  };

  formData = new FormData();
  formData.append('qqfile', blobInfo.blob(), blobInfo.filename());
  formData.append("subaction", "upload");
  formData.append("news_id", "{$row['id']}");
  formData.append("area", "template");
  formData.append("author", "{$p_name}");
  formData.append("mode", "quickload");
  formData.append("editor_mode", "tinymce");
  formData.append("user_hash", "{$dle_login_hash}");    
  
  xhr.send(formData);
};
HTML;

		$image_upload[2] = <<<HTML
paste_data_images: true,
automatic_uploads: true,
images_upload_handler: dle_image_upload_handler,
images_reuse_filename: true,
image_uploadtab: false,
images_file_types: 'gif,jpg,png,jpeg,bmp,webp,avif',
file_picker_types: 'image',

file_picker_callback: function (cb, value, meta) {
  var input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');

  input.onchange = function () {
	var file = this.files[0];

	var filename = file.name;
	filename = filename.split('.').slice(0, -1).join('.');

	var reader = new FileReader();
	reader.onload = function () {

	  var id = filename;
	  var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
	  var base64 = reader.result.split(',')[1];
	  var blobInfo = blobCache.create(id, file, base64);
	  blobCache.add(blobInfo);

	  cb(blobInfo.blobUri());
	};
	reader.readAsDataURL(file);
  };

  input.click();
},
HTML;
		
	} else {
		
		$image_upload[0] = "";
		$image_upload[1] = "";
		$image_upload[2] = "";
		
	}	
	
	if( $user_group[$member_id['user_group']]['allow_file_upload'] ) {
		$image_upload[0] = "dleupload ";
	}	

if( @file_exists( ROOT_DIR . '/templates/'. $config['skin'].'/editor.css' ) ) {
	
	$editor_css = "templates/{$config['skin']}/editor.css";
		
} else $editor_css = "engine/editor/css/content.css";

echo <<<HTML
<script>
jQuery(function($){

	{$image_upload[1]}

	tinyMCE.baseURL = 'engine/editor/jscripts/tiny_mce';
	tinyMCE.suffix = '.min';

	if(dle_theme === null) dle_theme = '';

	tinymce.init({
		selector: 'textarea.wysiwygeditor',
		language : "{$lang['wysiwyg_language']}",
		element_format : 'html',
		body_class: dle_theme,
		skin: dle_theme == 'dle_theme_dark' ? 'oxide-dark' : 'oxide',

		width : "100%",
		height : 400,
		deprecation_warnings: false,

		plugins: ["fullscreen advlist autolink lists link image charmap anchor searchreplace visualblocks visualchars nonbreaking noneditable table paste codemirror spellchecker dlebutton codesample hr quickbars autosave wordcount pagebreak"],
		
		setup: function(editor) {
			editor.on('PreInit', function() {
				var shortEndedElements = editor.schema.getShortEndedElements();
				shortEndedElements['path'] = {};
				shortEndedElements['source'] = {};
				shortEndedElements['use'] = {};
			});
		},

		relative_urls : false,
		convert_urls : false,
		remove_script_host : false,
		verify_html: false,
		nonbreaking_force_tab: true,
		branding: false,
		browser_spellcheck: true,
		default_link_target: '_blank',
		pagebreak_separator: '{PAGEBREAK}',
		pagebreak_split_block: true,
		noneditable_editable_class: 'contenteditable',
		noneditable_noneditable_class: 'noncontenteditable',
		contextmenu: 'image imagetools table spellchecker lists',
		
		image_advtab: true,
		image_caption: true,
		{$image_upload[2]}
		
		draggable_modal: true,
		menubar: 'edit insert format table view',
		toolbar: 'bold italic underline strikethrough | align | bullist numlist | link dleleech unlink | {$implugin} {$image_upload[0]} dlemp dlaudio dletube dleemo | dle | fontformatting textformatting fullscreen code',
		toolbar_mode: 'floating',
		toolbar_groups: {
		  
		  fontformatting: {
			icon: 'change-case',
			tooltip: 'Formatting',
			items: 'formatselect fontselect fontsizeselect | forecolor backcolor'
		  },
		  
		  textformatting: {
			icon: 'edit-block',
			tooltip: 'Tools',
			items: 'searchreplace spellchecker | dletypo removeformat'
		  },
		  
		  align: {
			icon: 'align-center',
			tooltip: 'Formatting',
			items: 'alignleft aligncenter alignright alignjustify'
		  },
		  
		  dle: {
			icon: 'preview',
			tooltip: 'DLE Tags',
			items: 'dlequote dlespoiler dlehide codesample | pagebreak dlepage'
		  }
		  
		  
		},
		
		menu: {
			view: { title: 'View', items: 'restoredraft code | visualaid visualchars visualblocks | spellchecker | fullscreen' }
		},
		removed_menuitems: 'codeformat, bold, italic, underline, strikethrough',

		quickbars_insert_toolbar: '',
		quickbars_selection_toolbar: 'bold italic underline | quicklink dlequote dlespoiler dlehide | formatselect',

		autosave_ask_before_unload: true,
		autosave_interval: '10s',
		autosave_prefix: 'dle-editor-{path}{query}-{id}-',
		autosave_restore_when_empty: false,
		autosave_retention: '10m',
  
		formats: {
		  bold: {inline: 'b'},  
		  italic: {inline: 'i'},
		  underline: {inline: 'u', exact : true},  
		  strikethrough: {inline: 's', exact : true}
		},
		
		codesample_languages: [ {text: 'HTML/JS/CSS', value: 'markup'}],
		spellchecker_language : "ru",
		spellchecker_languages : "Russian=ru,Ukrainian=uk,English=en",
		spellchecker_rpc_url : "https://speller.yandex.net/services/tinyspell",
		
		dle_root : "",
		dle_upload_area : "template",
		dle_upload_user : "{$p_name}",
		dle_upload_news : "{$row['id']}",

		content_css : "{$editor_css}"

	});

});
</script>
    <div class="editor-panel"><textarea name="template" id="template" class="wysiwygeditor" style="width:98%;height:400px;">{$row['template']}</textarea></div>
HTML;

}
?>