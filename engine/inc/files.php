<?PHP
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
 Files: files.php
-----------------------------------------------------
 Use: manage uploaded pictures
=====================================================
*/
if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if( $member_id['user_group'] != 1 ) {
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

$path = '';
$userdir = '';

if( isset($_GET['userdir']) AND $_GET['userdir'] ) {
	
	$path = $userdir = cleanpath( $_GET['userdir'] );
	
}

DLEFiles::init( $config['file_driver'], false );

$location_sel = array ('0' => '', '1' => '' );

if( $config['file_driver'] ) {
	
	$disk = 'remote';
	$location = isset($_REQUEST['location']) ? intval($_REQUEST['location']) : '1';
	
	if(!$location) $disk = 'local';
	
} else $disk = 'local';


$max_file_size = (int)$config['max_up_size'] * 1024;
$allowed_extensions = array ("gif", "jpg", "png", "jpeg", "webp" , "bmp", "avif");
$simple_ext = implode( "', '", $allowed_extensions );

if( $disk == 'local' ) {

	if( isset($_REQUEST['location']) ) $url_location = "&location=0"; else $url_location = '';

	$upload_on_local = '"local_driver" : "1",';
	
} else {

	$url_location = $upload_on_local = "";
	
}


if ( $path == "files" ) {
	msg( "error", $lang['addnews_denied'], $lang['index_denied'] );
}

if( $action == "createfolder" ) {
	
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}
	
	if( isset( $_REQUEST['folder'] ) AND (string)$_REQUEST['folder'] ) {
		
		$folder = cleanpath( $_REQUEST['folder'] );
		
		DLEFiles::CreateDirectory( $userdir . "/". $folder, $disk );
	
	}
	
	header( "Location: ?mod=files&userdir={$userdir}{$url_location}" );
	die();
	
}

