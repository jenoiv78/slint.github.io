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
 File: filesystem.class.php
-----------------------------------------------------
 Use: DLE Files System
=====================================================
*/

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionProvider;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\FilesystemException;

if( !defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

abstract class DLEFiles {

	private static $root = null;
	
	private static $remote = null;
	private static $local = null;
	private static $local_on_remote_errors = null;
	
	public static $driver = null;	
	public static $error  = null;
	public static $remote_error = null;
	
	public static function init( $driver = null, $local_on_remote_errors = false, $root = null ) {
		global $config;
		
		self::$local = self::$remote = self::$error = self::$remote_error = self::$driver = self::$root = self::$local_on_remote_errors = null;
		
		if( is_null( $root ) ) {
			
			self::$root = ROOT_DIR.'/uploads/';
			
		} else {
			
			$root = self::normalize_path( $root );
			
			if( $root ) {
				self::$root = ROOT_DIR.'/'. $root .'/';
			} else {
				self::$root = ROOT_DIR.'/';
			}
			
		}
		
		if( is_null( $driver ) ) {
			
			$driver = intval($config['file_driver']);
			
		}
		
		$visibilityConverter = PortableVisibilityConverter::fromArray([
			'file' => [
				'public' => 0666,
				'private' => 0644
			],
			'dir' => [
				'public' => 0777,
				'private' => 0755
			],
		]);
			
		if( $driver AND $local_on_remote_errors ) {
			self::$local_on_remote_errors = true;
		}
			
		try {
			
			$adapter = new LocalFilesystemAdapter( self::$root, $visibilityConverter, LOCK_EX, LocalFilesystemAdapter::DISALLOW_LINKS );
			
			self::$local = new Filesystem($adapter, ['directory_visibility' => 'public', 'visibility' => 'public']);
			self::$driver = 'local';
		
		} catch(Throwable $e) {
			
				self::error( $e->getMessage() );
				return false;
			
		} catch (FilesystemException $e) {
				self::error( $e->getMessage() );
				return false;
		}

		if( $driver ) {
			
			try {
				
				$config['ftp_path'] = trim($config['ftp_path']);
				
				if( $config['ftp_path'] AND ($driver == '3' OR $driver == '4') )  {
					
					$config['ftp_path'] = trim($config['ftp_path'], '\\/');
					
				} elseif( $driver == '1' OR $driver == '2' ) {
					
					if(!$config['ftp_path']) $config['ftp_path'] = '/';
					else $config['ftp_path'] = '/'.trim($config['ftp_path'], '\\/').'/';
					
				}
				
				if( $driver == '1') {
					
					$adapter = new FtpAdapter(
						// Connection options
						FtpConnectionOptions::fromArray([
							'host' => $config['ftp_server'],
							'port' => intval($config['ftp_port']),
							'root' => $config['ftp_path'],
							'username' => $config['ftp_username'],
							'password' => $config['ftp_password'],
							'timeout' => 5
						]),
						null,
						null,
						$visibilityConverter
					);
					
				} elseif( $driver == '2') {
					
					$adapter = new SftpAdapter(
									new SftpConnectionProvider(
										$config['ftp_server'],
										$config['ftp_username'],
										$config['ftp_password'],
										null, // private key (optional, default: null) can be used instead of password, set to null if password is set
										null, // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
										intval($config['ftp_port']),
										false, // use agent (optional, default: false)
										5, // timeout (optional, default: 10)
										0, // max tries (optional, default: 4)
										null, // host fingerprint (optional, default: null),
										null
									),
									$config['ftp_path'],
									$visibilityConverter
					);
					
				} elseif( $driver == '3') {
					
					$clientoptions = ['version' => 'latest', 'use_aws_shared_config_files' => false];
					
					if(trim($config['remote_key_id']) AND trim($config['remote_secret_key'])) {
						
						$clientoptions['credentials'] = ['key' => trim($config['remote_key_id']), 'secret' => trim($config['remote_secret_key'])];

					}
					
					if(trim($config['region_name'])) {
						$clientoptions['region'] = trim($config['region_name']);
					}
					
					$client = new Aws\S3\S3Client($clientoptions);

					
					$adapter = new League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $config['bucket_name'], $config['ftp_path']);
					
				} elseif( $driver == '4' OR $driver == '5') {

					if( $driver == '4' ) {
						$clientoptions = ['version' => 'latest', 'use_aws_shared_config_files' => false, 'endpoint' => 'https://storage.yandexcloud.net'];
					} else {
						$clientoptions = ['version' => 'latest', 'use_aws_shared_config_files' => false, 'endpoint' => $config['remote_endpoint']];
					}
			
					if(trim($config['remote_key_id']) AND trim($config['remote_secret_key'])) {
						
						$clientoptions['credentials'] = ['key' => trim($config['remote_key_id']), 'secret' => trim($config['remote_secret_key'])];

					}
					
					if(trim($config['region_name'])) {
						$clientoptions['region'] = trim($config['region_name']);
					}
					
					$client = new Aws\S3\S3Client($clientoptions);

					
					$adapter = new League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $config['bucket_name'], $config['ftp_path']);
					
				} else {
					
					return false;
				
				}
				
				self::$remote = new Filesystem($adapter, ['directory_visibility' => 'public', 'visibility' => 'public']);
				self::$driver = 'remote';
				
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				return false;
				
			} catch (FilesystemException $e) {
					self::error( $e->getMessage() );
					return false;
			}
			
		}
		
		return true;
	
	}
	
	public static function Read( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->read($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->read($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
			}
		
		}
		
		return false;
		
	}
	
	public static function Save( $path, $contents, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;
		
		if( is_object(self::$remote) AND $driver == 'remote' ) {
			
			try {
				
				self::$remote->write($path, $contents);
				return true;
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				self::$local->write($path, $contents);
				return true;
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}

	public static function FileExists( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->fileExists($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;

			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->fileExists($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}

	public static function Size( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->fileSize($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->fileSize($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}
	
	public static function Delete( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->delete($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->delete($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}
	
	public static function ReadStream( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;
		
		if( is_object(self::$remote) AND $driver == 'remote' ) {
			
			try {
				
				return self::$remote->readStream($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->readStream($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}
	
	public static function WriteStream( $path, $stream, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;
		
		if( is_object(self::$remote) AND $driver == 'remote' ) {
			
			try {
				
				self::$remote->writeStream($path, $stream);
				return true;
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				self::$local->writeStream($path, $stream);
				return true;
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );

			}
		
		}
		
		return false;
		
	}
	
	public static function Rename( $source, $destination, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$source = self::normalize_path( $source );
		$destination = self::normalize_path( $destination );
		$driver = $driver ? $driver : self::$driver;
		
		if( is_object(self::$remote) AND $driver == 'remote' ) {
			
			try {
				
				self::$remote->move($source, $destination);
				return true;
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				self::$local->move($source, $destination);
				return true;
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				
			}
		
		}
		
		return false;
		
	}
	
	public static function MimeType( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;
		
		try {
			$detector = new League\MimeTypeDetection\ExtensionMimeTypeDetector();
			return $detector->detectMimeTypeFromPath($path);
		
		} catch(Throwable $e) {
				
			self::error( $e->getMessage() );
			
		} catch (FilesystemException $e) {
			
			self::error( $e->getMessage() );
			
		}
		
		return false;
		
	}
	
	public static function ListDirectory( $path, $allowed_ext = null, $driver = null, $recursive = false ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;
		$listing = array();

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {

				$listing = self::$remote->listContents($path)->sortByPath();
				
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				$listing = self::$local->listContents($path, $recursive)->sortByPath();
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				
			}
		
		}

		$array = array('dirs' => array(), 'files' => array());

		foreach ($listing as $item) {
			
			if( $path == $item->path() ) continue;
			
			$path_info = $item->path();
			
			$finfo = pathinfo( $path_info );
			$name = $finfo['basename'];
			
			if ($item instanceof \League\Flysystem\FileAttributes) {
					
				if( is_array( $allowed_ext ) ) {
					$ext = $finfo['extension'];
					if(!$ext OR !in_array( $ext, $allowed_ext )) continue;
				}
				
				$array['files'][] = array('path' => $path_info, 'name' => $name, 'size' => $item->fileSize() );
			
			} elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {

				$array['dirs'][] = array('path' => $path_info, 'name' => $name );

			}
		}
	
		return $array;
		
	}

	public static function DeleteDirectory( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->deleteDirectory($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->deleteDirectory($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				
			}
		
		}
		
		return false;
		
	}
	
	public static function CreateDirectory( $path, $driver = null ) {
		
		if( is_null( self::$driver ) ) {
			DLEFiles::init();
		}
		
		$path = self::normalize_path( $path );
		$driver = $driver ? $driver : self::$driver;

		if( is_object(self::$remote) AND $driver == 'remote') {
			
			try {
				
				return self::$remote->createDirectory($path);
			
			} catch(Throwable $e) {
					
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				$driver = self::$driver;
				
			}
		
		}
		
		if( is_object(self::$local) AND $driver == 'local' ) {
			
			try {
				
				return self::$local->createDirectory($path);
			
			} catch(Throwable $e) {
				
				self::error( $e->getMessage() );
				
			} catch (FilesystemException $e) {
				
				self::error( $e->getMessage() );
				
			}
		
		}
		
		return false;
		
	}
	
	private static function normalize_path( $path ) {
	
		$path = trim(str_replace(chr(0), '', (string)$path));
		$path = str_replace(array('/', '\\'), '/', $path);

		if( !$path ) return '';
		
		if (preg_match('#\p{C}+#u', $path)) {
			return '';
		}
	
		$path_parts = pathinfo( $path );

		$filename = $path_parts['basename'];
		
		$parts = array_filter(explode('/', $path_parts['dirname']), 'strlen');
		
		$absolutes = array();
		
		foreach ($parts as $part) {
			$part = trim($part);
			
			if ('.' == $part OR '..' == $part OR !$part) continue;
			
			$absolutes[] = $part;
		}
	
		$path = implode('/', $absolutes);
	
		if ( $path ) {
			$path = $path.'/';
		}
		
		if( $filename ) {
			$path .= $filename;
		}
	
		if( is_null( self::$root ) ) {
			$root = ROOT_DIR.'/';
		} else {
			$root = self::$root;
		}
		
		if(stripos ($path, $root) === 0) {
			$path = str_ireplace($root, '', $path);
		}
		
		return $path;
	
	}
	
	private static function error( $message ) {
		
		$message = str_ireplace( ROOT_DIR, '', $message );
		
		if( self::$driver == 'remote' AND self::$local_on_remote_errors) {
			
			self::$driver = 'local';
			self::$remote_error = $message;
			
		} else {
			
			self::$error = $message;
			
		}
		
	}

}

?>