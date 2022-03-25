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
 File: upload.class.php
-----------------------------------------------------
 Use: upload files on server
=====================================================
*/

if( !defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

class UploadFileViaFTP {  

	private $path_file = "";
	private $file_name = "";
	
	public $error_code = false;
	public $force_replace = false;

    function saveFile($path, $filename, $prefix=true, $force_prefix = false) {

        if( !DLEFiles::FileExists( "files/" . $this->path_file . $filename ) ){
            return false;
        }

        return $this->path_file . $filename;
    }

    function getFileName() {
		global $config;
	
		$path = trim(str_replace(chr(0), '', (string)$_POST['ftpurl']));
		$path = str_replace(array('/', '\\'), '/', $path);

		if( !$path ) return '';
		
		if (preg_match('#\p{C}+#u', $path)) {
			return '';
		}
	
		$path_parts = pathinfo( $path );

		$this->file_name = $path_parts['basename'];
		
		$parts = array_filter(explode('/', $path_parts['dirname']), 'strlen');
		
		$absolutes = array();
		
		foreach ($parts as $part) {
			$part = trim($part);
			
			if ('.' == $part OR '..' == $part OR !$part) continue;
			
			$absolutes[] = $part;
		}
	
		$path = implode('/', $absolutes);
	
		if ( $path ) {
			$this->path_file = $path.'/';
		}

		return $this->file_name;
	
    }


    function getFileSize() {

		return DLEFiles::Size( "files/" . $this->path_file . $this->file_name );

    }

    function getErrorCode() {

		return false;

    }
	
    function getImage() {
        return "files/" . $this->path_file . $this->file_name;
    }
	
}

class UploadFileViaURL {  

	private $from = "";
	
	public $error_code = false;
	public $force_replace = false;
	
    function saveFile($path, $filename, $auto_prefix = true, $force_prefix = false) {

		$file_prefix = "";
	
		if ( ($auto_prefix AND DLEFiles::FileExists( $path.$filename ) ) OR $force_prefix ) {

			$file_prefix = time()."_";

		}

		$filename = totranslit( $file_prefix.$filename );

		if( !DLEFiles::$error ) {
			
			$stream = @fopen( $this->from , 'r');
			
			if (is_resource($stream)) {
				
				DLEFiles::WriteStream( $path.$filename, $stream);
				
			} else {
				
				DLEFiles::$error = 'PHP Error: Unable to open the stream with uploaded file';
				return false;
			
			}
			
			if (is_resource($stream)) {
				fclose($stream);
			}
			
			if( DLEFiles::$error ) return false;

		} else return false;

        return $filename;
    }
	
    function getFileName() {
		global $config;

		$imageurl = trim( strip_tags( $_POST['imageurl'] ) );
		$imageurl = str_replace(chr(0), '', $imageurl);
		$imageurl = str_replace( "\\", "/", $imageurl );

		$url = @parse_url ( $imageurl );

        if (!array_key_exists('host', $url)) {
            return '';
        }

		if($url['scheme'] != 'http' AND $url['scheme'] != 'https') {

            return '';
		}

		if($url['host'] == 'localhost' OR $url['host'] == '127.0.0.1') {

            return '';
		}

		if( stripos ( $url['host'], $_SERVER['HTTP_HOST'] ) !== false ) {

			return '';

		}

		if( stripos( $imageurl, ".php" ) !== false ) return '';
		if( stripos( $imageurl, ".phtm" ) !== false ) return '';

		$this->from = $imageurl;

		$imageurl = explode( "/", $imageurl );
		$imageurl = end( $imageurl );

        return $imageurl;
    }
	
    function getFileSize() {

		$url = @parse_url( $this->from );

		if ( $url ) {
			
			if($url['scheme'] == "https" ) $port = 443; else $port = 80;

			$fp = @fsockopen( $url['host'], $port, $errno, $errstr, 10);

			if ($fp) {
				$x='';
	
				fputs($fp,"HEAD {$url['path']} HTTP/1.0\nHOST: {$url['host']}\n\n");
				while(!feof($fp)) $x.=fgets($fp,128);
				fclose($fp);

				if ( preg_match("#Content-Length: ([0-9]+)#i",$x,$size) ) {
					return intval($size[1]);
				} else {
					return strlen(@file_get_contents($this->from));
				}

			}

		}
		
		return 0;

    }
	
    function getImage() {
        return $this->from;
    }
	
    function getErrorCode() {

		return false;

    }
}

class UploadFileViaForm {
	
	public $error_code = false;
	public $force_replace = false;

    function saveFile($path, $filename, $auto_prefix = true, $force_prefix = false) {

		$file_prefix = "";
	
		if ( ($auto_prefix AND DLEFiles::FileExists( $path.$filename ) ) OR $force_prefix ) {

			$file_prefix = time()."_";

		}

		$filename = totranslit( $file_prefix.$filename );

		if( !DLEFiles::$error ) {
			
			$stream = @fopen( $_FILES['qqfile']['tmp_name'] , 'r');
			
			if (is_resource($stream)) {
				
				DLEFiles::WriteStream( $path.$filename, $stream);
				
			} else {
				
				DLEFiles::$error = 'PHP Error: Unable to open the stream with uploaded file';
				return false;
			
			}
			
			if (is_resource($stream)) {
				fclose($stream);
			}
			
			if( DLEFiles::$error ) return false;

		} else return false;

        return $filename;
    }
	
    function getFileName() {

		$path_parts = @pathinfo($_FILES['qqfile']['name']);

        return $path_parts['basename'];

    }
	
    function getFileSize() {
        return $_FILES['qqfile']['size'];
    }
	
    function getImage() {
        return array( 'tmp_name' => $_FILES['qqfile']['tmp_name'],  'name' => $this->getFileName() );
    }
	
    function getErrorCode() {

		$error_code = $_FILES['qqfile']['error'];

		if ($error_code !== UPLOAD_ERR_OK) {

		    switch ($error_code) { 
		        case UPLOAD_ERR_INI_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
		        case UPLOAD_ERR_FORM_SIZE: 
		            $error_code = 'PHP Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
		        case UPLOAD_ERR_PARTIAL: 
		            $error_code = 'PHP Error: The uploaded file was only partially uploaded'; break;
		        case UPLOAD_ERR_NO_FILE: 
		            $error_code = 'PHP Error: No file was uploaded'; break;
		        case UPLOAD_ERR_NO_TMP_DIR: 
		            $error_code = 'PHP Error: Missing a PHP temporary folder'; break;
		        case UPLOAD_ERR_CANT_WRITE: 
		            $error_code = 'PHP Error: Failed to write file to disk'; break;
		        case UPLOAD_ERR_EXTENSION: 
		            $error_code = 'PHP Error: File upload stopped by extension'; break;
		        default: 
		            $error_code = 'Unknown upload error';  break;
		    } 

		} else return false;

        return $error_code;
    }
}

class FileUploader {

	private $allowed_extensions = array ("gif", "jpg", "jpeg", "png", "webp", "bmp", "avif");
	private $allowed_video = array ("mp4", "mp3", "m4v", "m4a", "mov", "webm", "m3u8", "mkv" );
	private $allowed_files = array();
	private $area = "";
	private $author = "";
	private $news_id = "";
	private $t_size = "";
	private $t_seite = 0;
	private $make_thumb = true;
	private $m_size = "";
	private $m_seite = 0;
	private $make_medium = false;
	private $make_watermark = true;
	private $upload_path = "posts/";

    function __construct($area, $news_id, $author, $t_size, $t_seite, $make_thumb = true, $make_watermark = true, $m_size = 0, $m_seite = 0, $make_medium = false){        
		global $config, $db, $member_id, $user_group;

        $this->area = totranslit($area);

		if ( $this->area == "adminupload" ) {

			if (!isset($_FILES['qqfile']) OR $member_id['user_group'] != 1) die( "Hacking attempt!" );

			if( isset($_REQUEST['userdir']) AND $_REQUEST['userdir']) $userdir = cleanpath( $_REQUEST['userdir'] ). "/"; else $userdir = "";
			if( isset($_REQUEST['subdir']) AND $_REQUEST['subdir']) $subdir = cleanpath( $_REQUEST['subdir'] ). "/"; else $subdir = "";

			$this->upload_path = $userdir.$subdir;

		} else {

	        $this->allowed_files = explode( ',', strtolower( $user_group[$member_id['user_group']]['files_type'] ) );
		}

        $this->author = $db->safesql( $author );
        $this->news_id = intval($news_id);
        $this->t_size = $t_size;
        $this->t_seite = $t_seite;
        $this->make_thumb = $make_thumb;
        $this->m_size = $m_size;
        $this->m_seite = $m_seite;
        $this->make_medium = $make_medium;
        $this->make_watermark = $make_watermark;
		$ftp_upload_flag = false;
      
        if (isset($_FILES['qqfile'])) {

            $this->file = new UploadFileViaForm();

        } elseif ( isset($_POST['imageurl']) AND $_POST['imageurl'] ) {

            $this->file = new UploadFileViaURL();

        } elseif ( $member_id['user_group'] == 1 AND isset($_POST['ftpurl']) AND $_POST['ftpurl'] ) {

            $this->file = new UploadFileViaFTP();
			$ftp_upload_flag = true;
        } else {

            $this->file = false; 

        }

		if ($ftp_upload_flag OR $this->area == "adminupload" )
			define( 'FOLDER_PREFIX', "" );
		else
			define( 'FOLDER_PREFIX', date( "Y-m" )."/" );

    }

	private function check_filename ( $filename ) {
		
		$filename = (string)$filename;
		
		if( !$filename ) return false;
			
		$filename = str_replace(chr(0), '', $filename);
		$filename = str_replace( "\\", "/", $filename );
		$filename = preg_replace( '#[.]+#i', '.', $filename );
		$filename = str_replace( "/", "", $filename );
		$filename = str_ireplace( "php", "", $filename );

		$filename_arr = explode( ".", $filename );
		
		if(count($filename_arr) < 2) {
			return false;
		}
		
		$type = totranslit( end( $filename_arr ) );
		
		if(!$type) return false;
		
		$curr_key = key( $filename_arr );
		
		unset( $filename_arr[$curr_key] );

		$filename = totranslit( implode( "_", $filename_arr ) );
		
		if( !$filename ) {
			$filename = time() + rand( 1, 100 );
		}
		
		$filename = $filename . "." . $type;

		$filename = preg_replace( '#[.]+#i', '.', $filename );

		if( stripos ( $filename, ".php" ) !== false ) return false;
		if( stripos ( $filename, ".phtm" ) !== false ) return false;
		if( stripos ( $filename, ".shtm" ) !== false ) return false;
		if( stripos ( $filename, ".htaccess" ) !== false ) return false;
		if( stripos ( $filename, ".cgi" ) !== false ) return false;
		if( stripos ( $filename, ".htm" ) !== false ) return false;
		if( stripos ( $filename, ".ini" ) !== false ) return false;

		if( stripos ( $filename, "." ) === 0 ) return false;
		if( stripos ( $filename, "." ) === false ) return false;
		
		if( strlen( $filename ) > 200 ) {
			return false;
		}

		return $filename;

	}

	private function msg_error($message, $code = 500) {
		
		return json_encode(array('error' => $message ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	
	}
	
	function FileUpload(){
		global $config, $db, $lang, $member_id, $user_group;

		$_IP = get_ip();
		$added_time = time();
		$xfvalue = "";
		$driver = intval($config['file_driver']);
		
		if (substr ( $config['remote_url'], - 1, 1 ) != '/') $config['remote_url'] .= '/';
		
		if( $driver AND stripos($config['remote_url'], "https://" ) !== 0 AND stripos($config['remote_url'], "http://" ) !== 0 AND stripos($config['remote_url'], "//" ) !== 0 ) {
			return $this->msg_error( $lang['upload_error_8'] );
		}
		
		if (!$this->file){
			return $this->msg_error( $lang['upload_error_3'] );
        }

		$filename = $this->check_filename( $this->file->getFileName() );

		if (!$filename){
			return $this->msg_error( $lang['upload_error_4'] );
        }

		$filename_arr = explode( ".", $filename );
		$type = end( $filename_arr );

		if (!$type){
			return $this->msg_error( $lang['upload_error_4'] );
        }

		$error_code = $this->file->getErrorCode();

		if ( $error_code ){
			return $this->msg_error( $error_code );
        }
		
		$size = $this->file->getFileSize();
		
        if (!$size) {
            return $this->msg_error( $lang['upload_error_5'] );
        }
			
		if( $config['files_allow'] AND $user_group[$member_id['user_group']]['allow_file_upload'] AND in_array($type, $this->allowed_files ) ) {

			if( intval( $user_group[$member_id['user_group']]['max_file_size'] ) AND $size > ($user_group[$member_id['user_group']]['max_file_size'] * 1024) ) {
				
				return $this->msg_error( $lang['files_too_big'] );
			
			}

			if( $this->area != "template" AND $user_group[$member_id['user_group']]['max_files'] ) {
				
				$row = $db->super_query( "SELECT COUNT(*) as count  FROM " . PREFIX . "_files WHERE author = '{$this->author}' AND news_id = '{$this->news_id}'" );
				$count_files = $row['count'];
		
				if ($count_files AND $count_files >= $user_group[$member_id['user_group']]['max_files'] ) return $this->msg_error( $lang['error_max_files'] );
		
			}
			
			if ( isset($_REQUEST['public_file']) AND $_REQUEST['public_file'] ) $is_public = 1; else $is_public = 0;
			
			if( $user_group[$member_id['user_group']]['allow_public_file_upload'] AND $is_public) {
				$this->upload_path = "public_files/";
				$auto_prefix = true;
				$force_prefix = false;
			} else {
				$this->upload_path = "files/";
				$is_public = 0;
				$auto_prefix = false;
				$force_prefix = true;
			}

			if ( !$config['files_remote'] ) $driver = 0;
			
			DLEFiles::init( $driver, $config['local_on_fail'] );
			
			$uploaded_filename = $this->file->saveFile($this->upload_path . FOLDER_PREFIX, $filename, $auto_prefix, $force_prefix);

			if ( DLEFiles::$error ){
				return $this->msg_error( DLEFiles::$error );
			}
			
			if ( !$uploaded_filename ){
				return $this->msg_error( $lang['images_uperr_3'] );
			}

			$added_time = time();
			$file_link = $config['http_home_url'] . "engine/skins/images/all_file.png";
			$data_url = "#";
			$file_play = "";
			$size = DLEFiles::Size( $this->upload_path . FOLDER_PREFIX . $uploaded_filename );

			if ( $driver AND !DLEFiles::$remote_error ) {
				$http_url = $config['remote_url'];
				$md5 = md5( $size );
			} else {
				$http_url = $config['http_home_url'] . "uploads/";
				$md5 = md5_file( ROOT_DIR . "/uploads/" . $this->upload_path. FOLDER_PREFIX . $uploaded_filename );
				$driver = 0;
			}

			if ($user_group[$member_id['user_group']]['allow_admin']) $db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$added_time}', '{$_IP}', '36', '{$uploaded_filename}')" );

			if( in_array( $type, $this->allowed_video ) ) {
			
				if( $type == "mp3" ) {
						
					$file_link = $config['http_home_url'] . "engine/skins/images/mp3_file.png";
					$file_play = "audio";
	
				} else {
						
					$file_link = $config['http_home_url'] . "engine/skins/images/video_file.png";
					$file_play = "video";
				}
				
				$data_url = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
				
			}

			if( $user_group[$member_id['user_group']]['allow_public_file_upload'] AND $is_public) {
				$data_url = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
			}
			
			if( $this->area == "template" ) {
				
				$db->query( "INSERT INTO " . PREFIX . "_static_files (static_id, author, date, name, onserver, size, checksum, driver, is_public) values ('{$this->news_id}', '{$this->author}', '{$added_time}', '{$filename}', '". FOLDER_PREFIX ."{$uploaded_filename}', '{$size}', '{$md5}', '{$driver}', '{$is_public}')" );
				$id = $db->insert_id();
				$del_name = 'static_files';
			
			} else {
				
				$db->query( "INSERT INTO " . PREFIX . "_files (news_id, name, onserver, author, date, size, checksum, driver, is_public) values ('{$this->news_id}', '{$filename}', '". FOLDER_PREFIX ."{$uploaded_filename}', '{$this->author}', '{$added_time}', '{$size}', '{$md5}', '{$driver}', '{$is_public}')" );
				$id = $db->insert_id();
				$del_name = "files";
			
			}
			$size = formatsize($size);
			
$return_box = <<<HTML
<div class="file-preview-card" data-type="file" data-area="{$del_name}" data-deleteid="{$id}" data-url="{$data_url}" data-path="{$id}:{$filename}" data-play="{$file_play}" data-public="{$is_public}">
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content">
		<img src="{$file_link}" class="file-preview-image">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="ID: {$id}, {$filename}">{$filename}</div>
			<div class="file-size-info">({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview"><a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a></div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;

			if( $this->area == "xfieldsfile" ) {
				
				$return_box = "&nbsp;<button class=\"qq-upload-button btn btn-sm bg-danger btn-raised\" onclick=\"xffiledelete('".$_REQUEST['xfname']."','".$id."');return false;\">{$lang['xfield_xfid']}</button>";
				
				if( $is_public ) {
					$xfvalue = $data_url;
				} else {
					$xfvalue = "[attachment={$id}:{$filename}]";
				}
				
			}

		} elseif ( in_array( $type, $this->allowed_extensions ) AND $user_group[$member_id['user_group']]['allow_image_upload'] ) {

			$tinypng_error = false;
			$min_size_upload = true;
			
			if( $this->area == "comments" AND !$config['comments_remote'] ) $driver = 0;
			elseif ( $this->area == "template" AND !$config['static_remote'] ) $driver = 0;
			elseif ( $this->area == "adminupload" AND isset($_REQUEST['local_driver']) AND $_REQUEST['local_driver']) $driver = 0;
			elseif ( !$config['image_remote'] ) $driver = 0;
	
			DLEFiles::init( $driver, $config['local_on_fail'] );
			
			if( intval( $config['max_up_size'] ) AND $size > ($config['max_up_size'] * 1024) ) {
				
				return $this->msg_error( $lang['images_big'] );
			
			}

			if( $this->area != "template" AND $this->area != "adminupload" AND $this->area != "comments" AND $user_group[$member_id['user_group']]['max_images'] ) {
				
				$row = $db->super_query( "SELECT images  FROM " . PREFIX . "_images WHERE author = '{$this->author}' AND news_id = '{$this->news_id}'" );
				if ($row['images']) $count_images = count(explode( "|||", $row['images'] )); else $count_images = false;		
				if( $count_images AND $count_images >= $user_group[$member_id['user_group']]['max_images'] ) return $this->msg_error( $lang['error_max_images'] );
				
			}
			
			if( $this->area == "comments" AND $user_group[$member_id['user_group']]['up_count_image'] ) {
				
				$row = $db->super_query( "SELECT COUNT(*) as count  FROM " . PREFIX . "_comments_files WHERE c_id = '{$this->news_id}' AND author = '{$this->author}'" );
		
				if( $row['count'] >= $user_group[$member_id['user_group']]['up_count_image'] ) return $this->msg_error( $lang['error_max_images'] );
				
			}

			if(  $this->area == "adminupload" AND DLEFiles::FileExists( $this->upload_path . FOLDER_PREFIX . $filename ) ) {
				
				return $this->msg_error( $lang['images_uperr_4'] );

			}
			
			if( $this->area == "adminupload" ){
				$min_size_upload = false;
			}

			$image = new thumbnail( $this->file->getImage(), true, $min_size_upload );
			
			if ( $image->error ){
				return $this->msg_error( $image->error );
			}
			
			if( $config['max_up_side'] ) $image->size_auto( $config['max_up_side'], $config['o_seite'] );
			
			if( $this->make_watermark ) $image->insert_watermark( $config['max_watermark'] );
			
			$dimension = $image->width."x".$image->height;
			
			if( $member_id['user_group'] != 1 OR $image->re_save ) {
				
				$uploaded_filename = $image->save($this->upload_path . FOLDER_PREFIX . $filename, true );

			} else {
				
				$uploaded_filename = $this->file->saveFile($this->upload_path . FOLDER_PREFIX, $filename, true);
				
			}
			
			if ( $image->error ){
				return $this->msg_error( $image->error );
			}
			
			if ( DLEFiles::$error ){
				return $this->msg_error( DLEFiles::$error );
			}
			
			if ( !$uploaded_filename ){
				return $this->msg_error( $lang['images_uperr_3'] );
			}

			$size = formatsize( DLEFiles::Size( $this->upload_path . FOLDER_PREFIX . $uploaded_filename ) );
			$thumb_data = 0;
			$added_time = time();
		
			if( $this->make_thumb ) {
				
				if( $image->size_auto( $this->t_size, $this->t_seite ) ) {
					
					if( $this->make_watermark ) $image->insert_watermark( $config['max_watermark'] );
					
					$image->save( $this->upload_path . FOLDER_PREFIX . "thumbs/" . $uploaded_filename, false );
					
					$thumb_data = 1;
					
				}
				
				if ( $image->error ){
					return $this->msg_error( $image->error );
				}
			
			}

			$medium_data = 0;
			
			if( $this->make_medium ) {
				
				if( $image->size_auto( $this->m_size, $this->m_seite ) ) {
					
					if( $this->make_watermark ) $image->insert_watermark( $config['max_watermark'] );
					
					$image->save( $this->upload_path . FOLDER_PREFIX . "medium/" . $uploaded_filename, false );
					
					$medium_data = 1;
					
				}
				
				if ( $image->error ){
					return $this->msg_error( $image->error );
				}
				
			}
			
			if( $image->tinypng_error ) $tinypng_error = $image->tinypng_error;
			
			if ( $driver AND !DLEFiles::$remote_error ) {
				
				$http_url = $config['remote_url'];
				$insert_image = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
				
			} else {
				
				$http_url = $config['http_home_url'] . "uploads/";
				$insert_image = FOLDER_PREFIX . $uploaded_filename;
				$driver = 0;
				
			}

			$insert_image .= "|{$thumb_data}|{$medium_data}|{$dimension}|{$size}"; 
			
			if( $this->area != "template" AND $this->area != "adminupload" AND $this->area != "comments") {
				
				$row = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_images WHERE news_id = '{$this->news_id}' AND author = '{$this->author}'" );
				
				if( !$row['count'] ) {
					
					$db->query( "INSERT INTO " . PREFIX . "_images (images, author, news_id, date) values ('{$insert_image}', '{$this->author}', '{$this->news_id}', '{$added_time}')" );
				
				} else {
					
					$update_images = true;
					
					$row = $db->super_query( "SELECT images  FROM " . PREFIX . "_images WHERE news_id = '{$this->news_id}' AND author = '{$this->author}'" );
					
					$listimages = array ();
					$update_images = true;
					
					if( $row['images'] ) {
						
						$listimages = explode( "|||", $row['images'] );
						
						foreach ( $listimages as $file_image ) {
							
							$file_image = get_uploaded_image_info( $file_image );
							
							if( $file_image->path == FOLDER_PREFIX . $uploaded_filename ) $update_images = false;
						
						}
					}
					
					if( $update_images ) {
						
						$listimages[] = $insert_image;
						$listimages = implode( "|||", $listimages );
						
						$db->query( "UPDATE " . PREFIX . "_images SET images='{$listimages}' WHERE news_id = '{$this->news_id}' AND author = '{$this->author}'" );
						
					}
				}
			}

			if( $this->area == "template" ) {
				
				$row = $db->super_query( "SELECT id  FROM " . PREFIX . "_static_files WHERE static_id = '{$this->news_id}' AND name LIKE '%"  .FOLDER_PREFIX . $uploaded_filename . "%'" );
				
				if( isset($row['id']) AND $row['id']) {
					
					$id = $row['id'];
					
				} else {
					
					$db->query( "INSERT INTO " . PREFIX . "_static_files (static_id, author, date, name, driver) values ('{$this->news_id}', '{$this->author}', '{$added_time}', '{$insert_image}', '{$driver}')" );
					$id = $db->insert_id();
				
				}

			}

			if( $this->area == "comments" ) {
				
				$row = $db->super_query( "SELECT id  FROM " . PREFIX . "_comments_files WHERE c_id = '{$this->news_id}' AND name LIKE '%"  .FOLDER_PREFIX . $uploaded_filename . "%'" );
				
				if( isset($row['id']) AND $row['id']) {
					
					$id = $row['id'];
					
				} else {
					
					$db->query( "INSERT INTO " . PREFIX . "_comments_files (c_id, author, date, name, driver) values ('{$this->news_id}', '{$this->author}', '{$added_time}', '{$insert_image}', '{$driver}')" );
					$id = $db->insert_id();
				
				}
	
			}
			
			if ($user_group[$member_id['user_group']]['allow_admin']) $db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$added_time}', '{$_IP}', '36', '{$uploaded_filename}')" );
			
			$img_url = $data_url = $link = $flink = $http_url . $this->upload_path . FOLDER_PREFIX . $uploaded_filename;
			$image_path = FOLDER_PREFIX . $uploaded_filename;

			if( $medium_data ) {
				
				$img_url = 	$http_url . $this->upload_path . FOLDER_PREFIX . "medium/" . $uploaded_filename;
				$medium_data = "yes";
				$tm_url = $img_url;
				
			} else $medium_data = "no";

			if( $thumb_data ) {
				
				$img_url = 	$http_url . $this->upload_path . FOLDER_PREFIX . "thumbs/" . $uploaded_filename;
				$thumb_data = "yes";
				$th_url = $img_url;
				
			} else $thumb_data = "no";
			
			if($medium_data == "yes" ) $link = $tm_url;
			elseif( $thumb_data == "yes" ) $link = $th_url;
			else $flink = false;
			
			if( $this->area == "comments" OR $this->area == "template") {
				
				if( $this->area == "comments" ) {
					
					$del_name = 'comments_files';
					
				} else $del_name = 'static_files';

$return_box = <<<HTML
<div class="file-preview-card" data-type="image" data-area="{$del_name}" data-deleteid="{$id}" data-url="{$data_url}" data-path="{$image_path}" data-thumb="{$thumb_data}" data-medium="{$medium_data}">
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content">
		<img src="{$img_url}" class="file-preview-image">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="{$uploaded_filename}">{$uploaded_filename}</div>
			<div class="file-size-info">{$dimension} ({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview">
				<a onclick="document.activeElement.blur(); hs.expand(this); return false;" href="{$data_url}" rel="tooltip" title="{$lang['up_im_expand']}"><i class="mediaupload-icon mediaupload-icon-zoom"></i></a>
				<a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a>	
			</div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;
	
			} elseif( $this->area == "xfieldsimage" OR $this->area == "xfieldsimagegalery" ) {

				$xfvalue = $insert_image;
				$xf_id = md5($xfvalue);
				
				if( $this->area == "xfieldsimage" ) {
					
					$del_name = "xfimagedelete('".$_REQUEST['xfname']."','".FOLDER_PREFIX . $uploaded_filename."');return false;";
					
				} else $del_name = "xfimagegalerydelete_".md5($_REQUEST['xfname'])."('".$_REQUEST['xfname']."','".FOLDER_PREFIX . $uploaded_filename."', '".$xf_id."');return false;";
				
				$return_box = "<div id=\"xf_{$xf_id}\" data-id=\"{$xfvalue}\" data-alt=\"\" class=\"uploadedfile\"><div class=\"info\">{$uploaded_filename}</div><div class=\"uploadimage\"><img style=\"width:auto;height:auto;max-width:100px;max-height:90px;\" src=\"" . $img_url . "\" /></div><div class=\"info\"><a href=\"#\" onclick=\"xfaddalt('".$xf_id."', '".$_REQUEST['xfname']."');return false;\">{$lang['xf_img_descr']}</a><br><a href=\"#\" onclick=\"{$del_name}\">{$lang['xfield_xfid']}</a></div></div>";
				
			} else {

$return_box = <<<HTML
<div class="file-preview-card" data-type="image" data-area="images" data-deleteid="{$image_path}" data-url="{$data_url}" data-path="{$image_path}" data-thumb="{$thumb_data}" data-medium="{$medium_data}">
	<div class="active-ribbon"><span><i class="mediaupload-icon mediaupload-icon-ok"></i></span></div>
	<div class="file-content">
		<img src="{$img_url}" class="file-preview-image">
	</div>
	<div class="file-footer">
		<div class="file-footer-caption">
			<div class="file-caption-info" rel="tooltip" title="{$uploaded_filename}">{$uploaded_filename}</div>
			<div class="file-size-info">{$dimension} ({$size})</div>
		</div>
		<div class="file-footer-bottom">
			<div class="file-preview">
				<a onclick="document.activeElement.blur(); hs.expand(this); return false;" href="{$data_url}" rel="tooltip" title="{$lang['up_im_expand']}"><i class="mediaupload-icon mediaupload-icon-zoom"></i></a>
				<a class="clipboard-copy-link" href="#" rel="tooltip" title="{$lang['up_im_copy']}"><i class="mediaupload-icon mediaupload-icon-copy"></i></a>	
			</div>
			<div class="file-delete"><a class="file-delete-link" href="#"><i class="mediaupload-icon mediaupload-icon-trash"></i></a></div>
		</div>
	</div>
</div>
HTML;

			}

		} else return $this->msg_error( $lang['images_uperr_2'] );
		
		$return_array = array (
			'success' => true,
			'returnbox' => $return_box,
			'uploaded_filename' => $uploaded_filename,
			'xfvalue' => $xfvalue,
			'link' => $link,
			'flink' => $flink,
			'remote_error' => DLEFiles::$remote_error,
			'tinypng_error' => $tinypng_error
		);
		
		return json_encode($return_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	}

}

?>