if( $action == "doimagedelete" ) {
	
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		
		die( "Hacking attempt! User not found" );
	
	}

	if( !isset( $_POST['images'] ) AND !isset($_POST['folders']) ) {
		msg( "error", $lang['images_delerr'], $lang['images_delerr_1'], 'javascript:history.go(-1)' );
	}

	if( isset( $_POST['images'] ) AND is_array($_POST['images']) AND count($_POST['images']) ) {
		
		foreach ( $_POST['images'] as $image ) {
	
			$image = totranslit($image);
	
			if( $image ) {
				
				if( stripos ( $image, ".htaccess" ) !== false ) die("Hacking attempt!");
		
				$img_name_arr = explode( ".", $image );
				$type = totranslit( end( $img_name_arr ) );
		
				if( !in_array( $type, $allowed_extensions ) ) die("Hacking attempt!");
	
				DLEFiles::Delete( $userdir . "/". $image, $disk );
				DLEFiles::Delete( $userdir . "/thumbs/". $image, $disk );
				DLEFiles::Delete( $userdir . "/medium/". $image, $disk );
		
				$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '37', '{$image}')" );
		
			}
		}
	
	}
	
	if( isset( $_POST['folders'] ) AND is_array($_POST['folders']) AND count($_POST['folders']) ) {
		
		
		foreach ( $_POST['folders'] as $folder ) {
			
			$folder = cleanpath( $folder );
			
			if( !$userdir ){
				
				$not_allowed = array("files", "posts", "fotos", "shared", "icons");
				
				if( in_array( $folder, $not_allowed ) ) {
					msg( "error", $lang['images_delerr'], $lang['images_delerr_2'], 'javascript:history.go(-1)' );
				}
			
			}
		
			DLEFiles::DeleteDirectory( $userdir . "/". $folder, $disk );
			
		}
	
	}
	
}

	$js_array[] = "engine/classes/uploads/html5/fileuploader.js";
	$js_array[] = "engine/classes/highslide/highslide.js";

	$files = DLEFiles::ListDirectory( $path, $allowed_extensions, $disk );
	
	if( DLEFiles::$error ) {
		msg( "error", $lang['addnews_denied'], DLEFiles::$error );
	}

	echoheader( "<i class=\"fa fa-file-image-o position-left\"></i><span class=\"text-semibold\">{$lang['header_f_1']}</span>", $lang['header_f_2'] );
	
	$folder_list = array();

	if( $userdir ) {
		$prev_link = explode("/", $userdir);
		array_pop($prev_link);
		$prev_link = implode("/", $prev_link);
		
		$folder_list[] = <<<HTML
<div class="folder-preview-card">
	<div class="file-content" onclick="document.location='?mod=files&userdir={$prev_link}{$url_location}'; return false;" return false;">
		<i class="fa fa-arrow-circle-o-left text-slate-600\"></i>
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info">{$lang['files_prev']}</div>
		</div>
	</div>
</div>
HTML;

	}
	
	foreach ( $files['dirs'] as $entryname) {
		
		$folder = $entryname['name'];
		
		if( $userdir ) $link = $userdir."/".$folder; else $link = $folder;
		
		if($link == "files") continue;
		if($link == "posts") $folder = $lang['images_news'];
		if($link == "fotos") $folder = $lang['images_foto'];
		if($link == "shared") $folder = $lang['images_shared'];
		if($link == "icons") $folder = $lang['images_icons'];
		
		$not_allowed = array("files", "posts", "fotos", "shared", "icons");
		
		if( !in_array( $link, $not_allowed ) ) {
			$del_label ="<label><input type=\"checkbox\" class=\"icheck\" name=\"folders[]\" value=\"{$entryname['name']}\" ></label>";
		} else $del_label = '';
				
		$folder_list[] = <<<HTML
<div class="folder-preview-card">
	<div class="file-content">
		<img src="engine/skins/images/folder.png" class="file-preview-image" onclick="document.location='?mod=files&userdir={$link}{$url_location}'; return false;">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info">{$folder}</div>
		</div>
	</div>
	{$del_label}
</div>
HTML;

	}

	$total_size = 0;
	
	foreach ( $files['files'] as $entryname ) {
		
		$file = $entryname['name'];
		
		$total_size += $entryname['size'];
		
		if ( $disk == 'remote' ) {
			$http_url = $config['remote_url'];
		} else {
			$http_url = $config['http_home_url'] . "uploads/";
		}
			
		if($userdir) {
			$img_url = $http_url . $userdir . "/" . $entryname['name'];	
		} else {
			$img_url = $http_url . $entryname['name'];
		}
		
		$size = formatsize( $entryname['size'] );
		
		$folder_list[] = <<<HTML
<div class="file-preview-card">
	<div class="file-content">
		<img src="{$img_url}" class="file-preview-image" onclick="hs.expand(this, {src: '{$img_url}'}); return false;">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info">{$entryname['name']}</div>
			<div class="file-size-info">({$size})</div>
		</div>
	</div>
	<label><input type="checkbox" class="icheck" name="images[]" value="{$entryname['name']}" ></label>
</div>
HTML;
	
	}
	
	$folder_list = implode('', $folder_list);
	
	if( $total_size ) {
		$total_size = formatsize( $total_size );
		$total_size = "<div class=\"mt-20\"><span class=\"position-left\">{$lang['images_size']}</span>{$total_size}</div>";
	} else $total_size = "";

	if( $config['file_driver'] ) {

		$location_sel[$location] = 'selected="selected"';
	
		$storage_list = <<<HTML
	<div class="heading-elements">
		<div class="form-group has-feedback">
			<span class="position-left">{$lang['select_storage']}</span>
			<select class="uniform" name="location" onchange="changeLocation(this.value)">
				<option value="0" {$location_sel[0]}>{$lang['select_storage_1']}</option>
				<option value="1" {$location_sel[1]}>{$lang['select_storage_2']}</option>
			</select>
		</div>
	</div>
HTML;

	} else {
		$storage_list = '';
	}
	
	echo <<<HTML
<form action="" method="post" name="delimages" id="delimages">
<div class="panel panel-default">
  <div class="panel-heading">
    {$lang['uploaded_file_list']}
	{$storage_list}
  </div>
	
  <div class="panel-body row-seamless">
	<div class="file-list">
	{$folder_list}
	</div>
	{$total_size}
  </div>
