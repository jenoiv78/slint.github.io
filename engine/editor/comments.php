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
 File: comments.php
-----------------------------------------------------
 Use: WYSIWYG for comments
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

$p_id = isset($p_id) ? intval($p_id) : 0;
$p_name= isset($p_name) ? $p_name : '';


if( $config['allow_comments_wysiwyg'] == 1 ) {

	if ($user_group[$member_id['user_group']]['allow_url']) $link_icon = "'insertLink', 'dleleech',"; else $link_icon = "";

	if ($user_group[$member_id['user_group']]['allow_image']) {
		if($config['bbimages_in_wysiwyg']) $link_icon .= "'dleimg',"; else $link_icon .= "'insertImage',";
	}

	if ($user_group[$member_id['user_group']]['allow_up_image']) {
		$link_icon .= "'dleupload',";
		$image_upload_params = "imageDefaultWidth: 0,imageUpload: true,imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'webp', 'avif'],imageMaxSize: {$user_group[$member_id['user_group']]['up_image_size']} * 1024,imageUploadURL: dle_root + 'engine/ajax/controller.php?mod=upload',imageUploadParam: 'qqfile',imageUploadParams: { 'subaction' : 'upload', 'news_id' : '{$p_id}', 'area' : 'comments', 'author' : '{$p_name}', 'mode' : 'quickload', 'user_hash' : '{$dle_login_hash}' },";
	} else {
		$image_upload_params = "imageUpload: false,";
	}

	if ($user_group[$member_id['user_group']]['video_comments']) {
		$link_icon .= "'insertVideo', 'dleaudio',";
	}

	if ($user_group[$member_id['user_group']]['media_comments']) $link_icon .= "'dlemedia',";

	$onload_scripts[] = <<<HTML

      $('#comments').froalaEditor({
        dle_root: dle_root,
        dle_upload_area : "comments",
        dle_upload_user : "{$p_name}",
        dle_upload_news : "{$p_id}",
        width: '100%',
        height: '220',
        language: '{$lang['wysiwyg_language']}',

		htmlAllowedTags: ['div', 'span', 'p', 'br', 'strong', 'em', 'ul', 'li', 'ol', 'b', 'u', 'i', 's', 'a', 'img', 'hr'],
		htmlAllowedAttrs: ['class', 'href', 'alt', 'src', 'style', 'target'],
		pastePlain: true,
        imagePaste: false,
        listAdvancedTypes: false,
        {$image_upload_params}
		videoInsertButtons: ['videoBack', '|', 'videoByURL'],
		quickInsertEnabled: false,

        toolbarButtonsXS: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'formatOL', 'formatUL', '|', {$link_icon} 'emoticons', '|', 'dlehide', 'dlequote', 'dlespoiler'],

        toolbarButtonsSM: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'formatOL', 'formatUL', '|', {$link_icon} 'emoticons', '|', 'dlehide', 'dlequote', 'dlespoiler'],

        toolbarButtonsMD: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'formatOL', 'formatUL', '|', {$link_icon} 'emoticons', '|', 'dlehide', 'dlequote', 'dlespoiler'],

        toolbarButtons: ['bold', 'italic', 'underline', 'strikeThrough', '|', 'align', 'formatOL', 'formatUL', '|', {$link_icon} 'emoticons', '|', 'dlehide', 'dlequote', 'dlespoiler']

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

HTML;

$wysiwyg = <<<HTML
<script>
	var text_upload = "{$lang['bb_t_up']}";
	var dle_quote_title  = "{$lang['i_quote']}";
</script>
<div class="wseditor"><textarea id="comments" name="comments" rows="10" cols="50" class="ajaxwysiwygeditor">{$text}</textarea></div>
HTML;

} else {

	if ($user_group[$member_id['user_group']]['allow_url']) $link_icon = "link dleleech "; else $link_icon = "";
	
	if ($user_group[$member_id['user_group']]['allow_image']) {
		if($config['bbimages_in_wysiwyg']) $link_icon .= "| dleimage "; else $link_icon .= "| image ";
	}

	$image_upload = array();
	
	if ( $user_group[$member_id['user_group']]['allow_image'] AND  $user_group[$member_id['user_group']]['allow_up_image'] ) {

		$link_icon .= "dleupload ";

		$image_upload[1] = <<<HTML
function dle_image_upload_handler (blobInfo, success, failure, progress) {
  var xhr, formData;

  xhr = new XMLHttpRequest();
  xhr.withCredentials = false;
  xhr.open('POST', dle_root + 'engine/ajax/controller.php?mod=upload');
  
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
  formData.append("news_id", "{$p_id}");
  formData.append("area", "comments");
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

	if ($user_group[$member_id['user_group']]['video_comments']) $link_icon .= "dlemp dlaudio ";

	if ($user_group[$member_id['user_group']]['media_comments']) $link_icon .= "dletube ";

	if( @file_exists( ROOT_DIR . '/templates/'. $config['skin'].'/editor.css' ) ) {
		
			$editor_css = "templates/{$config['skin']}/editor.css";
			
	} else $editor_css = "engine/editor/css/content.css";
	
	$onload_scripts[] = <<<HTML

	{$image_upload[1]}
	
	tinyMCE.baseURL = dle_root + 'engine/editor/jscripts/tiny_mce';
	tinyMCE.suffix = '.min';

	tinymce.init({
		selector: 'textarea#comments',
		language : "{$lang['wysiwyg_language']}",
		element_format : 'html',
		width : "100%",
		height : 260,

		deprecation_warnings: false,
		
		plugins: ["link image lists paste quickbars dlebutton noneditable"],
		
		draggable_modal: true,
		toolbar_mode: 'floating',
		contextmenu: false,
		relative_urls : false,
		convert_urls : false,
		remove_script_host : false,
		browser_spellcheck: true,
		extended_valid_elements : "div[align|style|class|contenteditable],b/strong,i/em,u,s,p[align|style|class|contenteditable]",
		quickbars_insert_toolbar: '',
		quickbars_selection_toolbar: 'bold italic underline | dlequote dlespoiler dlehide',
		
	    formats: {
	      bold: {inline: 'b'},
	      italic: {inline: 'i'},
	      underline: {inline: 'u', exact : true},
	      strikethrough: {inline: 's', exact : true}
	    },
		
		paste_as_text: true,
		elementpath: false,
		branding: false,
		
		dle_root : dle_root,
		dle_upload_area : "comments",
		dle_upload_user : "{$p_name}",
		dle_upload_news : "{$p_id}",
		
		menubar: false,
		noneditable_editable_class: 'contenteditable',
		noneditable_noneditable_class: 'noncontenteditable',
		image_dimensions: false,
		{$image_upload[2]}
		
		toolbar: "bold italic underline | alignleft aligncenter alignright | bullist numlist | dleemo {$link_icon} | dlequote dlespoiler dlehide",
		content_css : dle_root + "{$editor_css}"

	});
HTML;

$wysiwyg = <<<HTML
<script>
	var text_upload = "{$lang['bb_t_up']}";
	var dle_quote_title  = "{$lang['i_quote']}";
</script>
<div class="wseditor dlecomments-editor"><textarea id="comments" name="comments" style="width:100%;height:260px;">{$text}</textarea></div>
HTML;


}

if ( isset($allow_subscribe) AND $allow_subscribe ) $wysiwyg .= "<br /><label class=\"comments_subscribe\"><input type=\"checkbox\" name=\"allow_subscribe\" id=\"allow_subscribe\" value=\"1\" />" . $lang['c_subscribe'] . "</label><br />";


?>
