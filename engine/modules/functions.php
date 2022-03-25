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
 File: functions.php
=====================================================
*/

if( !defined('DATALIFEENGINE') ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if ( isset($config['auth_domain']) AND $config['auth_domain'] ) {

	$domain_cookie = explode (".", clean_url( $_SERVER['HTTP_HOST'] ));
	$domain_cookie_count = count($domain_cookie);
	$domain_allow_count = -2;
	
	if ( $domain_cookie_count > 2 ) {
	
		if ( in_array($domain_cookie[$domain_cookie_count-2], array('com', 'net', 'org') )) $domain_allow_count = -3;
		if ( $domain_cookie[$domain_cookie_count-1] == 'ua' ) $domain_allow_count = -3;
		
		$domain_cookie = array_slice($domain_cookie, $domain_allow_count);
	}
	
	$domain_cookie = "." . implode (".", $domain_cookie);
	
	if( ip2long($_SERVER['HTTP_HOST']) == -1 OR ip2long($_SERVER['HTTP_HOST']) === false ) define( 'DOMAIN', $domain_cookie );
	else define( 'DOMAIN', '' );

} else define( 'DOMAIN', '' );

function dle_session( $sid = false ) {
	global $config;
	
	$params = session_get_cookie_params();

	if ( DOMAIN ) $params['domain'] = DOMAIN;
	
	if (isset($config['only_ssl']) AND $config['only_ssl']) $params['secure'] = true;

	session_set_cookie_params($params['lifetime'], "/", $params['domain'], $params['secure'], true);

	if ( $sid ) session_id( $sid );

	session_start();

}

function set_cookie($name, $value, $expires) {
	global $config;
	
	if( $expires ) {
		
		$expires = time() + ($expires * 86400);
	
	} else {
		
		$expires = FALSE;
	
	}
	
	if ($config['only_ssl']) setcookie( $name, $value, $expires, "/", DOMAIN, TRUE, TRUE );
	else setcookie( $name, $value, $expires, "/", DOMAIN, FALSE, TRUE );

}

function formatsize($file_size) {
	
	if( !$file_size OR $file_size < 1) return '0 b';
	
    $prefix = array("b", "Kb", "Mb", "Gb", "Tb");
    $exp = floor(log($file_size, 1024)) | 0;

    $file_size = round($file_size / (pow(1024, $exp)), 2).' '.$prefix[$exp];
	$file_size = str_replace(",", ".", $file_size);

    return $file_size;

}

class microTimer {
	var $time;

	function __construct() {
		$this->time = $this->get_real_time();
	}
	function get() {
		return round( ($this->get_real_time() - $this->time), 5 );
	}

	function get_real_time() {
		list ( $seconds, $microSeconds ) = explode( ' ', microtime() );
		return (( float ) $seconds + ( float ) $microSeconds);
	}
}

function flooder($ip, $news_time = false) {
	global $config, $db;
	
	$ip = $db->safesql($ip);
	
	if ( $news_time ) {

		$this_time = time() - intval($news_time);
		$db->query( "DELETE FROM " . PREFIX . "_flood where id < '$this_time' AND flag='1' " );
		
		$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_flood WHERE ip = '$ip' AND flag='1'");
		
		if( $row['count'] ) return TRUE;
		else return FALSE;

	} else {

		$this_time = time() - intval($config['flood_time']);
		$db->query( "DELETE FROM " . PREFIX . "_flood where id < '$this_time' AND flag='0' " );
		
		$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_flood WHERE ip = '$ip' AND flag='0'");
		
		if( $row['count'] ) return TRUE;
		else return FALSE;

	}

}

function totranslit($var, $lower = true, $punkt = true) {
	global $langtranslit;
	
	if ( is_array($var) ) return "";

	$var = str_replace(chr(0), '', $var);
	
	$var = trim( strip_tags( $var ) );
	$var = preg_replace( "/\s+/u", "-", $var );
	$var = str_replace( "/", "-", $var );
	
	if (is_array($langtranslit) AND count($langtranslit) ) {
		$var = strtr($var, $langtranslit);
	}

	if ( $punkt ) $var = preg_replace( "/[^a-z0-9\_\-.]+/mi", "", $var );
	else $var = preg_replace( "/[^a-z0-9\_\-]+/mi", "", $var );

	$var = preg_replace( '#[\-]+#i', '-', $var );
	$var = preg_replace( '#[.]+#i', '.', $var );

	if ( $lower ) $var = strtolower( $var );

	$var = str_ireplace( ".php", "", $var );
	$var = str_ireplace( ".php", ".ppp", $var );

	if( strlen( $var ) > 200 ) {
		
		$var = substr( $var, 0, 200 );
		
		if( ($temp_max = strrpos( $var, '-' )) ) $var = substr( $var, 0, $temp_max );
	
	}
	
	return $var;
}

function langdate($format, $stamp, $servertime = false, $custom = false ) {
	global $langdate, $member_id, $customlangdate;

	$timezones = array('Pacific/Midway','US/Samoa','US/Hawaii','US/Alaska','US/Pacific','America/Tijuana','US/Arizona','US/Mountain','America/Chihuahua','America/Mazatlan','America/Mexico_City','America/Monterrey','US/Central','US/Eastern','US/East-Indiana','America/Lima','America/Caracas','Canada/Atlantic','America/La_Paz','America/Santiago','Canada/Newfoundland','America/Buenos_Aires','America/Godthab','Atlantic/Stanley','Atlantic/Azores','Africa/Casablanca','Europe/Dublin','Europe/Lisbon','Europe/London','Europe/Amsterdam','Europe/Belgrade','Europe/Berlin','Europe/Bratislava','Europe/Brussels','Europe/Budapest','Europe/Copenhagen','Europe/Madrid','Europe/Paris','Europe/Prague','Europe/Rome','Europe/Sarajevo','Europe/Stockholm','Europe/Vienna','Europe/Warsaw','Europe/Zagreb','Europe/Athens','Europe/Bucharest','Europe/Helsinki','Europe/Istanbul','Asia/Jerusalem','Europe/Kiev','Europe/Minsk','Europe/Riga','Europe/Sofia','Europe/Tallinn','Europe/Vilnius','Asia/Baghdad','Asia/Kuwait','Africa/Nairobi','Asia/Tehran','Europe/Kaliningrad','Europe/Moscow','Europe/Volgograd','Europe/Samara','Asia/Baku','Asia/Muscat','Asia/Tbilisi','Asia/Yerevan','Asia/Kabul','Asia/Yekaterinburg','Asia/Tashkent','Asia/Kolkata','Asia/Kathmandu','Asia/Almaty','Asia/Novosibirsk','Asia/Jakarta','Asia/Krasnoyarsk','Asia/Hong_Kong','Asia/Kuala_Lumpur','Asia/Singapore','Asia/Taipei','Asia/Ulaanbaatar','Asia/Urumqi','Asia/Irkutsk','Asia/Seoul','Asia/Tokyo','Australia/Adelaide','Australia/Darwin','Asia/Yakutsk','Australia/Brisbane','Pacific/Port_Moresby','Australia/Sydney','Asia/Vladivostok','Asia/Sakhalin','Asia/Magadan','Pacific/Auckland','Pacific/Fiji');

	if( is_array($custom) ) $locallangdate = $customlangdate; else $locallangdate = $langdate;

	if( !is_array($locallangdate) ) {
		$locallangdate = array();
	}	
	
	if (!$stamp) { $stamp = time(); }
	
	$local = new DateTime('@'.$stamp);

	if (isset($member_id['timezone']) AND $member_id['timezone'] AND !$servertime) {
		$localzone = $member_id['timezone'];

	} else {

		$localzone = date_default_timezone_get();
	}

	if (!in_array($localzone, $timezones)) $localzone = 'Europe/Moscow';

	$local->setTimeZone(new DateTimeZone($localzone));

	return strtr( $local->format($format), $locallangdate );

}

function formdate( $matches=array() ) {
	global $news_date, $customlangdate, $config;
	
	if($config['decline_date']) return langdate($matches[1], $news_date);
	else return langdate($matches[1], $news_date, false, $customlangdate);

}

function check_newscount( $matches=array() ) {
	global $global_news_count;

	$block = $matches[3];

	$counts = explode( ',', trim($matches[2]) );
	
    if( $matches[1] == "newscount" ) {

        if( !in_array($global_news_count, $counts) ) return "";

    } else {

        if( in_array($global_news_count, $counts) ) return "";

    }

	return $block;
	
}

function msgbox($title, $text) {
	global $tpl;

	if (!class_exists('dle_template')) {
	    return;
	}
	
	$tpl_2 = new dle_template( );
	$tpl_2->dir = TEMPLATE_DIR;
	
	$tpl_2->load_template( 'info.tpl' );
	
	$tpl_2->set( '{error}', $text );
	$tpl_2->set( '{title}', $title );
	
	$tpl_2->compile( 'info' );
	$tpl_2->clear();
	
	$tpl->result['info'] .= $tpl_2->result['info'];
}

function ShowRating($id, $rating, $vote_num, $allow = true) {
	global $lang, $config, $row, $dle_module;

	if( !$config['rating_type'] ) {
		
		if( $rating AND $vote_num ) {
			
			$rating = round( ($rating / $vote_num), 0 );
			
		} else {
			$rating = 0;
		}
		
		if ($rating < 0 ) $rating = 0;
		
		$rating = $rating * 20;
	
		if( !$allow ) {
		
			$rated = <<<HTML
<div class="rating">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		</ul>
</div>
HTML;
		
			return $rated;
		}
	
		$rated = <<<HTML
<div id='ratig-layer-{$id}'>
	<div class="rating">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		<li><a href="#" title="{$lang['useless']}" class="r1-unit" onclick="doRate('1', '{$id}'); return false;">1</a></li>
		<li><a href="#" title="{$lang['poor']}" class="r2-unit" onclick="doRate('2', '{$id}'); return false;">2</a></li>
		<li><a href="#" title="{$lang['fair']}" class="r3-unit" onclick="doRate('3', '{$id}'); return false;">3</a></li>
		<li><a href="#" title="{$lang['good']}" class="r4-unit" onclick="doRate('4', '{$id}'); return false;">4</a></li>
		<li><a href="#" title="{$lang['excellent']}" class="r5-unit" onclick="doRate('5', '{$id}'); return false;">5</a></li>
		</ul>
	</div>
</div>
HTML;
	
		return $rated;

	} elseif ($config['rating_type'] == "1") {
		
		if( $rating < 0 ) $rating = 0;
		
		if( $allow ) $rated = "<span id=\"ratig-layer-{$id}\"><span class=\"ratingtypeplus\" >{$rating}</span></span>";
		else $rated = "<span class=\"ratingtypeplus\" >{$rating}</span>";
		
		return $rated;
	
	} elseif ($config['rating_type'] == "2" OR $config['rating_type'] == "3") {
		
		$extraclass = "ratingzero";
		
		if( $rating < 0 ) {
			$extraclass = "ratingminus";
		}
		
		if( $rating > 0 ) {
			$extraclass = "ratingplus";
			$rating = "+".$rating;
		}
		
		if( $allow ) $rated = "<span id=\"ratig-layer-{$id}\"><span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span></span>";
		else $rated = "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>";
		
		return $rated;
		
	}
	
}

function ShowCommentsRating($id, $rating, $vote_num, $allow = true) {
	global $lang, $config;

	if( !$config['comments_rating_type'] ) {
		
		if( $rating AND $vote_num ) $rating = round( ($rating / $vote_num), 0 );
		else $rating = 0;
		
		if ($rating < 0 ) $rating = 0;

		$rating = $rating * 20;
	
		if( !$allow ) {
		
			$rated = <<<HTML
<div class="rating">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		</ul>
</div>
HTML;
		
			return $rated;
		}
	
		$rated = <<<HTML
<div id='comments-ratig-layer-{$id}'><div class="rating">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		<li><a href="#" title="{$lang['useless']}" class="r1-unit" onclick="doCommentsRate('1', '{$id}'); return false;">1</a></li>
		<li><a href="#" title="{$lang['poor']}" class="r2-unit" onclick="doCommentsRate('2', '{$id}'); return false;">2</a></li>
		<li><a href="#" title="{$lang['fair']}" class="r3-unit" onclick="doCommentsRate('3', '{$id}'); return false;">3</a></li>
		<li><a href="#" title="{$lang['good']}" class="r4-unit" onclick="doCommentsRate('4', '{$id}'); return false;">4</a></li>
		<li><a href="#" title="{$lang['excellent']}" class="r5-unit" onclick="doCommentsRate('5', '{$id}'); return false;">5</a></li>
		</ul>
</div></div>
HTML;
	
		return $rated;

	} elseif ($config['comments_rating_type'] == "1") {
		
		if( $rating < 0 ) $rating = 0;
		
		if( $allow ) $rated = "<span id=\"comments-ratig-layer-{$id}\"><span class=\"ratingtypeplus\" >{$rating}</span></span>";
		else $rated = "<span class=\"ratingtypeplus\" >{$rating}</span>";
		
		return $rated;
	
	} elseif ($config['comments_rating_type'] == "2" OR $config['comments_rating_type'] == "3") {
		
		$extraclass = "ratingzero";
		
		if( $rating < 0 ) {
			$extraclass = "ratingminus";
		}
		
		if( $rating > 0 ) {
			$extraclass = "ratingplus";
			$rating = "+".$rating;
		}
		
		if( $allow ) $rated = "<span id=\"comments-ratig-layer-{$id}\"><span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span></span>";
		else $rated = "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>";
		
		return $rated;
		
	}
	
}

function userrating($id) {
	global $db, $config, $lang;

	$id = intval($id);
		
	$row = $db->super_query( "SELECT SUM(rating) as rating, SUM(vote_num) as num FROM " . PREFIX . "_post_extras WHERE user_id ='{$id}'" );
	
	if( !$config['rating_type'] ) {	
	
		if( $row['num'] ) $rating = round( ($row['rating'] / $row['num']), 0 );
		else $rating = 0;

		if ($rating < 0 ) $rating = 0;
		
		$rating = $rating * 20;
	
		$rated = <<<HTML
<div class="rating" style="display:inline;">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		</ul>
		</div>
HTML;
	
		return $rated;
	
	} elseif ($config['rating_type'] == "1") {
		
		if( $row['num'] ) $rating = number_format($row['rating'], 0, ',', ' '); else $rating = 0;
		
		if( $row['num'] < 0 ) $rating = 0;
		
		return "<span class=\"ratingtypeplus\" >{$rating}</span>";
		
	} elseif ($config['rating_type'] == "2" OR $config['rating_type'] == "3" ) {

		if( $row['num'] ) $rating = number_format($row['rating'], 0, ',', ' '); else $rating = 0;

		$extraclass = "ratingzero";
		
		if( $row['rating'] < 0 ) {
			$extraclass = "ratingminus";
		}
		
		if( $row['rating'] > 0 ) {
			$extraclass = "ratingplus";
			$rating = "+".$rating;
		}
		
		if($config['rating_type'] == "2") {
			
			return "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>";
		
		} else {
			$dislikes = ($row['num'] - $row['rating'])/2;
			$likes = $row['num'] - $dislikes;
			
			return str_replace(array('{likes}', '{dislikes}', '{rating}'), array("<span class=\"ratingtypeplusminus ratingplus\" >{$likes}</span>", "<span class=\"ratingtypeplusminus ratingminus\" >{$dislikes}</span>", "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>"), $lang['like_dislike_sum']);
		}

		
	}
}

function commentsuserrating($id) {
	global $db, $config, $lang;

	$id = intval($id);
	$row = $db->super_query( "SELECT SUM(rating) as rating, SUM(vote_num) as num FROM " . PREFIX . "_comments WHERE user_id ='{$id}'" );
	
	if( !$config['comments_rating_type'] ) {	
	
		if( $row['num'] ) $rating = round( ($row['rating'] / $row['num']), 0 );
		else $rating = 0;

		if ($rating < 0 ) $rating = 0;
		
		$rating = $rating * 20;
	
		$rated = <<<HTML
<div class="rating" style="display:inline;">
		<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
		</ul>
		</div>
HTML;
	
		return $rated;
	
	} elseif ($config['comments_rating_type'] == "1") {
		
		if( $row['num'] ) $rating = number_format($row['rating'], 0, ',', ' '); else $rating = 0;
		
		if( $rating < 0 ) $rating = 0;
		
		return "<span class=\"ratingtypeplus\" >{$rating}</span>";
		
	} elseif ($config['comments_rating_type'] == "2" OR $config['comments_rating_type'] == "3") {
		
		if( $row['num'] ) $rating = number_format($row['rating'], 0, ',', ' '); else $rating = 0;

		$extraclass = "ratingzero";
		
		if( $row['rating'] < 0 ) {
			$extraclass = "ratingminus";
		}
		
		if( $row['rating'] > 0 ) {
			$extraclass = "ratingplus";
			$rating = "+".$rating;
		}
		
		if($config['comments_rating_type'] == "2") {
			
			return "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>";
		
		} else {
			
			$dislikes = ($row['num'] - $row['rating'])/2;
			$likes = $row['num'] - $dislikes;
			
			return str_replace(array('{likes}', '{dislikes}', '{rating}'), array("<span class=\"ratingtypeplusminus ratingplus\" >{$likes}</span>", "<span class=\"ratingtypeplusminus ratingminus\" >{$dislikes}</span>", "<span class=\"ratingtypeplusminus {$extraclass}\" >{$rating}</span>"), $lang['like_dislike_sum']);
		}
		
	}
}

function CategoryNewsSelection($categoryid = 0, $parentid = 0, $nocat = TRUE, $sublevelmarker = '', $returnstring = '') {
	global $cat_info, $user_group, $member_id, $dle_module;

	if ($dle_module == 'addnews') {
		
		if($member_id['cat_allow_addnews']) $allow_list = explode( ',', $member_id['cat_allow_addnews'] );
		else $allow_list = explode( ',', $user_group[$member_id['user_group']]['cat_allow_addnews'] );
		
	} else $allow_list = explode( ',', $user_group[$member_id['user_group']]['allow_cats'] );

	$not_allow_list = explode( ',', $user_group[$member_id['user_group']]['not_allow_cats'] );

	if ($dle_module == 'search') {
		if( count( $cat_info ) ){
			foreach ($cat_info as $cats) {
				if($cats['disable_search']) $not_allow_list[] = $cats['id'];
			}
		}
	}
	
	if ($member_id['cat_add']) $spec_list = explode( ',', $member_id['cat_add'] );
	else $spec_list = explode( ',', $user_group[$member_id['user_group']]['cat_add'] );

	$root_category = array ();
	
	if( $parentid == 0 ) {
		if( $nocat AND $allow_list[0] == "all") $returnstring .= '<option value="0"></option>';
	} else {
		$sublevelmarker .= '&nbsp;&nbsp;&nbsp;';
	}
	
	if( count( $cat_info ) ) {
		
		foreach ( $cat_info as $cats ) {
			if( $cats['parentid'] == $parentid ) $root_category[] = $cats['id'];
		}
		
		if( count( $root_category ) ) {
			foreach ( $root_category as $id ) {
				
				if( $allow_list[0] == "all" OR in_array( $id, $allow_list ) ) {
					
					if( in_array( $id, $not_allow_list ) ) continue;
					
					if( $spec_list[0] == "all" or in_array( $id, $spec_list ) ) $color = "";
					else $color = "style=\"color: red\" ";
					
					$returnstring .= "<option {$color}value=\"" . $id . '" ';
					
					if( is_array( $categoryid ) ) {
						foreach ( $categoryid as $element ) {
							
							$element = intval($element);
							
							if( $element == $id ) $returnstring .= 'selected';
							
						}
					} elseif( intval($categoryid) == $id ) $returnstring .= 'selected';
					
					$returnstring .= '>' . $sublevelmarker . $cat_info[$id]['name'] . '</option>';
				}
				
				$returnstring = CategoryNewsSelection( $categoryid, $id, $nocat, $sublevelmarker, $returnstring );
			}
		}
	}
	return $returnstring;
}

function get_ID($cat_info, $category) {
	foreach ( $cat_info as $cats ) {
		if( $cats['alt_name'] == $category ) return $cats['id'];
	}
	return false;
}

function set_vars($file, $data) {
	
	$file = totranslit($file, true, false);
	
	if ( is_array($data) OR is_int($data) OR is_string($data) ) {
		
		file_put_contents (ENGINE_DIR . '/cache/system/' . $file . '.php', json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), LOCK_EX);
		@chmod( ENGINE_DIR . '/cache/system/' . $file . '.php', 0666 );
		
	}
}