HTML;


	echo "<div class=\"panel-footer\">
		<div id=\"file-uploader\" style=\"width:210px;float:left;\"></div>
		<button onclick=\"createfolder(); return false;\" class=\"btn bg-slate-600 btn-sm btn-raised\"><i class=\"fa fa-folder-o position-left\"></i>{$lang['btn_folder']}</button>
		<div style=\"float:right;\"><button class=\"btn bg-danger btn-sm btn-raised\" onclick=\"delete_file(); return false;\">{$lang['images_del']}</button><input type=\"hidden\" name=\"action\" value=\"doimagedelete\"><input type=\"hidden\" name=\"user_hash\" value=\"$dle_login_hash\" /></div>
	</div>";

	$max_file_size = number_format($max_file_size, 0, '', '');

	echo <<<HTML
   </div>
</form>
<script>
function changeLocation(value){

	document.location='?mod=files&location='+value;

}

jQuery(function($){

	var totaladded = 0;
	var totaluploaded = 0;

	var uploader = new qq.FileUploader({
		element: document.getElementById('file-uploader'),
		action: 'engine/ajax/controller.php?mod=upload',
		maxConnections: 1,
		encoding: 'multipart',
		allowdrop: false,
        sizeLimit: {$max_file_size},
		allowedExtensions: ['{$simple_ext}'],
	    params: {"subaction" : "upload", "news_id" : "0", "area" : "adminupload", "userdir" : "{$userdir}",{$upload_on_local} "user_hash" : "{$dle_login_hash}"},
        template: '<div class="qq-uploader">' + 
                '<div class="qq-upload-button btn bg-teal btn-sm btn-raised position-left" style="width: auto;">{$lang['media_upload_st14']}</div>' +
                '<ul class="qq-upload-list" style="display:none;"></ul>' + 
             '</div>',
		onSubmit: function(id, fileName) {

					totaladded ++;

					$('<div id="uploadfile-'+id+'" class="file-box mt-20"><span class="qq-upload-file">{$lang['media_upload_st6']}<b>&nbsp;'+fileName+'&nbsp;</b></span><span class="qq-status"><span class="qq-upload-spinner"></span><span class="qq-upload-size"></span></span></div>').appendTo('.panel-body.row-seamless');

        },
		onProgress: function(id, fileName, loaded, total){
					$('#uploadfile-'+id+' .qq-upload-size').text(uploader._formatSize(loaded)+' {$lang['media_upload_st8']} '+uploader._formatSize(total));
		},
		onComplete: function(id, fileName, response){
						totaluploaded ++;

						if ( response.success ) {

							$('#uploadfile-'+id+' .qq-status').html('{$lang['media_upload_st9']}');

							if (totaluploaded == totaladded ) setTimeout("location.replace(window.location)", 1000);


						} else {
						
							$('#uploadfile-'+id+' .qq-status').html('{$lang['media_upload_st10']}');

							if( response.error ) $('#uploadfile-'+id+' .qq-status').append( '<br /><span class="text-danger">' + response.error + '</span>' );

							setTimeout(function() {
								$('#uploadfile-'+id).fadeOut('slow');
							}, 10000);
						}
		},
        messages: {
            typeError: "{$lang['media_upload_st11']}",
            sizeError: "{$lang['media_upload_st12']}",
            emptyError: "{$lang['media_upload_st13']}"
        },
		debug: false
    });

    hs.graphicsDir = '{$config['http_home_url']}engine/classes/highslide/graphics/';
	hs.numberOfImagesToPreload = 0;
	hs.captionEval = 'this.thumb.alt';
	hs.showCredits = false;
	hs.transitions = ['expand', 'crossfade'];
	hs.wrapperClassName = 'less';
	hs.outlineType = null;

});

function delete_file() {
	DLEconfirm( '{$lang['delete_selected']}', '{$lang['p_info']}', function () {
		document.delimages.submit();
	} );
};

function createfolder( ){

	DLEprompt("{$lang['folder_enter']}", '', "{$lang['p_prompt']}", function (folder) {

		document.location='?mod=files&user_hash={$dle_login_hash}&userdir={$userdir}{$url_location}&action=createfolder&folder='+folder;

	});

};

</script>
HTML;

	echofooter();

?>