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
 File: favorites.php
=====================================================
*/

if(!defined('DATALIFEENGINE')) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

$id = intval( $_REQUEST['fav_id'] );

if( !$id OR $id < 1) {
	
	echo json_encode(array("error" => true, "content" => 'action not correct' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
	
}

if( !$is_logged ){
	
	echo json_encode(array("error" => true, "content" => $lang['fav_error'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
	
}

if( $_REQUEST['user_hash'] == "" OR $_REQUEST['user_hash'] != $dle_login_hash ) {

	echo json_encode(array("error" => true, "content" => $lang['sess_error'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
	
}
$row = $db->super_query( "SELECT id, approve FROM " . PREFIX . "_post WHERE id ='{$id}'" );

if( !$row['id'] ) {
	echo json_encode(array("error" => true, "content" => $lang['news_page_err'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
}

if( !$row['approve'] AND $_REQUEST['action'] == "plus") {
	echo json_encode(array("error" => true, "content" => $lang['fav_plus_err_2'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
}

if( $_REQUEST['action'] == "plus" ) {

	$list = explode( ",", $member_id['favorites'] );
	
	foreach ( $list as $daten ) {

		if( $daten == $id ) {
			echo json_encode(array("error" => true, "content" => $lang['fav_plus_err_1'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			die ();
		}

	}
	
	$list[] = $id;
	$favorites = $db->safesql(implode( ",", $list ));
	
	$member_id['favorites'] = $favorites;
	
	$db->query( "UPDATE " . USERPREFIX . "_users SET favorites='{$favorites}' WHERE user_id = '{$member_id['user_id']}'" );
	

	if ( $_REQUEST['alert'] ) $buffer = $lang['fav_plus'];
	else $buffer = "<img src=\"" . $config['http_home_url'] . "templates/{$config['skin']}/dleimages/minus_fav.gif\" onclick=\"doFavorites('" . $id . "', 'minus'); return false;\" title=\"" . $lang['news_minfav'] . "\" style=\"vertical-align: middle;border: none;\" />";

} elseif( $_REQUEST['action'] == "minus" ) {
	
	$list = explode( ",", $member_id['favorites'] );
	$i = 0;
	
	foreach ( $list as $daten ) {

		if( $daten == $id ) unset( $list[$i] );
		$i ++;

	}
	
	if( count( $list ) ) $member_id['favorites'] = $db->safesql(implode( ",", $list ));
	else $member_id['favorites'] = "";
	
	$db->query( "UPDATE " . USERPREFIX . "_users SET favorites='{$member_id['favorites']}' WHERE user_id = '{$member_id['user_id']}'" );

	if ( $_REQUEST['alert'] ) $buffer = $lang['fav_minus'];
	else $buffer = "<img src=\"" . $config['http_home_url'] . "templates/{$config['skin']}/dleimages/plus_fav.gif\" onclick=\"doFavorites('" . $id . "', 'plus'); return false;\" title=\"" . $lang['news_addfav'] . "\" style=\"vertical-align: middle;border: none;\" />";

} else {
	
	echo json_encode(array("error" => true, "content" => 'action not correct' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	die ();
	
}

echo json_encode(array("success" => true, "content" => $buffer ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
?>