function get_vars($file) {
	$file = totranslit($file, true, false);

	$data = @file_get_contents( ENGINE_DIR . '/cache/system/' . $file . '.php' );

	if ( $data !== false ) {

		$data = json_decode( $data, true );
		if ( is_array($data) OR is_int($data) OR is_string($data) ) return $data;

	} 

	return false;	
}

function get_count_from_cache( $hash ) {
	
	$hash = md5($hash);

	$all_counts = dle_cache("news_cache_count");
	
	if( $all_counts ) {
		
		$all_counts = json_decode($all_counts, true);
		
		if( isset( $all_counts[$hash] ) ) {
			return intval($all_counts[$hash]);
		}
		
	}
	
	return 0;

}

function set_count_to_cache( $hash, $count ) {
	
	global $config;
	
	if( !$config['allow_cache'] ) return false;
	
	$hash = md5($hash);
	
	$all_counts = dle_cache("news_cache_count");
	
	if( $all_counts ) {
		
		$all_counts = json_decode($all_counts, true);
		
	}
	
	if( !is_array($all_counts) ) $all_counts = array();
	
	$all_counts[$hash] = intval($count);
	
	create_cache ( "news_cache_count", json_encode( $all_counts , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	
	return true;
	
}

function dle_cache($prefix, $cache_id = false, $member_prefix = false) {
	global $config, $is_logged, $member_id, $dlefastcache;
	
	if( !$config['allow_cache'] ) return false;

	$config['clear_cache'] = (intval($config['clear_cache']) > 1) ? intval($config['clear_cache']) : 0;

	if( $is_logged ) $end_file = $member_id['user_group'];
	else $end_file = "0";
	
	if( ! $cache_id ) {
		
		$key = $prefix;
	
	} else {
		
		$cache_id = md5( $cache_id );
		
		if( $member_prefix ) $key = $prefix . "_" . $cache_id . "_" . $end_file;
		else $key = $prefix . "_" . $cache_id;
	
	}
	
	if( $config['cache_type'] ) {
		if( $dlefastcache->connection > 0 ) {
			return $dlefastcache->get($key);
		}
	}

	$buffer = @file_get_contents( ENGINE_DIR . "/cache/" . $key . ".tmp" );

	if ( $buffer !== false AND $config['clear_cache'] ) {

		$file_date = @filemtime( ENGINE_DIR . "/cache/" . $key . ".tmp" );
		$file_date = time()-$file_date;

		if ( $file_date > ( $config['clear_cache'] * 60 ) ) {
			$buffer = false;
			@unlink( ENGINE_DIR . "/cache/" . $key . ".tmp" );
		}

		return $buffer;

	} else return $buffer;

}

function create_cache($prefix, $cache_text, $cache_id = false, $member_prefix = false) {
	global $config, $is_logged, $member_id, $dlefastcache;
	
	if( !$config['allow_cache'] ) return false;
	
	if( $is_logged ) $end_file = $member_id['user_group'];
	else $end_file = "0";
	
	if( ! $cache_id ) {
		
		$key = $prefix;
		
	} else {
		
		$cache_id = md5( $cache_id );
		
		if( $member_prefix ) $key = $prefix . "_" . $cache_id . "_" . $end_file;
		else $key = $prefix . "_" . $cache_id;
	
	}
	
	if($cache_text === false) $cache_text = '';

	if( $config['cache_type'] ) {
		if( $dlefastcache->connection > 0 ) {
			$dlefastcache->set( $key, $cache_text );
			return true;
		}
	}

	file_put_contents (ENGINE_DIR . "/cache/" . $key . ".tmp", $cache_text, LOCK_EX);
	@chmod( ENGINE_DIR . "/cache/" . $key . ".tmp", 0666 );
	
	return true;
	
}

function clear_cache($cache_areas = false) {
	global $dlefastcache, $config;

	if( $config['cache_type'] ) {
		if( $dlefastcache->connection > 0 ) {
			$dlefastcache->clear( $cache_areas );
			return true;
		}
	}

	if ( $cache_areas ) {
		if(!is_array($cache_areas)) {
			$cache_areas = array($cache_areas);
		}
	}
		
	$fdir = opendir( ENGINE_DIR . '/cache' );
		
	while ( $file = readdir( $fdir ) ) {
		if( $file != '.htaccess' AND !is_dir(ENGINE_DIR . '/cache/' . $file) ) {
			
			if( $cache_areas ) {
				
				foreach($cache_areas as $cache_area) if( stripos( $file, $cache_area ) === 0 ) @unlink( ENGINE_DIR . '/cache/' . $file );
			
			} else {
				
				@unlink( ENGINE_DIR . '/cache/' . $file );
			
			}
		}
	}
	
	return true;

}

function ChangeSkin( $skin ) {
	
	$templates_list = get_folder_list( 'templates' );
	unset($templates_list['smartphone']);
	
	$skin_list = "<form method=\"post\"><select onchange=\"submit()\" name=\"skin_name\">";
	
	foreach ( $templates_list as $key => $value ) {
		
		if( $key == $skin ) $selected = " selected=\"selected\"";
		else $selected = "";
		
		$skin_list .= "<option value=\"{$key}\"" . $selected . ">{$value['name']}</option>";
	}
	
	$skin_list .= '</select><input type="hidden" name="action_skin_change" value="yes"></form>';
	
	return $skin_list;
}

function get_folder_list( $folder = 'language' ) {
	
	$allowed_folder = array( 'language', 'templates' );
	
	$list = array ();
	
	if( !in_array($folder, $allowed_folder) ) {
		return $list;
	}
	
	if( !$handle = opendir( ROOT_DIR . "/". $folder ) ) {
		$list[]['name'] = $lang['opt_errfo']." ".$folder;
		return $list;
	}
	
	while ( false !== ($file = readdir( $handle )) ) {
		
		if( is_dir( ROOT_DIR . "/".$folder."/".$file ) AND ($file != "." and $file != "..") ) {
			
			if( is_file( ROOT_DIR . "/".$folder."/".$file."/info.json" ) ) {
				
				$data = json_decode( trim(file_get_contents( ROOT_DIR . "/".$folder."/".$file."/info.json" ) ), true );
				
				if( isset($data['name']) AND $data['name'] ) {
					$list[$file] = $data;
					continue;
				}
			}
			
			$list[$file]['name'] = $file;
		}
		
	}

	closedir( $handle );
	ksort($list);

	return $list;

	
}

function get_mass_cats($id) {
	global $cat_info;

	$id = explode ('-', $id);
	$temp_array = array();

	foreach ( $cat_info as $cats ) {

		if ($cats['id'] >= $id[0] AND $cats['id'] <= $id[1] ) $temp_array[] = intval($cats['id']);

	}

	if ( count($temp_array) ) { sort($temp_array); return implode(',', $temp_array); }
	else return 0;

}

function custom_comments( $matches=array() ) {
	global $db, $is_logged, $member_id, $cat_info, $config, $user_group, $category_id, $_TIME, $lang, $smartphone_detected, $dle_module, $allow_comments_ajax, $PHP_SELF, $dle_login_hash, $replace_links;

	if ( !count($matches) ) return "";
	
	$temp_category_id = $category_id;
	$param_str = trim($matches[1]);
	$custom_cache_id = "customcomments".$param_str.$config['skin'];

	$aviable = array("global");
	$comm_sort = "id";
	$comm_msort = "DESC";
	$where = array();
	$thisdate = date( "Y-m-d H:i:s", $_TIME );
	$sql_select = "SELECT cm.id, cm.post_id, cm.user_id, cm.date, cm.autor as gast_name, cm.email as gast_email, text, ip, is_register, cm.rating, cm.vote_num, name, u.email, news_num, u.comm_num, user_group, lastdate, reg_date, signature, foto, fullname, land, u.xfields, p.title, p.date as newsdate, p.alt_name, p.category FROM " . PREFIX . "_comments cm LEFT JOIN " . PREFIX . "_post p ON cm.post_id=p.id {cat_join}LEFT JOIN " . USERPREFIX . "_users u ON cm.user_id=u.user_id ";

	$allow_cache = $config['allow_cache'];
	$cats_select = false;
	$ids_for_sort = false;
	
	if( preg_match( "#available=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$aviable = explode( '|', $match[1] );
	}

	$do = $dle_module ? $dle_module : "main";

	if( !in_array( $do, $aviable ) AND ($aviable[0] != "global") ) return "";

	if( preg_match( "#newsid=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$param_str = str_replace($match[0], '', $param_str);
		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "p.id >= '" . intval($value[0]) . "' AND p.id <= '".intval($value[1])."'";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "p.id IN ('" . implode("','", $temp_array) . "')";
			$ids_for_sort = "FIND_IN_SET(p.id, '".implode(",", $temp_array)."') ";
		}

		if ( count($where_id) ) { 
			$custom_id = implode(' OR ', $where_id);
			$where[] = $custom_id;

		}
	}
	
	if( preg_match( "#newsidexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$param_str = str_replace($match[0], '', $param_str);
		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "(p.id < '" . intval($value[0]) . "' OR p.id > '".intval($value[1])."')";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "p.id NOT IN ('" . implode("','", $temp_array) . "')";
		}

		if ( count($where_id) ) { 
			$custom_id = implode(' AND ', $where_id);
			$where[] = $custom_id;

		}
	}
	
	if( preg_match( "#id=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "cm.id >= '" . intval($value[0]) . "' AND cm.id <= '".intval($value[1])."'";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "cm.id IN ('" . implode("','", $temp_array) . "')";
			$ids_for_sort = "FIND_IN_SET(cm.id, '".implode(",", $temp_array)."') ";
		}

		if ( count($where_id) ) { 
			$custom_id = implode(' OR ', $where_id);
			$where[] = $custom_id;

		}
	}
	
	if( preg_match( "#idexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "(cm.id < '" . intval($value[0]) . "' OR cm.id > '".intval($value[1])."')";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "cm.id NOT IN ('" . implode("','", $temp_array) . "')";
		}

		if ( count($where_id) ) { 
			$custom_id = implode(' AND ', $where_id);
			$where[] = $custom_id;

		}
	}

	$cat_join = "";
	
	if( preg_match( "#category=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$cats_select = true;

		$temp_array = array();

		$match[1] = explode (',', $match[1]);

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) $temp_array[] = get_mass_cats($value);
			else $temp_array[] = intval($value);

		}

		$temp_array = implode(',', $temp_array);

		$custom_category = $db->safesql( trim($temp_array) );
		$custom_category = str_replace( ",", "','", $custom_category );

		if( $config['allow_multi_category'] ) {
			
			$cat_join = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $custom_category . "')) c ON (p.id=c.news_id) ";
		
		} else {

			$where[] = "p.category IN ('" . $custom_category . "')";
		
		}
	}
	
	if( preg_match( "#categoryexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$cats_select = true;
		
		$temp_array = array();

		$match[1] = explode (',', $match[1]);

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) $temp_array[] = get_mass_cats($value);
			else $temp_array[] = intval($value);

		}

		$temp_array = implode(',', $temp_array);

		$custom_category = $db->safesql( trim($temp_array) );
		$custom_category = str_replace( ",", "','", $custom_category );

		if( $config['allow_multi_category'] ) {
			
			$where[] = "p.id NOT IN ( SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $custom_category . "') )";
		
		} else {
			
			$where[] = "p.category NOT IN ('" . $custom_category . "')";
		
		}
	}
	
	if (!$cats_select) {
		
		$allow_list = explode( ',', $user_group[$member_id['user_group']]['allow_cats'] );
		
		if( $allow_list[0] != "all" ) {
	
			if( $config['allow_multi_category'] ) {
					
				$cat_join = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . implode( "','", $allow_list ) . "')) c ON (p.id=c.news_id) ";
				
			} else {
					
				$where[] = "p.category IN ('" . implode( "','", $allow_list ) . "')";
				
			}
		
		}
	
		$not_allow_cats = explode ( ',', $user_group[$member_id['user_group']]['not_allow_cats'] );
			
		if( $not_allow_cats[0] != "" ) {
			
			if ($config['allow_multi_category']) {
				
				$where[] = "p.id NOT IN ( SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN (" . implode ( ',', $not_allow_cats ) . ") )";
			
			} else {
				
				$where[] = "p.category NOT IN ('" . implode ( "','", $not_allow_cats ) . "')";
			
			}
			
		}
	}
	
	$sql_select = str_replace( "{cat_join}", $cat_join, $sql_select );
	
	if( preg_match( "#days=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$days = intval(trim($match[1]));
		$where[] = "cm.date >= '{$thisdate}' - INTERVAL {$days} DAY AND cm.date < '{$thisdate}'";
	}

	if( preg_match( "#author=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "cm.autor = '{$value}'";

		}		
		
		$where[] = implode(' OR ', $temp_array);
		
		
	}

	if( preg_match( "#authorexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "cm.autor != '{$value}'";

		}		
		
		$where[] = implode(' AND ', $temp_array);
		
		
	}
	
	if( $config['allow_cmod'] ) {
		
		$where[] = "cm.approve=1";
	
	}

	if( preg_match( "#template=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_template = trim($match[1]);
	} else $custom_template = "comments";
	
	
	if( preg_match( "#sort=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_sort = array ('asc' => 'ASC', 'desc' => 'DESC' );

		$match[1] = strtolower($match[1]);

		if ( $allowed_sort[$match[1]] ) $comm_msort = $allowed_sort[$match[1]];

	}
	
	if( preg_match( "#order=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_sort = array ('date' => 'id', 'rating' => 'rating', 'rand' => 'RAND()' );

		$match[1] = strtolower($match[1]);

		if ( $allowed_sort[$match[1]] ) $comm_sort = $allowed_sort[$match[1]];
		
		if ($match[1] == "rand" ) { $comm_msort = ""; }
		
		if($match[1] == "id_as_list" AND $ids_for_sort){
			$comm_sort = $ids_for_sort;
			$comm_msort = "";
		}

	}
	
	if( preg_match( "#from=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_from = intval($match[1]);
	} else { $custom_from = 0; }

	if( preg_match( "#limit=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_limit = intval($match[1]);
	} else $custom_limit = intval($config['comm_nummers']);

	if( preg_match( "#cache=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		if( $match[1] == "yes" ) $config['allow_cache'] = 1;
		else $config['allow_cache'] = false;
	}

	if( count( $where ) ) {
		
		$where = implode( " AND ", $where );
		$where = "WHERE " . $where;
	
	} else $where = "";

	$sql_select .=  $where." ORDER BY " . $comm_sort . " " . $comm_msort . " LIMIT " . $custom_from . "," . $custom_limit;

	$content = dle_cache( "news", "customcomments".$custom_cache_id, true );

	if( $content !== false ) {

		$config['allow_cache'] = $allow_cache;
		return $content;
	
	} else {

		$tpl = new dle_template();
		$tpl->dir = TEMPLATE_DIR;
			
		$comments = new DLE_Comments( $db, $custom_limit, $custom_limit );
		$comments->query = $sql_select;
		$content = $comments->build_customcomments( $tpl, $custom_template.'.tpl' );

		if ( $config['allow_cache'] ) create_cache( "news", $content, "customcomments".$param_str.$config['skin'], true );

		$config['allow_cache'] = $allow_cache;
		$category_id = $temp_category_id;
		
		return $content;
	
	}
	

}

function custom_print( $matches=array() ) {
	global $db, $is_logged, $member_id, $xf_inited, $cat_info, $config, $user_group, $category_id, $_TIME, $lang, $smartphone_detected, $dle_module, $allow_comments_ajax, $PHP_SELF, $news_date, $banners, $banner_in_news, $ban_short, $url_page, $user_query, $custom_news, $global_news_count, $remove_canonical, $custom_navigation, $row, $_DOCUMENT_DATE;

	if ( !count($matches) ) return "";
	$save_row = $row;
	
	$param_str = trim($matches[1]);
	$custom_cache_id = "customnews".$param_str.$config['skin'];


	if( $config['user_in_news'] ) {
		
		$user_select = ", u.email, u.name, u.user_id, u.news_num, u.comm_num as user_comm_num, u.user_group, u.lastdate, u.reg_date, u.banned, u.allow_mail, u.info, u.signature, u.foto, u.fullname, u.land, u.favorites, u.pm_all, u.pm_unread, u.time_limit, u.xfields as user_xfields ";
		$user_join = "LEFT JOIN " . USERPREFIX . "_users u ON (e.user_id=u.user_id) ";
		
	} else { $user_select = ""; $user_join = ""; }
		
	$aviable = array("global");
	$thisdate = date( "Y-m-d H:i:s", $_TIME );
	$sql_select = "SELECT p.id, p.autor, p.date, p.short_story, CHAR_LENGTH(p.full_story) as full_story, p.xfields, p.title, p.category, p.alt_name, p.comm_num, p.allow_comm, p.fixed, p.tags, e.news_read, e.allow_rate, e.rating, e.vote_num, e.votes, e.view_edit, e.editdate, e.editor, e.reason {$user_select}FROM " . PREFIX . "_post p {cat_join}LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) {$user_join}";
	$where = array();
	$allow_cache = $config['allow_cache'];
	$cats_select = false;
	$ids_for_sort = false;
	$cat_join_count = "";
	$xfields_in_news = array();

	if( preg_match( "#aviable=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$aviable = explode( '|', $match[1] );
	}
	
	if( preg_match( "#available=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$aviable = explode( '|', $match[1] );
	}
	
	$do = $dle_module ? $dle_module : "main";

	if( !in_array( $do, $aviable ) AND ($aviable[0] != "global") ) return "";

	if( preg_match( "#id=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "id >= '" . intval($value[0]) . "' AND id <= '".intval($value[1])."'";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "id IN ('" . implode("','", $temp_array) . "')";
			$ids_for_sort = "FIND_IN_SET(id, '".implode(",", $temp_array)."') ";
		}

		if ( count($where_id) ) { 
			$custom_id = "(".implode(' OR ', $where_id).")";
			$where[] = $custom_id;

		}
	}
	
	if( preg_match( "#idexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$temp_array = array();
		$where_id = array();
		$match[1] = explode (',', trim($match[1]));

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) {
				$value = explode('-', $value);
				$where_id[] = "(id < '" . intval($value[0]) . "' OR id > '".intval($value[1])."')";

			} else $temp_array[] = intval($value);

		}

		if ( count($temp_array) ) {

			$where_id[] = "id NOT IN ('" . implode("','", $temp_array) . "')";
		}

		if ( count($where_id) ) { 
			$custom_id = implode(' AND ', $where_id);
			$where[] = $custom_id;

		}
	}
	
	$cat_join = "";

	if( preg_match( "#category=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$cats_select = true;
		
		$temp_array = array();

		$match[1] = explode (',', $match[1]);

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) $temp_array[] = get_mass_cats($value);
			else $temp_array[] = intval($value);

		}

		$temp_array = trim(implode(',', $temp_array));

		if( $temp_array AND preg_match( "#subcat=['\"](yes|only)['\"]#i", $param_str, $subcat_match ) ) {
			
			$subcat_array = array();
			$c_arr = explode (',', $temp_array);
			
			foreach ($c_arr as $value) {
				$subcat_array[] = get_sub_cats ( $value, '', false );
			}
			
			$subcat_array = trim(str_replace( "|", ",", implode(',', $subcat_array)));
			
			if( $subcat_match[1] == "yes" ) {
				$temp_array .= ','.$subcat_array;
			}
			
			if( $subcat_match[1] == "only" ) {
				$temp_array = $subcat_array;
			}
			
		}

		$custom_category = $db->safesql( $temp_array );
		$custom_category = str_replace( ",", "','", $custom_category );

		if( $config['allow_multi_category'] ) {
			
			$cat_join = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $custom_category . "')) c ON (p.id=c.news_id) ";
		
		} else {

			$where[] = "p.category IN ('" . $custom_category . "')";
		
		}
	}
	
	if( preg_match( "#categoryexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$cats_select = true;
		
		$temp_array = array();

		$match[1] = explode (',', $match[1]);

		foreach ($match[1] as $value) {

			if( count(explode('-', $value)) == 2 ) $temp_array[] = get_mass_cats($value);
			else $temp_array[] = intval($value);

		}

		$temp_array = trim(implode(',', $temp_array));

		if( $temp_array AND preg_match( "#subcat=['\"](yes|only)['\"]#i", $param_str, $subcat_match ) ) {
			
			$subcat_array = array();
			$c_arr = explode (',', $temp_array);
			
			foreach ($c_arr as $value) {
				$subcat_array[] = get_sub_cats ( $value, '', false );
			}
			
			$subcat_array = trim(str_replace( "|", ",", implode(',', $subcat_array)));
			
			if( $subcat_match[1] == "yes" ) {
				$temp_array .= ','.$subcat_array;
			}
			
			if( $subcat_match[1] == "only" ) {
				$temp_array = $subcat_array;
			}
			
		}

		$custom_category = $db->safesql( $temp_array );
		$custom_category = str_replace( ",", "','", $custom_category );

		if( $config['allow_multi_category'] ) {
			
			$where[] = "p.id NOT IN ( SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $custom_category . "') )";
		
		} else {
			
			$where[] = "category NOT IN ('" . $custom_category . "')";
		
		}
	}
	
	if( !$cats_select ) {
		
		$allow_list = explode( ',', $user_group[$member_id['user_group']]['allow_cats'] );
		
		if( $allow_list[0] != "all" AND !$user_group[$member_id['user_group']]['allow_short'] ) {
	
			if( $config['allow_multi_category'] ) {
					
				$cat_join = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . implode( "','", $allow_list ) . "')) c ON (p.id=c.news_id) ";
				
			} else {
					
				$where[] = "category IN ('" . implode( "','", $allow_list ) . "')";
				
			}
		
		}
	
		$not_allow_cats = explode ( ',', $user_group[$member_id['user_group']]['not_allow_cats'] );
			
		if( $not_allow_cats[0] != "" ) {
			
			if ($config['allow_multi_category']) {
				
				$where[] = "p.id NOT IN ( SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . implode ( "','", $not_allow_cats ) . "') )";
			
			} else {
				
				$where[] = "category NOT IN ('" . implode ( "','", $not_allow_cats ) . "')";
			
			}
			
		}
		
	}
	
	$sql_select = str_replace( "{cat_join}", $cat_join, $sql_select );
	
	if( preg_match( "#futureannounce=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		if( $match[1] == "yes" ) $fromfuture = true;
		else $fromfuture = false;
		
	} else $fromfuture = false;
	
	if( preg_match( "#days=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$days = intval(trim($match[1]));
		
		if($fromfuture) {
			
			$startdate = date("Y-m-d 00:00:00", strtotime("+1 day"));
			$enddate = date("Y-m-d 00:00:00", strtotime("+".($days+1)." day"));
			$where[] = "p.date >= '{$startdate}' AND p.date < '{$enddate}'";
			
		} else {
			
			$where[] = "p.date >= '{$thisdate}' - INTERVAL {$days} DAY AND p.date < '{$thisdate}'";
			
		}
		
	} else $days = 0;

	if( preg_match( "#author=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.autor = '{$value}'";

		}		
		
		$where[] = "(".implode(' OR ', $temp_array).")";
		
		
	}

	if( preg_match( "#authorexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.autor != '{$value}'";

		}		
		
		$where[] = implode(' AND ', $temp_array);
		
		
	}

	if( preg_match( "#catalog=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.symbol = '{$value}'";

		}		
		
		$where[] = "(".implode(' OR ', $temp_array).")";
		
		
	}

	if( preg_match( "#catalogexclude=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.symbol != '{$value}'";

		}		
		
		$where[] = implode(' AND ', $temp_array);
		
		
	}
	
	if( preg_match( "#xfields=[\"](.+?)[\"]#i", $param_str, $match ) ) {

		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.xfields LIKE '%{$value}%'";

		}		
		
		$where[] = "(".implode(' OR ', $temp_array).")";
		
	}

	
	if( preg_match( "#xfieldsexclude=[\"](.+?)[\"]#i", $param_str, $match ) ) {
		
		$match[1] = explode (',', $match[1]);

		$temp_array = array();

		foreach ($match[1] as $value) {

			$value = $db->safesql(trim($value));
			$temp_array[] = "p.xfields NOT LIKE '%{$value}%'";

		}		
		
		$where[] = implode(' AND ', $temp_array);
		
		
	}

	if( preg_match( "#template=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_template = trim($match[1]);
	} else $custom_template = "shortstory";

	if( preg_match( "#from=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_from = intval($match[1]);
		$custom_all = $custom_from;
	} else { $custom_from = 0; $custom_all = 0;}

	if( preg_match( "#limit=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$custom_limit = intval($match[1]);
	} else $custom_limit = $config['news_number'];

	if( preg_match( "#cache=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		if( $match[1] == "yes" ) $config['allow_cache'] = 1;
		else $config['allow_cache'] = false;
	}

	if( $config['allow_cache'] ) $short_news_cache = true; else $short_news_cache = false;
	
	if( preg_match( "#fixed=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		$fixed = "";

		if( $match[1] == "yes" ) $fixed = "fixed DESC, ";
		elseif( $match[1] == "only" ) { $where[] = "fixed='1'"; }
		elseif( $match[1] == "without" ) { $where[] = "fixed='0'"; }

	} else $fixed = "";
	
	
	if( preg_match( "#banners=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		if( $match[1] == "yes" ) $use_banners = true;
		else $use_banners = false;
		
	} else $use_banners = false;

	if( $is_logged and ($user_group[$member_id['user_group']]['allow_edit'] and ! $user_group[$member_id['user_group']]['allow_all_edit']) ) $config['allow_cache'] = false;

	if (isset($custom_category) AND $custom_category) {
		
		if( $cat_info[$custom_category]['news_sort'] ) $news_sort = $cat_info[$custom_category]['news_sort']; else $news_sort = $config['news_sort'];
		if( $cat_info[$custom_category]['news_msort'] ) $news_msort = $cat_info[$custom_category]['news_msort']; else $news_msort = $config['news_msort'];
		
	} else {
		
		$news_sort = $config['news_sort'];
		$news_msort = $config['news_msort'];
	}

	if( preg_match( "#sort=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_sort = array ('asc' => 'ASC', 'desc' => 'DESC' );

		$match[1] = strtolower($match[1]);

		if ( $allowed_sort[$match[1]] ) $news_msort = $allowed_sort[$match[1]];

	}

	if( preg_match( "#order=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_sort = array ('date' => 'date', 'editdate' => 'editdate', 'rating' => 'rating', 'reads' => 'news_read', 'comments' => 'p.comm_num','title' => 'title', 'rand' => 'RAND()' );

		$match[1] = strtolower($match[1]);

		if ( $allowed_sort[$match[1]] ) $news_sort = $allowed_sort[$match[1]];

		if ($match[1] == "rand" ) { $fixed = ""; $news_msort = ""; }
		
		if($match[1] == "id_as_list" AND $ids_for_sort){
			$news_sort = $ids_for_sort;
			$news_msort = "";
		}

		if($match[1] == "lastviewed") {

			if(!$config['last_viewed']) return $lang['enable_lastviewed'];
		
			if( !$_COOKIE['viewed_ids'] ) return '';
			
			$viewed_ids = explode(',', trim($_COOKIE['viewed_ids']));
			$temp_array = array();
			
			if($news_msort == "ASC") $viewed_ids = array_reverse($viewed_ids);
			
			foreach ($viewed_ids as $value) {
				$value = intval(trim($value));
				
				if ($value > 0) $temp_array[] = $db->safesql($value);
				
			}
			
			if( count($temp_array) ) {
				$fixed = "";
				$where[] = "id IN ('" . implode("','", $temp_array) . "')";
				$news_sort = "FIND_IN_SET(id, '".implode(",", $temp_array)."') ";
				$news_msort = "";
				$config['allow_cache'] = false;
			}
		
		}
		
	}
	
	if( preg_match( "#sortbyuser=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		
		if( $match[1] == "yes" ) {
			
			if (isset ( $_SESSION['dle_sort_global'] )) $news_sort = $_SESSION['dle_sort_global'];
			if (isset ( $_SESSION['dle_direction_global'] )) $news_msort = $_SESSION['dle_direction_global'];
			
			if ( !defined('CUSTOMSORT') ) {
				define('CUSTOMSORT', true);
			}
	
		}

	}
	
	if( preg_match( "#navigation=['\"](.+?)['\"]#i", $param_str, $match ) ) {

		if( $match[1] == "yes" AND $url_page !== false ) {

			$build_navigation = true;
			if (isset ( $_GET['cstart'] )) $cstart = intval ( $_GET['cstart'] ); else $cstart = 0;

			if ($cstart > $config['max_cache_pages']) $config['allow_cache'] = false;

			if ($cstart) {
				$cstart = $cstart - 1;
				$cstart = ($cstart * $custom_limit) + $custom_from;
				$custom_from = $cstart;
				$remove_canonical = true;
			}
			
			$custom_cache_id = $custom_cache_id.$cstart;
			
		} else $build_navigation = false;

	} else $build_navigation = false;

	$content = dle_cache( "news", $custom_cache_id, true );
	
	if( $content ) {
		
		$content = json_decode($content, true);
		
		if( is_array( $content ) ) {
			
			if( $content['navigation'] ) {
				if ( !defined('CUSTOMNAVIGATION') ) {
					define('CUSTOMNAVIGATION', true);
					$custom_navigation = $content['navigation'];
				}
			}
			
			if( isset( $active['last-modified'] ) ) {
				
				if( $active['last-modified'] > $_DOCUMENT_DATE ) {
					$_DOCUMENT_DATE = $active['last-modified'];
				}
				
			}
				
			$content = $content['content'];
			
		}
	}
				
	if( $content !== false ) {

		$config['allow_cache'] = $allow_cache;
		$custom_news = true;
		$row = $save_row;
		
		if ($config['allow_quick_wysiwyg'] AND ($user_group[$member_id['user_group']]['allow_edit'] OR $user_group[$member_id['user_group']]['allow_all_edit'])) $allow_comments_ajax = true;
				
		return $content;
	
	} else {

		if( preg_match( "#tags=['\"](.+?)['\"]#i", $param_str, $match ) ) {

			$temp_array = array();
			
			$match[1] = explode (',', trim($match[1]));
			
			foreach ($match[1] as $value) {
				$value = $db->safesql(trim($value));
				if( $value ) $temp_array[] = "tag='{$value}'";
			}
			
			if ( count($temp_array) ) {
	
				$temp_array = implode(" OR ", $temp_array);
				
				$db->query ( "SELECT news_id FROM " . PREFIX . "_tags WHERE {$temp_array}" );

				$temp_array = array ();
				
				while ( $row = $db->get_row () ) {
					
					if (!in_array($row['news_id'], $temp_array)) $temp_array[] = $row['news_id'];
				
				}
				
				if (count ( $temp_array )) {
					
					$where[] = "id IN ('" . implode("','", $temp_array) . "')";
				
				} else $where[] = "id IN ('0')";
				
			}
			
		}
		
		$where[] = "approve=1";

		if( $config['no_date'] AND !$config['news_future'] AND !$days) $where[] = "date < '" . $thisdate . "'";
		
		if ( $build_navigation ) {
			
			$sql_count = "SELECT COUNT(*) as count FROM " . PREFIX . "_post p {$cat_join}WHERE ".implode(' AND ', $where);

		} else $sql_count = "";

		$tpl = new dle_template();
		$tpl->dir = TEMPLATE_DIR;				
		$tpl->is_custom = true;

		$tpl->load_template( $custom_template . '.tpl' );
	
		$sql_select .= " WHERE ".implode(' AND ', $where)." ORDER BY " . $fixed . $news_sort . " " . $news_msort . " LIMIT " . $custom_from . "," . $custom_limit;

		$sql_result = $db->query( $sql_select );

		include (DLEPlugins::Check(ENGINE_DIR . '/modules/show.custom.php'));

		if( $config['files_allow'] ) if( strpos( $tpl->result['content'], "[attachment=" ) !== false ) {
			$tpl->result['content'] = show_attach( $tpl->result['content'], $attachments );
		}
		
		if ( $custom_news ) create_cache( "news", json_encode( array('content' => $tpl->result['content'], 'navigation' => $tpl->result['navigation'], 'last-modified' => $_DOCUMENT_DATE ) , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), $custom_cache_id, true );
		
		$config['allow_cache'] = $allow_cache;
		$tpl->is_custom = false;
		$row = $save_row;
		
		return $tpl->result['content'];
	
	}

}

function check_ip($ips) {
	
	$_IP = get_ip();

	$blockip = false;
	
	if( is_array( $ips ) ) {
		
		if( strpos($_IP, ":") === false ) {
			$delimiter = ".";
		} else $delimiter = ":";
		
		$this_ip_split = explode( $delimiter, $_IP );
		$ip_lenght = count($this_ip_split);
		
		foreach ( $ips as $ip_line ) {

			$ip_arr = trim( $ip_line['ip'] );
			
			if( $ip_arr == $_IP ) {
				
				$blockip = $_IP;
				break;
			
			} elseif ( count(explode ('/', $ip_arr)) == 2 ) {
				
				if( maskmatch($_IP, $ip_arr) ) {
					$blockip = $ip_line['ip'];
					break;
				}
				
			} else {
				
				$ip_check_matches = 0;
				$db_ip_split = explode( $delimiter, $ip_arr );

				for($i_i = 0; $i_i < $ip_lenght; $i_i ++) {
					if( $this_ip_split[$i_i] == $db_ip_split[$i_i] or $db_ip_split[$i_i] == '*' ) {
						$ip_check_matches += 1;
					}
				
				}
			
				if( $ip_check_matches == $ip_lenght ) {
					$blockip = $ip_line['ip'];
					break;
				}
			}		
		}
	}
	
	return $blockip;
}

function allowed_ip($ip_array) {
	
	$ip_array = trim( $ip_array );

	$_IP = get_ip();

	if( !$ip_array ) {
		return true;
	}
	
	if( strpos($_IP, ":") === false ) {
		$delimiter = ".";
	} else $delimiter = ":";
	
	$db_ip_split = explode( $delimiter, $_IP );
	$ip_lenght = count($db_ip_split);
	
	$ip_array = explode( "|", $ip_array );
	
	foreach ( $ip_array as $ip ) {
		
		$ip = trim( $ip );
		
		if( $ip == $_IP ) {
			
			return true;
		
		} elseif( count(explode ('/', $ip)) == 2 ) {
				
			if( maskmatch($_IP, $ip) ) return true;
				
		} else {
			
			$ip_check_matches = 0;
			$this_ip_split = explode( $delimiter, $ip );
			
			for($i_i = 0; $i_i < $ip_lenght; $i_i ++) {
				if( $this_ip_split[$i_i] == $db_ip_split[$i_i] OR $this_ip_split[$i_i] == '*' ) {
					$ip_check_matches += 1;
				}
			
			}
			
			if( $ip_check_matches == $ip_lenght ) return true;
		}
	
	}
	
	return false;
}

function maskmatch($IP, $CIDR) {
	
    list ($address, $netmask) = explode('/', $CIDR, 2);

	if( strpos($IP, ".") !== false AND strpos($CIDR, ".") !== false ) {
		
		return ( ip2long($IP) & ~((1 << (32 - $netmask)) - 1) ) == ip2long ($address);
	
	} elseif( strpos($IP, ":") !== false AND strpos($CIDR, ":") !== false ) {
		
        if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
          return false;
        }
		
        $bytesAddr = unpack('n*', @inet_pton($address));
        $bytesTest = unpack('n*', @inet_pton($IP));

        if (!$bytesAddr || !$bytesTest) {
            return false;
        }

        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
            $left = $netmask - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xffff >> $left) & 0xffff;
            if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
                return false;
            }
        }
		
		return true;
		
	}
	
	return false;

}

function check_netz($ip1, $ip2) {
	
	if( strpos($ip1, ":") === false ) {
		$delimiter = ".";
	} else $delimiter = ":";
	
	$ip1 = explode( $delimiter, $ip1 );
	$ip2 = explode( $delimiter, $ip2 );
	
	if( $ip1[0] != $ip2[0] ) return false;
	if( $ip1[1] != $ip2[1] ) return false;
	
	if($delimiter == ":") {
		if( $ip1[2] != $ip2[2] ) return false;
		if( $ip1[3] != $ip2[3] ) return false;
	}
	
	return true;

}

function show_attach($story, $id, $static = false) {
	global $db, $config, $lang, $user_group, $member_id, $_TIME, $news_date;

	$find_1 = array();
	$find_2 = array();
	$replace_1 = array();
	$replace_2 = array();

	$tpl = new dle_template();
	$tpl->dir = TEMPLATE_DIR;
	$root = $config['http_home_url'];
	
	if (strpos($root, "//") === 0) {
		$root = isSSL() ? $root = "https:".$root : $root = "http:".$root;
	} elseif (strpos($root, "/") === 0) {
		$root = isSSL() ? $root = "https://".$_SERVER['HTTP_HOST'].$root : "http://".$_SERVER['HTTP_HOST'].$root;
	}
	
	if( $static ) {
		
		if( is_array( $id ) and count( $id ) ) {
			$list = array();
			
			foreach ( $id as $value ) {
				$list[] = intval($value);
			}
			
			$id = implode( ',', $list );
			
			$where = "static_id IN ({$id})";
			
		} else $where = "static_id = '".intval($id)."'";
		
		$db->query( "SELECT * FROM " . PREFIX . "_static_files WHERE $where" );
		
		$area = "&area=static";
	
	} else {
		
		if( is_array( $id ) and count( $id ) ) {
			
			$list = array();
			
			foreach ( $id as $value ) {
				$list[] = intval($value);
			}
			
			$id = implode( ',', $list );
			
			$where = "news_id IN ({$id})";
			
		} else $where = "news_id = '".intval($id)."'";
		
		$db->query( "SELECT * FROM " . PREFIX . "_files WHERE $where" );
		
		$area = "";
	
	}

	if( !file_exists( $tpl->dir . "/attachment.tpl" ) ) {
	
		$tpl->template = <<<HTML
[allow-download]<span class="attachment"><a href="{link}" >{name}</a> [count] [{size}] ({$lang['att_dcount']} {count})[/count]</span>[/allow-download]
[not-allow-download]<span class="attachment">{$lang['att_denied']}</span>[/not-allow-download]
HTML;
	
		$tpl->copy_template = $tpl->template;
	
	} else {
		
		$tpl->load_template( 'attachment.tpl' );
		
	}
	
	while ( $row = $db->get_row() ) {

		$row['name'] = explode( "/", $row['name'] );
		$row['name'] = end( $row['name'] );
		
		$filename_arr = explode( ".", $row['onserver'] );
		$type = strtolower(end( $filename_arr ));

		$find_1[] = '[attachment=' . $row['id'] . ']';
		$find_2[] = "#\[attachment={$row['id']}:(.+?)\]#i";
		
		if( $row['is_public'] ) $uploaded_path = 'public_files/'; else $uploaded_path = 'files/';

		if (stripos ( $tpl->copy_template, "{md5}" ) !== false) {
			
			if($row['checksum']) $tpl->set( '{md5}', $row['checksum'] );
			else $tpl->set( '{md5}', @md5_file( ROOT_DIR . '/uploads/' . $uploaded_path.$row['onserver'] ) );
			
		}

		if (stripos ( $tpl->copy_template, "{size}" ) !== false) {
			
			if($row['size']) $tpl->set( '{size}', formatsize($row['size']) );
			else $tpl->set( '{size}', formatsize( @filesize( ROOT_DIR . '/uploads/' . $uploaded_path.$row['onserver'] ) ) );
			
		}
		
		$onlineview_ext = array('doc', 'docx','odt','pdf','xls','xlsx');
		
		if ( in_array($type, $onlineview_ext) ) {

			$tpl->set( '[allow-online]', "" );
			$tpl->set( '[/allow-online]', "" );
			$tpl->set( '{online-view-link}', "https://docs.google.com/viewer?url=".urlencode( $root."index.php?do=download&id=".$row['id'].$area."&viewonline=1" ) );

		} else {
			
			$tpl->set( '{online-view-link}', "" );
			$tpl->set_block( "'\\[allow-online\\](.*?)\\[/allow-online\\]'si", "" );
			
		}
		
		if ( $user_group[$member_id['user_group']]['allow_files'] ) {
			
			$tpl->set( '[allow-download]', "" );
			$tpl->set( '[/allow-download]', "" );
			$tpl->set_block( "'\\[not-allow-download\\](.*?)\\[/not-allow-download\\]'si", "" );
					
		} else {
			
			$tpl->set( '[not-allow-download]', "" );
			$tpl->set( '[/not-allow-download]', "" );
			$tpl->set_block( "'\\[allow-download\\](.*?)\\[/allow-download\\]'si", "" );
			
		}
		
		if ( $config['files_count'] ) {
			$tpl->set( '{count}', $row['dcount'] );
			$tpl->set( '[count]', "" );
			$tpl->set( '[/count]', "" );
			$tpl->set_block( "'\\[not-allow-count\\](.*?)\\[/not-allow-count\\]'si", "" );
					
		} else {
			$tpl->set( '{count}', "" );			
			$tpl->set( '[not-allow-count]', "" );
			$tpl->set( '[/not-allow-count]', "" );
			$tpl->set_block( "'\\[count\\](.*?)\\[/count\\]'si", "" );
			
		}
		
		if( date( 'Ymd', $row['date'] ) == date( 'Ymd', $_TIME ) ) {
			
			$tpl->set( '{date}', $lang['time_heute'] . langdate( ", H:i", $row['date'] ) );
		
		} elseif( date( 'Ymd', $row['date'] ) == date( 'Ymd', ($_TIME - 86400) ) ) {
			
			$tpl->set( '{date}', $lang['time_gestern'] . langdate( ", H:i", $row['date'] ) );
		
		} else {
			
			$tpl->set( '{date}', langdate( $config['timestamp_active'], $row['date'] ) );
		
		}

		$news_date = $row['date'];
		$tpl->copy_template = preg_replace_callback ( "#\{date=(.+?)\}#i", "formdate", $tpl->copy_template );
		
		$tpl->set( '{name}', $row['name'] );
		$tpl->set( '{extension}', $type );
		$tpl->set( '{link}', $config['http_home_url']."index.php?do=download&id=".$row['id'].$area );
		$tpl->set( '{id}', $row['id'] );

		$tpl->compile( 'attachment' );
		
		$replace_1[] = $tpl->result['attachment'];
		
		$tpl->result['attachment'] = str_replace( $row['name'], "\\1", $tpl->result['attachment'] );
		
		$replace_2[] = $tpl->result['attachment'];
		
		$tpl->result['attachment'] = '';

	}
	
	$tpl->clear();
	$db->free();

	$story = str_replace ( $find_1, $replace_1, $story );
	$story = preg_replace( $find_2, $replace_2, $story );
	
	return $story;

}

function xfieldsload($profile = false) {
	global $lang, $config;
	
	if( $profile ) $path = ENGINE_DIR . '/data/xprofile.txt';
	else $path = ENGINE_DIR . '/data/xfields.txt';
	
	$filecontents = file( $path );
	$fields = array();
	$tmp_arr = array();
	
	if( !is_array( $filecontents ) ) {
		
		return array();
	
	} elseif( count($filecontents) ) {
		
		foreach ( $filecontents as $name => $value ) {
			if( trim($value) ) {
				$tmp_arr = explode( "|", trim($value, "\t\n\r\0\x0B") );
				foreach ( $tmp_arr as $name2 => $value2 ) {
					$value2 = str_replace( "&#124;", "|", $value2 );
					$value2 = str_replace( "__NEWL__", "\r\n", $value2 );
					$value2 = html_entity_decode($value2, ENT_QUOTES, $config['charset']);
					$fields[$name][$name2] = $value2;
				}
			}
		}
		
		return $fields;
	}
	
	return array();
}

function xfieldsdataload($id) {
	
	if( !is_string($id) OR !$id ) return array();
	
	$xfieldsdata = explode( "||", $id );
	
	foreach ( $xfieldsdata as $xfielddata ) {
		list ( $xfielddataname, $xfielddatavalue ) = explode( "|", $xfielddata );
		$xfielddataname = str_replace( "&#124;", "|", $xfielddataname );
		$xfielddataname = str_replace( "__NEWL__", "\r\n", $xfielddataname );
		$xfielddatavalue = str_replace( "&#124;", "|", $xfielddatavalue );
		$xfielddatavalue = str_replace( "__NEWL__", "\r\n", $xfielddatavalue );
		$data[$xfielddataname] = trim($xfielddatavalue);
	}
	
	return $data;
}

function clear_content ( $content, $len = 300 ) {
	
	global $config;
	
	if(!$content OR !is_string($content) ) {
		return '';
	}
	
	$remove = array ("\x60", "\t", "\n", "\r", '\t', '\n', '\r', "{PAGEBREAK}", "&nbsp;", "<br />", "<br>", " ," );
	$len = intval($len);
	
	$content = stripslashes($content);
	
	$content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, $config['charset'] );

	$content = preg_replace( "#\[hide(.*?)\](.+?)\[/hide\]#is", "", $content );
	$content = preg_replace( "'\[attachment=(.*?)\]'si", "", $content );
	$content = preg_replace( "'\[page=(.*?)\](.*?)\[/page\]'si", "", $content );
	$content = preg_replace( "#<!--dle_spoiler(.+?)<!--spoiler_text-->#is", "", $content );
	$content = preg_replace( "#<!--spoiler_text_end-->(.+?)<!--/dle_spoiler-->#is", "", $content );
	$content = preg_replace( "'{banner_(.*?)}'si", "", $content );
	$content = preg_replace( "'\\[banner_(.*?)\\](.*?)\\[/banner_(.*?)\\]'si", "", $content );
	$content = preg_replace( "#<pre(.*?)>(.+?)</pre>#is", "", $content );
	$content = str_replace( "&#1072;", "a", $content );
	$content = str_replace( "&#111;", "o", $content );
	$content = preg_replace( "#&(.+?);#", "", $content );
	$content = str_replace( "><", "> <", $content );

	$content = str_replace( $remove, ' ', $content );
	$content = strip_tags($content);
	
	$content = preg_replace("#(^|\s|>)((http|https)://\w+[^\s\[\]\<]+)#i", '', $content);
	
	$content = str_replace("&amp;amp;", "&amp;", htmlspecialchars($content, ENT_COMPAT, $config['charset'] ));
	
	$content = preg_replace('/\s+/u', ' ', $content);

	if( $len AND $len > 1 ) {

		if(dle_strlen( $content, $config['charset'] ) > $len ) {
	
			$content = dle_substr( $content, 0, $len, $config['charset'] );
				
			if( ($temp_dmax = dle_strrpos( $content, ' ', $config['charset'] )) ) $content = dle_substr( $content, 0, $temp_dmax, $config['charset'] );
				
		}

	}
	
	return trim($content);
}

function create_keywords($story) {
	global $metatags, $config;
	
	$keyword_count = 20;
	$newarr = array ();
	
	$quotes = array ("&quot;", ",", ".", "/", "#", ";", ":", "@", "~", "[", "]", "{", "}", "=", "-", "+", ")", "(", "*", "^", "%", "$", "?", "!");
	$fastquotes = array ("\x22", "$", "{", "}", "[", "]");
	$remove = array ('\t', '\n', '\r' );
	
	$story = str_replace( $remove, ' ', $story );
	
	$story = clear_content(stripslashes ($story), 0);
	
	$story = str_replace( $fastquotes, '', $story );
	
	$metatags['description'] = $story;

	if( dle_strlen( $metatags['description'], $config['charset'] ) > 300 ) {
		
		$metatags['description'] = dle_substr( $story, 0, 300, $config['charset'] );
	
		if( ($temp_dmax = dle_strrpos( $metatags['description'], ' ', $config['charset'] )) ) $metatags['description'] = dle_substr( $metatags['description'], 0, $temp_dmax, $config['charset'] );

	}
	
	$story = str_replace( $quotes, '', $story );
	
	$arr = explode( " ", $story );
	
	foreach ( $arr as $word ) {
		if( dle_strlen( $word, $config['charset'] ) > 4 ) $newarr[] = $word;
	}
	
	$arr = array_count_values( $newarr );
	arsort( $arr );
	
	$arr = array_keys( $arr );
	
	$total = count( $arr );
	
	$offset = 0;
	
	$arr = array_slice( $arr, $offset, $keyword_count );
	
	$metatags['keywords'] = implode( ", ", $arr );
	
}

function news_permission($id) {
	
	if( $id == "" ) return;
	
	$data = array ();
	$groups = explode( "||", $id );
	foreach ( $groups as $group ) {
		list ( $groupid, $groupvalue ) = explode( ":", $group );
		$data[$groupid] = $groupvalue;
	}
	return $data;
}

function bannermass($fest, $massiv) {
	
	if( is_array($massiv) AND count($massiv) ) {
		return $fest . $massiv[array_rand( $massiv )]['text'];
	} else return $fest;

}

function get_sub_cats($id, $subcategory = '', $with_id = true) {
	
	global $cat_info;
	$subfound = array ();
	
	if( !$subcategory AND $with_id) $subcategory = $id;
	
	foreach ( $cat_info as $cats ) {
		if( $cats['parentid'] == $id ) {
			$subfound[] = $cats['id'];
		}
	}
	
	foreach ( $subfound as $parentid ) {
		
		if( $subcategory ) $subcategory .= "|";
		
		$subcategory .= $parentid;
		$subcategory = get_sub_cats( $parentid, $subcategory );
		
	}
	
	return $subcategory;

}

function check_xss() {

	$url = html_entity_decode( urldecode( $_SERVER['QUERY_STRING'] ), ENT_QUOTES, 'ISO-8859-1' );
	$url = str_replace( "\\", "/", $url );

	if (isset($_GET['do']) AND $_GET['do'] == "xfsearch") {

		$f = html_entity_decode( urldecode( $_GET['xf'] ), ENT_QUOTES, 'ISO-8859-1' );

		$count1 = substr_count ($f, "'");
		$count2 = substr_count ($url, "'");

		if ( $count1 == $count2 AND (strpos( $url, '<' ) === false) AND (strpos( $url, '>' ) === false) AND (strpos( $url, '.php' ) === false) ) return;

	}

	if (isset($_GET['do']) AND $_GET['do'] == "tags") {

		$f = html_entity_decode( urldecode( $_GET['tag'] ), ENT_QUOTES, 'ISO-8859-1' );

		$count1 = substr_count ($f, "'");
		$count2 = substr_count ($url, "'");

		if ( $count1 == $count2 AND (strpos( $url, '<' ) === false) AND (strpos( $url, '>' ) === false) AND (strpos( $url, './' ) === false) AND (strpos( $url, '../' ) === false) AND (strpos( $url, '.php' ) === false) ) return;

	}
	
	if( $url ) {
		
		if( (strpos( $url, '<' ) !== false) || (strpos( $url, '>' ) !== false) || (strpos( $url, './' ) !== false) || (strpos( $url, '../' ) !== false) || (strpos( $url, '\'' ) !== false) || (strpos( $url, '.php' ) !== false) ) {
			if( $_GET['do'] != "search" OR $_GET['subaction'] != "search" ) {
				header( "HTTP/1.1 403 Forbidden" );
				die( "Hacking attempt!" );
			}
		}
	
	}
	
	$url = html_entity_decode( urldecode( $_SERVER['REQUEST_URI'] ), ENT_QUOTES, 'ISO-8859-1' );
	$url = str_replace( "\\", "/", $url );
	
	if( $url ) {
		
		if( (strpos( $url, '<' ) !== false) || (strpos( $url, '>' ) !== false) || (strpos( $url, '\'' ) !== false) ) {
			if( $_GET['do'] != "search" OR $_GET['subaction'] != "search" ) {
				header( "HTTP/1.1 403 Forbidden" );
				die( "Hacking attempt!" );
			}
		
		}
	
	}

}

function if_category_rating( $category ) {
	global $cat_info;
	
	$category = explode( ',', $category );
	
	$found = false;
	
	foreach ( $category as $element ) {
			
		if( isset( $cat_info[$element]['rating_type'] ) AND $cat_info[$element]['rating_type'] > -1 ) {
			return $cat_info[$element]['rating_type'];
		}
	
	}
	
	return $found;
}

function check_category( $matches=array() ) {
	global $category_id;

	$block = $matches[3];
	$category = $category_id;

	$temp_array = array();

	$matches[2] = str_replace(" ", "", $matches[2] );
	$matches[2] = explode (',', $matches[2]);

	foreach ($matches[2] as $value) {

		if( count(explode('-', $value)) == 2 ) $temp_array[] = get_mass_cats($value);
		else $temp_array[] = intval($value);

	}

	$temp_array = implode(',', $temp_array);

	if ($matches[1] == "category" OR $matches[1] == "catlist") $action = true; else $action = false;
	
	$cats = explode( ',', $temp_array );
	$category = explode( ',', $category );
	$found = false;
	
	foreach ( $category as $element ) {
		
		if( $action ) {
			
			if( in_array( $element, $cats ) ) {
				
				return $block;
			}
		
		} else {
			
			if( in_array( $element, $cats ) ) {
				$found = true;
			}
		
		}
	
	}

	if ( !$action AND !$found ) {	

		return $block;
	}

	return "";

}

function clean_url($url) {
	
	if( $url == '' ) return;
	
	$url = str_replace( "http://", "", strtolower( $url ) );
	$url = str_replace( "https://", "", $url );
	if( substr( $url, 0, 2 ) == '//' ) $url = str_replace( "//", "", $url );
	if( substr( $url, 0, 4 ) == 'www.' ) $url = substr( $url, 4 );
	$url = explode( '/', $url );
	$url = reset( $url );
	$url = explode( ':', $url );
	$url = reset( $url );
	
	return $url;
}

function get_url($id) {
	
	global $cat_info;

	$cat_id = false;
	
	if( !$id ) return "";

	$id = explode (",", $id);

	foreach ($id as $val) {
		
		$val = intval($val);
		
		if ( $val AND $cat_info[$val]['id'] ) {
			$cat_id = $val;
			break;
		}
		
	}
	
	if( !$cat_id ) return "";
	
	$id = $cat_id;
	
	$parent_id = $cat_info[$id]['parentid'];
	
	$url = $cat_info[$id]['alt_name'];
	
	while ( $parent_id ) {
		
		if( !$cat_info[$parent_id]['id'] ) {
			break;
		}
		
		$url = $cat_info[$parent_id]['alt_name'] . "/" . $url;
		
		$parent_id = $cat_info[$parent_id]['parentid'];

		if($parent_id) {	
			if( $cat_info[$parent_id]['parentid'] == $cat_info[$parent_id]['id'] ) break;
		}
	
	}
	
	return $url;
}

function get_categories($id, $separator=" &raquo;") {
	
	global $cat_info, $config, $PHP_SELF;
	
	if( ! $id ) return;
	
	$parent_id = $cat_info[$id]['parentid'];
	
	if( $config['allow_alt_url'] ) $list = "<a href=\"" . $config['http_home_url'] . get_url( $id ) . "/\">{$cat_info[$id]['name']}</a>";
	else $list = "<a href=\"$PHP_SELF?do=cat&amp;category={$cat_info[$id]['alt_name']}\">{$cat_info[$id]['name']}</a>";
	
	while ( $parent_id ) {
		
		if( $config['allow_alt_url'] ) $list = "<a href=\"" . $config['http_home_url'] . get_url( $parent_id ) . "/\">{$cat_info[$parent_id]['name']}</a>" . $separator . $list;
		else $list = "<a href=\"$PHP_SELF?do=cat&amp;category={$cat_info[$parent_id]['alt_name']}\">{$cat_info[$parent_id]['name']}</a>" . $separator . $list;
		
		$parent_id = $cat_info[$parent_id]['parentid'];

		if( !isset($cat_info[$parent_id]['id']) OR ( isset($cat_info[$parent_id]['id']) AND !$cat_info[$parent_id]['id']) ) {
			break;
		}
		
		if($parent_id) {		
			if( $cat_info[$parent_id]['parentid'] == $cat_info[$parent_id]['id'] ) break;
		}

	}

	return $list;
}

function get_breadcrumbcategories($id, $separator="&raquo;", $last_link = true) {
	
	global $cat_info, $config, $PHP_SELF, $elements, $position;
	
	if( !$id ) return;
	
	$parent_id = $cat_info[$id]['parentid'];
	$list = $temp = array();
	$i = 0;

	if( $config['allow_alt_url'] ) $list[0]['link'] = $config['http_home_url'] . get_url( $id ) . "/";
	else $list[0]['link'] = "{$PHP_SELF}?do=cat&amp;category={$cat_info[$id]['alt_name']}";
		
	if (!$last_link)	{
		
		$list[0]['uri'] = $list[0]['link'];
		unset($list[0]['link']);
		
	}
	
	$list[0]['name'] = $cat_info[$id]['name'];
	
	while ( $parent_id ) {
		$i++;
		
		if( $config['allow_alt_url'] ) $list[$i]['link'] = $config['http_home_url'] . get_url( $parent_id ) . "/";
		else $list[$i]['link'] = "{$PHP_SELF}?do=cat&amp;category={$cat_info[$parent_id]['alt_name']}";
		
		$list[$i]['name'] = $cat_info[$parent_id]['name'];
		$parent_id = $cat_info[$parent_id]['parentid'];

		if( !isset($cat_info[$parent_id]['id']) OR ( isset($cat_info[$parent_id]['id']) AND !$cat_info[$parent_id]['id']) ) {
			break;
		}
		
		if($parent_id) {		
			if( $cat_info[$parent_id]['parentid'] == $cat_info[$parent_id]['id'] ) break;
		}
		
	}

	if(count($list)) {
		
		$list = array_reverse($list);
		
		foreach($list as $value) {
			
			if( isset($value['link']) AND $value['link']) {
				$temp[] = "<a href=\"{$value['link']}\">{$value['name']}</a>";;
			} else {
				$temp[] = $value['name'];
				$value['link'] = $value['uri'];
			}
			
			$elements[] = array(
				'@type'		=> "ListItem",
				'position'	=> $position,
				'item'		=> array(
					'@id'	=> $value['link'],
					'name'	=> $value['name'],
				)
			);
			$position++;
		
		}
		
		$list = $temp;
	}	
	
	return implode($separator, $list);
}

function news_sort($do) {
	
	global $config, $lang, $category_id, $cat_info;

	if( ! $do ) $do = "main";
	
	$find_sort = "dle_sort_" . $do;
	$direction_sort = "dle_direction_" . $do;

	if($do == "cat" AND $category_id) {
		$find_sort .= "_".$category_id;
		$direction_sort .= "_".$category_id;
	}
	
	$find_sort = str_replace( ".", "", $find_sort );
	$direction_sort = str_replace( ".", "", $direction_sort );
	
	$sort = array ();
	$allowed_sort = array ('date', 'rating', 'news_read', 'comm_num', 'title' );
	
	$soft_by_array = array (

		'date' => array ( 'name' => $lang['sort_by_date'], 'value' => "date", 'direction' => "desc", 'image' => "" ),
		'rating' => array ( 'name' => $lang['sort_by_rating'], 'value' => "rating", 'direction' => "desc", 'image' => "" ), 
		'news_read' => array ( 'name' => $lang['sort_by_read'], 'value' => "news_read", 'direction' => "desc", 'image' => "" ), 
		'comm_num' => array ( 'name' => $lang['sort_by_comm'], 'value' => "comm_num", 'direction' => "desc", 'image' => "" ), 
		'title' => array ( 'name' => $lang['sort_by_title'], 'value' => "title", 'direction' => "desc", 'image' => "" )

	 );

	if( !$config['allow_comments'] ) { unset($allowed_sort[3]); unset($soft_by_array['comm_num']); }
		
	if( isset( $_SESSION[$direction_sort] ) AND ($_SESSION[$direction_sort] == "desc" OR $_SESSION[$direction_sort] == "asc") ) $direction = $_SESSION[$direction_sort];
	else $direction = $config['news_msort'];

	if( isset( $_SESSION[$find_sort] ) AND $_SESSION[$find_sort] AND in_array( $_SESSION[$find_sort], $allowed_sort ) ) $soft_by = $_SESSION[$find_sort];
	elseif( $do == "cat" AND $cat_info[$category_id]['news_sort'] ) $soft_by = $cat_info[$category_id]['news_sort'];
	else $soft_by = $config['news_sort'];
	
	if( strtolower( $direction ) == "asc" ) {
		
		$soft_by_array[$soft_by]['image'] = " class=\"desc\"";
		$soft_by_array[$soft_by]['direction'] = "desc";
	
	} else {
		
		$soft_by_array[$soft_by]['image'] = " class=\"asc\"";
		$soft_by_array[$soft_by]['direction'] = "asc";
	}
	
	foreach ( $soft_by_array as $value ) {
		
		$sort[] = "<li" . $value['image'] . "><a href=\"#\" onclick=\"dle_change_sort('{$value['value']}','{$value['direction']}'); return false;\">" . $value['name'] . "</a></li>";
	}
	
	$sort = "<form name=\"news_set_sort\" id=\"news_set_sort\" method=\"post\"><ul class=\"sort\">" . implode( $sort ) . "</ul>";
	
	$sort .= <<<HTML
<input type="hidden" name="dlenewssortby" id="dlenewssortby" value="{$config['news_sort']}" />
<input type="hidden" name="dledirection" id="dledirection" value="{$config['news_msort']}" />
<input type="hidden" name="set_new_sort" id="set_new_sort" value="{$find_sort}" />
<input type="hidden" name="set_direction_sort" id="set_direction_sort" value="{$direction_sort}" />
</form>
HTML;
	
	return $sort;
}

function compare_tags($a, $b) {
	
	if( $a['tag'] == $b['tag'] ) return 0;
	
	return strcasecmp( $a['tag'], $b['tag'] );

}

function convert_unicode($t, $to = '') {
// deprecated
	return $t;
}

function build_js($js, $config) {

	$js_array = array();
	$i=0;
	$defer = "";
	$v = substr(md5($config['version_id'].SECURE_AUTH_KEY),0,5);
	
	$config['jquery_version'] = intval($config['jquery_version']);
	
	$ver = $config['jquery_version'] ? $config['jquery_version'] : "";

	if ($config['js_min']) {

		$js_array[] = "<script src=\"{$config['http_home_url']}engine/classes/min/index.php?g=general{$ver}&amp;v={$v}\"></script>";

		$default_array = array (
			"engine/classes/js/jqueryui{$ver}.js",
			'engine/classes/js/dle_js.js',
		);

		if ( count($js) ) $js = array_merge($default_array, $js); else $js = $default_array;
		
		$js_array[] = "<script src=\"{$config['http_home_url']}engine/classes/min/index.php?f=".implode(",", $js)."&amp;v={$v}\" defer></script>";

		return implode("\n", $js_array);

	} else {

		$default_array = array (
			"engine/classes/js/jquery{$ver}.js",
			"engine/classes/js/jqueryui{$ver}.js",
			'engine/classes/js/dle_js.js',
		);

		if ( count($js) ) $js = array_merge($default_array, $js); else $js = $default_array;

		foreach ($js as $value) {
			if($i > 0) $defer =" defer";
			$js_array[] = "<script src=\"{$config['http_home_url']}{$value}?v={$v}\"{$defer}></script>";
			$i++;
		}

		return implode("\n", $js_array);
	}
}

function build_css($css, $config) {
	
	$css_array = array();
	$v = substr(md5($config['version_id'].SECURE_AUTH_KEY),0,5);

	if ($config['js_min'] AND count($css) ) {

		return "<link href=\"{$config['http_home_url']}engine/classes/min/index.php?f=".implode(",", $css)."&amp;v={$v}\" rel=\"stylesheet\" type=\"text/css\">";


	} elseif( count($css) ) {

		foreach ($css as $value) {
		
			$css_array[] = "<link href=\"{$config['http_home_url']}{$value}?v={$v}\" rel=\"stylesheet\" type=\"text/css\">";
		
		}

		return implode("\n", $css_array);
	}

}

function check_static($matches=array()) {
	global $dle_module;

	$names = $matches[2];
	$block = $matches[3];

	if ($matches[1] == "static") $action = true; else $action = false;

	$names = str_replace(" ", "", $names );
	$names = explode( ',', $names );

	if ( isset($_GET['page']) ) $page = trim($_GET['page']); else $page = "";
	
	if( $action ) {
			
		if( in_array( $page, $names ) AND $dle_module == "static" ) {
				
			return $block;
		}
		
	} else {
			
		if( !in_array( $page, $names ) OR $dle_module != "static") {
				
			return $block;
		}
		
	}
	
	return "";
}


function dle_strlen($value, $charset = "utf-8" ) {

	if( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $value, $charset );
	} elseif( function_exists( 'iconv_strlen' ) ) {
		return iconv_strlen($value, $charset);
	}

	return strlen($value);
}

function dle_substr($str, $start, $length, $charset = "utf-8" ) {

	if( function_exists( 'mb_substr' ) ) {
		return mb_substr( $str, $start, $length, $charset );
	
	} elseif( function_exists( 'iconv_substr' ) ) {
		return iconv_substr($str, $start, $length, $charset);
	}

	return substr($str, $start, $length);

}

function dle_strrpos($str, $needle, $charset = "utf-8" ) {

	if( function_exists( 'mb_strrpos' ) ) {
		return mb_strrpos( $str, $needle, 0, $charset );
	
	} elseif( function_exists( 'iconv_strrpos' ) ) {
		return iconv_strrpos($str, $needle, $charset);
	}

	return strrpos($str, $needle);

}

function dle_strpos($str, $needle, $charset = "utf-8" ) {

	if( function_exists( 'mb_strpos' ) ) {
		return mb_strpos( $str, $needle, 0, $charset );
	} elseif( function_exists( 'iconv_strrpos' ) ) {
		return iconv_strpos($str, $needle, 0, $charset);
	}

	return strpos($str, $needle);

}

function dle_strtolower($str, $charset = "utf-8" ) {

	if( function_exists( 'mb_strtolower' ) ) {
		return mb_strtolower( $str, $charset );
	}

	return strtolower($str);

}

function check_allow_login($ip, $max ) {
	global $db, $config;

	$config['login_ban_timeout'] = intval($config['login_ban_timeout']);
	
	$max = intval($max);
	
	if( $max < 2 ) $max = 2;
	
	$block_date = time()-($config['login_ban_timeout'] * 60);

	$row = $db->super_query( "SELECT * FROM " . PREFIX . "_login_log WHERE ip='{$ip}'" );

	if ( isset( $row['count'] ) AND $row['count'] AND $row['date'] < $block_date ) {
		$db->query( "DELETE FROM " . PREFIX . "_login_log WHERE ip = '{$ip}'" );
		return true;
	}

	if ( isset( $row['count'] ) AND $row['count'] >= $max AND $row['date'] > $block_date ) return false;
	else return true;

}

function detect_encoding($string) {  
  static $list = array('utf-8', 'windows-1251');
   
  foreach ($list as $item) {

	if( function_exists( 'mb_convert_encoding' ) ) {

		$sample = mb_convert_encoding( $string, $item, $item );

	} elseif( function_exists( 'iconv' ) ) {
	
		$sample = iconv($item, $item, $string);
	
	}

	if (md5($sample) == md5($string)) return $item;

   }

   return null;
}
 
function get_ip() {
	global $config;
	
	if ($config['own_ip']) $ip = $_SERVER[$config['own_ip']]; else $ip = $_SERVER['REMOTE_ADDR'];

	$temp_ip = explode(",", $ip);

	if(count($temp_ip) > 1) $ip = trim($temp_ip[0]);

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ) {
		return filter_var( $ip , FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	return 'not detected';
}

function get_votes($all) {
	
	$data = array ();
	
	if( $all != "" ) {
		$all = explode( "|", $all );
		
		foreach ( $all as $vote ) {
			list ( $answerid, $answervalue ) = explode( ":", $vote );
			$data[$answerid] = intval( $answervalue );
		}
	}
	
	return $data;
}

function http_get_contents( $file, $post_params = false ) {
		
	$data = false;

	if (stripos($file, "http://") !== 0 AND stripos($file, "https://") !== 0) {
		return false;
	}
		
	if( function_exists( 'curl_init' ) ) {
			
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $file );

		if( is_array($post_params) ) {

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_params));

		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_TIMEOUT, 5 );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			
		$data = curl_exec( $ch );
		curl_close( $ch );

		if( $data !== false ) return $data;
		
	} 

	if( preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen')) ) {

		if( is_array($post_params) ) {

			$file .= '?'.http_build_query($post_params);
		}

		$data = @file_get_contents( $file );
			
		if( $data !== false ) return $data;

	}

	return false;	
}

function CheckGzip(){ 

	if (headers_sent() || connection_aborted() || ini_get('zlib.output_compression')) return false; 

	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'br' ) !== false AND function_exists( 'brotli_compress' ) ) return "br";
	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false AND function_exists( 'gzencode' ) ) return "gzip";
	
	return false; 
}


function GzipOut($debug=false, $mysql=false){
	global $config, $Timer, $db, $tpl, $_DOCUMENT_DATE;
	
	$s = "";

	@header("Content-type: text/html; charset=".$config['charset']);
	
	if ($debug) $s = "\n<!-- The script execution time ".$Timer->get()." seconds -->\n<!-- The time compilation of templates ".round($tpl->template_parse_time, 5)." seconds -->\n<!-- Time executing MySQL query: ".round($db->MySQL_time_taken, 5)." seconds -->\n<!-- The total number of MySQL queries ".$db->query_num." -->";
	
	if( $debug AND function_exists( "memory_get_peak_usage" ) ) $s .="\n<!-- RAM uses ".round(memory_get_peak_usage()/(1024*1024),2)." MB -->";

	if($debug AND $mysql AND count($db->query_list) ) {
		
		$temp_list = array();
		
		foreach ($db->query_list as $value) {
			$temp_list[] = "[query] => ".$value['query']."\n[time] => ".$value['time']."\n[num] => ".$value['num'];
		}
		
		$s .="\n<!-- MySQL queries list:\n\n".implode("\n\n", $temp_list)."\n\n-->";
		
	}

	if($_DOCUMENT_DATE) {
		
		$IfModifiedSince = false;
	
		if ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) $IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
		elseif( isset($_ENV['HTTP_IF_MODIFIED_SINCE']) ) $IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));
	
		if ($IfModifiedSince && $IfModifiedSince >= $_DOCUMENT_DATE) {
			ob_end_clean();
			header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
			die();
		}
	
		@header ("Last-Modified: " . date('r', $_DOCUMENT_DATE) ." GMT");
	}
	
	if($config['disable_frame']) {
		
		if( !preg_match('%^(http:|https:)?//(www.)?(webvisor.com)%', $_SERVER['HTTP_REFERER']) ) {
			@header ("X-Frame-Options: SAMEORIGIN");
		}
	
	}
	
	if ( !$config['allow_gzip'] ) {if ($debug) echo $s; ob_end_flush(); return;}

    $ENCODING = CheckGzip();

    if ($ENCODING) {
        $s .= "\n<!-- For compression was used $ENCODING -->\n"; 
        $Contents = ob_get_clean(); 

        if ($debug){
			
			if ($ENCODING == "br" ) {
				$after_len = strlen(brotli_compress($Contents));
			} else {
				$after_len = strlen(gzencode($Contents));
			}
			
            $s .= "<!-- The total size of the page: ".strlen($Contents)." bytes "; 
            $s .= "After compression: ".$after_len." bytes -->"; 
            $Contents .= $s; 
        }

        header("Content-Encoding: {$ENCODING}");
		
		if ($ENCODING == "br" ) {
			$Contents = brotli_compress($Contents);
		} else {
			$Contents = gzencode($Contents);
		}	
		
		echo $Contents;
		
		ob_end_flush();
        die();

    } else {
		
	   ob_end_flush(); 
       die();

    }
}

function check_xfvalue( $matches=array() ) {
	global $xfieldsdata, $preg_safe_name, $value;
	
	$matches[1] = trim($matches[1]);
	$check_values = array();

	if( preg_match( "#^{$preg_safe_name}\s*\!\=\s*['\"](.+?)['\"]#i", $matches[1], $match ) ) {

		$tmp_values = explode( ",", trim($match[1]) );
		
		foreach($tmp_values as $check_value) {
			$check_values[] = trim($check_value);
		}
		
		if( !in_array($xfieldsdata[$value[0]], $check_values) ) {
			return $matches[2];
		} else return "";

	}
	
	if( preg_match( "#^{$preg_safe_name}\s*\=\s*['\"](.+?)['\"]#i", $matches[1], $match ) ) {

		$tmp_values = explode( ",", trim($match[1]) );
		
		foreach($tmp_values as $check_value) {
			$check_values[] = trim($check_value);
		}
		
		if( in_array($xfieldsdata[$value[0]], $check_values) ) {
			return $matches[2];
		} else return "";
	}
	
	return $matches[0];
}

function enable_lazyload( $matches=array() ) {

	$matches[0] = str_replace ( 'src="', 'data-src="', $matches[0] );
	
	return $matches[0];
}

function deletenewsbyid( $id ) {
	global $config, $db;

	$id = intval($id);
	DLEFiles::init( $config['file_driver'], false );
	
	$row = $db->super_query( "SELECT user_id FROM " . PREFIX . "_post_extras WHERE news_id = '{$id}'" );
	
	$db->query( "UPDATE " . USERPREFIX . "_users SET news_num=news_num-1 WHERE user_id='{$row['user_id']}'" );
	
	$db->query( "DELETE FROM " . PREFIX . "_post WHERE id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_post_extras WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_post_extras_cats WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_poll WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_poll_log WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_post_log WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_post_pass WHERE news_id='{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_tags WHERE news_id = '{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_xfsearch WHERE news_id = '{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_logs WHERE news_id = '{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_subscribe WHERE news_id='{$id}'");

	deletecommentsbynewsid( $id );

	$row = $db->super_query( "SELECT images  FROM " . PREFIX . "_images WHERE news_id = '{$id}'" );

	if( isset($row['images']) AND $row['images']) {
		
		$listimages = explode( "|||", $row['images'] );
	
		foreach ( $listimages as $dataimage ) {
			
			$dataimage = get_uploaded_image_info($dataimage);
			
			$query = $db->safesql( $dataimage->path );
			$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_post WHERE short_story LIKE '%{$query}%' OR full_story LIKE '%{$query}%' OR xfields LIKE '%{$query}%'");

			if( isset($row['count']) AND $row['count'] ) {
				continue;
			}
			
			if( $dataimage->remote AND DLEFiles::$driver == 'remote' ) $disk = 'remote';
			else $disk = 'local';
	
			DLEFiles::Delete( "posts/" . $dataimage->path, $disk );
			
			if( $dataimage->thumb ) {
				
				DLEFiles::Delete( "posts/{$dataimage->folder}/thumbs/{$dataimage->name}", $disk );
				
			}
			
			if( $dataimage->medium ) {
				
				DLEFiles::Delete( "posts/{$dataimage->folder}/medium/{$dataimage->name}", $disk );
				
			}
						
		}
	
		$db->query( "DELETE FROM " . PREFIX . "_images WHERE news_id = '{$id}'" );
	
	}

	$db->query( "SELECT * FROM " . PREFIX . "_files WHERE news_id = '{$id}'" );

	while ( $row = $db->get_row() ) {

		if( $row['driver'] AND DLEFiles::$driver == 'remote' ) $disk = 'remote';
		else $disk = 'local';
		
		if( trim($row['onserver']) == ".htaccess") die("Hacking attempt!");
		
		if( $row['is_public'] ) $uploaded_path = 'public_files/'; else $uploaded_path = 'files/';

		DLEFiles::Delete( $uploaded_path.$row['onserver'], $disk );

	}

	$db->query( "DELETE FROM " . PREFIX . "_files WHERE news_id = '{$id}'" );

	$sql_result = $db->query( "SELECT user_id, favorites FROM " . USERPREFIX . "_users WHERE favorites LIKE '%{$id}%'" );
	
	while ( $row = $db->get_row($sql_result) ) {
		
		$temp_fav = explode( ",", $row['favorites'] );
		$new_fav = array();
		
		foreach ( $temp_fav as $value ) {
			$value = intval($value);
			if($value != $id ) $new_fav[] = $value;
		}
		
		if(count($new_fav)) $new_fav = $db->safesql(implode(",", $new_fav));
		else $new_fav = "";
		
		$db->query( "UPDATE " . USERPREFIX . "_users SET favorites='{$new_fav}' WHERE user_id='{$row['user_id']}'" );

	}
}

function deletecomments( $id ) {
	global $config, $db;
	
	$id = intval($id);
	DLEFiles::init( $config['file_driver'], false );
	
	$row = $db->super_query( "SELECT id, post_id, user_id, is_register, approve FROM " . PREFIX . "_comments WHERE id = '{$id}'" );
	
	$db->query( "DELETE FROM " . PREFIX . "_comments WHERE id = '{$id}'" );
	$db->query( "DELETE FROM " . PREFIX . "_comment_rating_log WHERE c_id = '{$id}'" );	

	if( $row['is_register'] ) {
		$db->query( "UPDATE " . USERPREFIX . "_users SET comm_num=comm_num-1 WHERE user_id ='{$row['user_id']}'" );
	}
	
	if($row['approve']) $db->query( "UPDATE " . PREFIX . "_post SET comm_num=comm_num-1 WHERE id='{$row['post_id']}'" );

	$db->query( "SELECT id, name, driver FROM " . PREFIX . "_comments_files WHERE c_id = '{$id}'" );
	
	while ( $row = $db->get_row() ) {
		
		$dataimage = get_uploaded_image_info( $row['name'] );
		
		if( $row['driver'] AND DLEFiles::$driver == 'remote' ) $disk = 'remote';
		else $disk = 'local';
		
		DLEFiles::Delete( "posts/" . $dataimage->path, $disk );
		
		if( $dataimage->thumb ) {
			
			DLEFiles::Delete( "posts/{$dataimage->folder}/thumbs/{$dataimage->name}", $disk );
			
		}
			
	}
	
	$db->query( "DELETE FROM " . PREFIX . "_comments_files WHERE c_id = '{$id}'" );
	
	if ( $config['tree_comments'] ) {

		$sql_result = $db->query( "SELECT id FROM " . PREFIX . "_comments WHERE parent = '{$id}'" );
	
		while ( $row = $db->get_row( $sql_result ) ) {
			deletecomments( $row['id'] );
		}

	}

}

function deletecommentsbynewsid( $id ) {
	global $config, $db;
	
	$id = intval($id);
	DLEFiles::init( $config['file_driver'], false );
	
	$result = $db->query( "SELECT id FROM " . PREFIX . "_comments WHERE post_id='{$id}'" );
	
	while ( $row = $db->get_array( $result ) ) {
		
		$db->query( "DELETE FROM " . PREFIX . "_comment_rating_log WHERE c_id = '{$row['id']}'" );

		$db->query( "SELECT id, name, driver FROM " . PREFIX . "_comments_files WHERE c_id = '{$row['id']}'" );
		
		while ( $file = $db->get_row() ) {
			
			$dataimage = get_uploaded_image_info( $row['name'] );
			
			if( $row['driver'] AND DLEFiles::$driver == 'remote' ) $disk = 'remote';
			else $disk = 'local';
			
			DLEFiles::Delete( "posts/" . $dataimage->path, $disk );
			
			if( $dataimage->thumb ) {
				
				DLEFiles::Delete( "posts/{$dataimage->folder}/thumbs/{$dataimage->name}", $disk );
				
			}

		}
		
		$db->query( "DELETE FROM " . PREFIX . "_comments_files WHERE c_id = '{$row['id']}'" );
	
	}
	
	$result = $db->query( "SELECT COUNT(*) as count, user_id FROM " . PREFIX . "_comments WHERE post_id='{$id}' AND is_register='1' GROUP BY user_id" );
	
	while ( $row = $db->get_array( $result ) ) {
		
		$db->query( "UPDATE " . USERPREFIX . "_users SET comm_num=comm_num-{$row['count']} WHERE user_id='{$row['user_id']}'" );
	
	}
	
	$db->query( "DELETE FROM " . PREFIX . "_comments WHERE post_id='{$id}'" );


}

function normalize_name($var, $punkt = true) {
	
	if ( !is_string($var) ) return;

	$var = str_replace(chr(0), '', $var);
	
	$var = trim( strip_tags( $var ) );
	$var = preg_replace( "/\s+/u", "-", $var );
	$var = str_replace( "/", "-", $var );
	
	if ( $punkt ) $var = preg_replace( "/[^a-z0-9\_\-.]+/mi", "", $var );
	else $var = preg_replace( "/[^a-z0-9\_\-]+/mi", "", $var );

	$var = preg_replace( '#[\-]+#i', '-', $var );
	$var = preg_replace( '#[.]+#i', '.', $var );
	
	return $var;
}

function clearfilepath( $file, $ext=array() ) {

	$file = trim(str_replace(chr(0), '', (string)$file));
	$file = str_replace(array('/', '\\'), '/', $file);
	
	$path_parts = pathinfo( $file );

	if( count($ext) ) {
		if ( !in_array( $path_parts['extension'], $ext ) ) return '';
	}
	
	$filename = normalize_name($path_parts['basename'], true);
	
	if( !$filename) return '';
	
	$parts = array_filter(explode('/', $path_parts['dirname']), 'strlen');
	
	$absolutes = array();
	
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = normalize_name($part, false);
		}
	}

	$path = implode('/', $absolutes);
	
	if ( $path ) return implode('/', $absolutes).'/'.$filename;
	else return '';

}

function cleanpath($path) {
	$path = trim(str_replace(chr(0), '', (string)$path));
	$path = str_replace(array('/', '\\'), '/', $path);
	$parts = array_filter(explode('/', $path), 'strlen');
	$absolutes = array();
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = totranslit($part, false, false);
		}
	}

	return implode('/', $absolutes);
}

function get_uploaded_image_info( $file, $root_folder = 'posts', $force_size = false ) {
	global $config;
	
	$info = array();
	$file = explode("|", $file);
	$path = $file[0];
	$path = str_replace('&#58;',':', $path);

	if( stripos($path, "https://" ) === 0 OR stripos($path, "http://" ) === 0 OR stripos($path, "//" ) === 0 ) {
		
		$info['remote'] = true;
		$info['local'] 	= false;
		$info['exists'] = true;
		$info['url'] 	= $path;
		
		$path = explode("/{$root_folder}/", $path);
		
		$info['path'] = $path[1];
		$info['root'] = $path[0] . "/{$root_folder}/";
		
	} else {
		
		$info['remote'] = false;
		$info['exists'] = true;
		$info['path'] 	= $path;
		$info['root']   = $config['http_home_url'] . "uploads/{$root_folder}/";
		$info['url'] 	= $info['root'] . $info['path'];
		
		if( !file_exists( ROOT_DIR . "/uploads/{$root_folder}/" . $info['path'] ) ) {
			
			$info['url'] = 	$config['http_home_url'] . "engine/skins/images/noimage.jpg";
			$file[1] = 0;
			$file[2] = 0;
			$file[3] = "0x0";
			$file[4] = "0 b";
			$info['exists'] = false;
	
		}

	}

	if( count($file) == 1) {

		$info['local_check'] = true;
		$file[1] = 0;
		$file[2] = 0;

		$files_array = explode('/', $file[0]);

		if( count($files_array) == 2 ) {
			$folder_prefix = $files_array[0].'/';
			$file_name =  $files_array[1];
		} else {
			$folder_prefix = '';
			$file_name =  $files_array[0];
		}

		if( file_exists( ROOT_DIR . "/uploads/{$root_folder}/" . $folder_prefix . "thumbs/" . $file_name ) ) $file[1] = 1;
		if( file_exists( ROOT_DIR . "/uploads/{$root_folder}/" . $folder_prefix . "medium/" . $file_name ) ) $file[2] = 1;
		
		if( $force_size ) {
			
			if( file_exists( ROOT_DIR . "/uploads/{$root_folder}/" . $info['path'] ) ) {
				
				$img_info =  @getimagesize( ROOT_DIR . "/uploads/{$root_folder}/" . $info['path'] );
				$file[3] = "{$img_info[0]}x{$img_info[1]}";
				$file[4] = formatsize( filesize( ROOT_DIR . "/uploads/{$root_folder}/" . $info['path'] ) );
	
			} else {
				
				$file[3] = "0x0";
				$file[4] = "0 b";
				
			}
				
		}
		
		
	} else $info['local_check'] = false;

	$parts = pathinfo($info['path']);
	$info['folder'] = $parts['dirname'];
	$info['name'] = $parts['basename'];

	if( isset($file[1]) AND $file[1]) {
		$info['thumb'] = $info['root'] . $info['folder'] . "/thumbs/" . $info['name'];
	} else {
		$info['thumb'] = false;
	}
	
	if( isset($file[2]) AND $file[2]) {
		$info['medium'] = $info['root'] . $info['folder'] . "/medium/" . $info['name'];
	} else {
		$info['medium'] = false;
	}
	
	if( isset($file[3]) AND $file[3]) $info['dimension'] = $file[3]; else $info['dimension'] = false;
	if( isset($file[4]) AND $file[4]) $info['size'] = $file[4]; else $info['size'] = false;

	return (object)$info;
}

function is_md5hash( $md5 = '' ) {
  return strlen($md5) == 32 && ctype_xdigit($md5);
}

function generate_pin(){
	
	$pin = "";
	
	for($i = 0; $i < 5; $i ++) {
		$rand = "";
	
		if(function_exists('openssl_random_pseudo_bytes')) {
			 do{
				 $rand = floor(10*(hexdec(bin2hex(openssl_random_pseudo_bytes(4)))/0xffffffff));
			 }while($rand == 10);
		} else {
	
			$rand = mt_rand( 0, 9 );
		}
		
		$pin .= $rand;
	}
	
    return $pin;
}

function isSSL() {
    if( (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443)
        || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
		|| (isset($_SERVER['CF_VISITOR']) && $_SERVER['CF_VISITOR'] == '{"scheme":"https"}')
		|| (isset($_SERVER['HTTP_CF_VISITOR']) && $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}')
    ) return true; else return false;
}

function preg_quote_replacement($str) {
    return str_replace(array('\\', '$'), array('\\\\', '\\$'), $str);
}

?>