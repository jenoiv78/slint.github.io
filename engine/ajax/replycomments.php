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
 File: replycomments.php
-----------------------------------------------------
 Use: comments reply
=====================================================
*/

if(!defined('DATALIFEENGINE')) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if ( !$config['allow_registration'] ) {
	$dle_login_hash = sha1( SECURE_AUTH_KEY . $_IP );
}

if( $_REQUEST['user_hash'] == "" OR $_REQUEST['user_hash'] != $dle_login_hash ) {
	echo $lang['sess_error'];
	die();
}

if( !$user_group[$member_id['user_group']]['allow_addc'] OR !$config['allow_comments'] OR !$config['tree_comments']) {
	echo $lang['reply_error_1'];
	die();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0 ;
$indent = isset($_GET['indent']) ? intval($_GET['indent']) : 0 ;
$needwrap = isset($_GET['needwrap']) ? intval($_GET['needwrap']) : 0 ;

if( $id < 1 ) {
	echo $lang['reply_error_2'];
	die();
}

$row = $db->super_query("SELECT id, post_id, autor FROM " . PREFIX . "_comments WHERE id = '{$id}'");

if (!$row['id']) {
	echo $lang['reply_error_2'];
	die();
}

if ( $is_logged AND $user_group[$member_id['user_group']]['disable_comments_captcha'] AND $member_id['comm_num'] >= $user_group[$member_id['user_group']]['disable_comments_captcha'] ) {
		
		$user_group[$member_id['user_group']]['comments_question'] = false;
		$user_group[$member_id['user_group']]['captcha'] = false;
		
}


echo $lang['reply_descr']." <b>".$row['autor']."</b><br />";

echo "<form  method=\"post\" name=\"dle-comments-form-{$id}\" id=\"dle-comments-form-{$id}\">";

if( $is_logged ) echo "<input type=\"hidden\" name=\"name{$id}\" id=\"name{$id}\" value=\"{$member_id['name']}\" /><input type=\"hidden\" name=\"mail{$id}\" id=\"mail{$id}\" value=\"\" />";
else {
		
	echo <<<HTML
<div class="commentsreplyname" style="float:left;width:50%;padding-right: 10px;box-sizing: border-box;"><input type="text" name="name{$id}" id="name{$id}" style="width:100%;" placeholder="{$lang['reply_name']}" required></div>
<div class="commentsreplymail" style="float:left;width:50%;padding-left: 10px;box-sizing: border-box;"><input type="text" name="mail{$id}" id="mail{$id}" style="width:100%;" placeholder="{$lang['reply_mail']}"></div>
<div style="clear:both;padding-bottom:5px;"></div>
HTML;

}

	$p_name = urlencode($member_id['name']);
	$p_id = 0;

	if( $config['allow_comments_wysiwyg'] < 1 OR $config['simple_reply'] == "2" ) {
		
		if ( $config['simple_reply'] != "2") {
			
			include_once (DLEPlugins::Check(ENGINE_DIR . '/ajax/bbcode.php'));
			
			if ( $config['allow_comments_wysiwyg'] == 0 ) $params = "onfocus=\"setNewField(this.name, document.getElementById( 'dle-comments-form-{$id}' ) )\"";
			else $params = "";
		
		} else $params = "";
		
		$box_class = "bb-editor";


	} else {
		
		$params = "class=\"ajaxwysiwygeditor\"";
		$box_class = "wseditor dlecomments-editor";

		if ($config['allow_comments_wysiwyg'] == "1") {	

			if( $user_group[$member_id['user_group']]['allow_url'] ) $link_icon = "'insertLink', 'dleleech',"; else $link_icon = "";
			
			if ($user_group[$member_id['user_group']]['allow_image']) {
				if($config['bbimages_in_wysiwyg']) $link_icon .= "'dleimg',"; else $link_icon .= "'insertImage',";
			}
			
			if ($user_group[$member_id['user_group']]['allow_up_image']) {
				$link_icon .= "'dleupload',";
				$image_upload_params = "imageDefaultWidth: 0,imageUpload: true,imageAllowedTypes: ['jpeg', 'jpg', 'png', 'gif', 'webp', 'avif'],imageMaxSize: {$user_group[$member_id['user_group']]['up_image_size']} * 1024,imageUploadURL: dle_root + 'engine/ajax/controller.php?mod=upload',imageUploadParam: 'qqfile',imageUploadParams: { 'subaction' : 'upload', 'news_id' : '{$p_id}', 'area' : 'comments', 'author' : '{$p_name}', 'mode' : 'quickload', 'user_hash' : '{$dle_login_hash}' },";
			} else {
				$image_upload_params = "imageUpload: false,";
			}
	
			if ($user_group[$member_id['user_group']]['video_comments']) $link_icon .= "'insertVideo', 'dleaudio',";
			if ($user_group[$member_id['user_group']]['media_comments']) $link_icon .= "'dlemedia',";
			
		$bb_code = <<<HTML
<script>
	var text_upload = "{$lang['bb_t_up']}";

      $('.ajaxwysiwygeditor').froalaEditor({
        dle_root: dle_root,
        dle_upload_area : "comments",
        dle_upload_user : "{$p_name}",
        dle_upload_news : "{$p_id}",
        width: '100%',
        height: '220',
        zIndex: 9990,
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
</script>
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
	
	if( $config['simple_reply'] ==  "1" AND $config['jquery_version'] != "3" ) $timeout = 1400; else $timeout = 100;
	
		$bb_code = <<<HTML

<script>
var text_upload = "{$lang['bb_t_up']}";

setTimeout(function() {

	tinymce.remove('textarea.ajaxwysiwygeditor');

	tinyMCE.baseURL = dle_root + 'engine/editor/jscripts/tiny_mce';
	tinyMCE.suffix = '.min';
	
	{$image_upload[1]}
	
	tinymce.init({
		selector: 'textarea.ajaxwysiwygeditor',
		language : "{$lang['wysiwyg_language']}",
		element_format : 'html',
		width : "100%",
		height : 245,
		deprecation_warnings: false,
		
		plugins: ["link image lists paste quickbars dlebutton noneditable"],
		
		draggable_modal: true,
		toolbar_mode: 'floating',
		contextmenu: false,
		relative_urls : false,
		convert_urls : false,
		remove_script_host : false,
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
		statusbar : false,
		branding: false,
		browser_spellcheck: true,
		
		menubar: false,
		noneditable_editable_class: 'contenteditable',
		noneditable_noneditable_class: 'noncontenteditable',
		image_dimensions: false,
		{$image_upload[2]}
		
		toolbar: "bold italic underline | alignleft aligncenter alignright | bullist numlist | dleemo {$link_icon} | dlequote dlespoiler dlehide",
		
		dle_root: dle_root,
		dle_upload_area : "comments",
		dle_upload_user : "{$p_name}",
		dle_upload_news : "{$p_id}",
		
		content_css : dle_root + "{$editor_css}"

	});

}, {$timeout});

</script>
HTML;


		}
	}

echo <<<HTML
<div class="{$box_class}">
{$bb_code}
<textarea name="comments{$id}" id="comments{$id}" style="width:100%;height:245px;" {$params}></textarea>
</div>
HTML;

if ($config['allow_subscribe'] AND $user_group[$member_id['user_group']]['allow_subscribe']) {
echo <<<HTML
<div style="padding-top:5px;">
	<label class="comments_subscribe"><input type="checkbox" name="subscribe{$id}" id="subscribe{$id}" value="1">{$lang['c_subscribe']}</label>
</div>
HTML;
}

if( $user_group[$member_id['user_group']]['comments_question'] ) {
	$question = $db->super_query("SELECT id, question FROM " . PREFIX . "_question ORDER BY RAND() LIMIT 1");

	$_SESSION['question'] = $question['id'];

	$question = htmlspecialchars( stripslashes( $question['question'] ), ENT_QUOTES, $config['charset'] );
	
	echo <<<HTML
<div id="dle-question{$id}" style="padding-top:5px;">{$question}</div>
<div><input type="text" name="question_answer{$id}" id="question_answer{$id}" placeholder="{$lang['question_hint']}" class="quick-edit-text" required></div>
HTML;

}

if( $user_group[$member_id['user_group']]['captcha'] ) {

	if ( $config['allow_recaptcha'] ) {
		
		if( $config['allow_recaptcha'] == 2) {
			
			echo <<<HTML
	<input type="hidden" name="comments-recaptcha-response{$id}" id="comments-recaptcha-response{$id}" data-key="{$config['recaptcha_public_key']}" value="">
	<script>
	if ( typeof grecaptcha === "undefined"  ) {
	
		$.getScript( "https://www.google.com/recaptcha/api.js?render={$config['recaptcha_public_key']}").done(function () {
		
			grecaptcha.ready(function() {grecaptcha.execute('{$config['recaptcha_public_key']}', {action: 'comments'}).then(function(token) {\$('#comments-recaptcha-response{$id}').val(token);});});
			
		});

    } else {
		grecaptcha.execute('{$config['recaptcha_public_key']}', {action: 'comments'}).then(function(token) {\$('#comments-recaptcha-response{$id}').val(token);});
	}
	</script>
HTML;

		} else {
			
		echo <<<HTML
<div id="dle_recaptcha{$id}" style="padding-top:5px;height:78px;"></div><input type="hidden" name="recaptcha{$id}" id="recaptcha{$id}" value="1" />
<script>
<!--
	var recaptcha_widget;
	
	if ( typeof grecaptcha === "undefined"  ) {
	
		$.getScript( "https://www.google.com/recaptcha/api.js?hl={$lang['wysiwyg_language']}&render=explicit").done(function () {
		
			var setIntervalID = setInterval(function () {
				if (window.grecaptcha) {
					clearInterval(setIntervalID);
					recaptcha_widget = grecaptcha.render('dle_recaptcha{$id}', {'sitekey' : '{$config['recaptcha_public_key']}', 'theme':'{$config['recaptcha_theme']}'});
				};
			}, 300);
		});

    } else {
		recaptcha_widget = grecaptcha.render('dle_recaptcha{$id}', {'sitekey' : '{$config['recaptcha_public_key']}', 'theme':'{$config['recaptcha_theme']}'});
	}
//-->
</script>
HTML;
		}
		
	} else {

		echo <<<HTML
<div style="padding-top:5px;" class="dle-captcha"><a onclick="reload{$id}(); return false;" title="{$lang['reload_code']}" href="#"><span id="dle-captcha{$id}"><img src="{$config['http_home_url']}engine/modules/antibot/antibot.php" alt="{$lang['reload_code']}" width="160" height="80" /></span></a>
<input class="sec-code" type="text" name="sec_code{$id}" id="sec_code{$id}" placeholder="{$lang['captcha_hint']}" required>
</div>
<script>
<!--
function reload{$id} () {

	var rndval = new Date().getTime(); 

	document.getElementById('dle-captcha{$id}').innerHTML = '<img src="{$config['http_home_url']}engine/modules/antibot/antibot.php?rndval=' + rndval + '" width="160" height="80" alt="" />';
	document.getElementById('sec_code{$id}').value = '';
};
//-->
</script>
HTML;

	}
}
	
echo "<input type=\"hidden\" name=\"postid{$id}\" id=\"postid{$id}\" value=\"{$row['post_id']}\" /></form>";

if( $config['simple_reply'] ) {

	echo  <<<HTML
<div class="save-buttons" style="text-align: right;"><input class="bbcodes applychanges" title="{$lang['reply_comments']}" type="button" onclick="ajax_fast_reply('{$id}', '{$indent}', '{$needwrap}'); return false;" value="{$lang['reply_comments_1']}">
<input class="bbcodes cancelchanges" title="{$lang['bb_t_cancel']}" type="button" onclick="ajax_cancel_reply(); return false;" value="{$lang['bb_b_cancel']}">
</div>
HTML;

	
}

?>