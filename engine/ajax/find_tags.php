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
 File: find_tags.php
=====================================================
*/

if(!defined('DATALIFEENGINE')) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if( $_REQUEST['user_hash'] == "" OR $_REQUEST['user_hash'] != $dle_login_hash ) {
	die( "error" );
}

if( !isset($_GET['term']) ) die("[]");

if( !$_GET['term'] ) die("[]");

$buffer = "[]";
$tags = array ();

if($_GET['mode'] == "xfield" ) {
	
	$term = dle_strtolower(htmlspecialchars ( strip_tags ( stripslashes ( trim ( rawurldecode($_GET['term']) ) ) ), ENT_QUOTES, $config['charset'] ), $config['charset'] );
	$term = $db->safesql(str_replace( array("{", "[", ":", "&amp;frasl;"), array("&#123;", "&#91;", "&#58;", "/"), $term ));
		
	$db->query("SELECT tagvalue as tag, COUNT(*) AS count FROM " . PREFIX . "_xfsearch WHERE LOWER(`tagvalue`) like '{$term}%' GROUP BY tagvalue ORDER by count DESC LIMIT 15");

} else {
	
	if( preg_match( "/[\||\<|\>]/", $_GET['term'] ) ) $term = "";
	else $term = $db->safesql(  dle_strtolower( htmlspecialchars( strip_tags( stripslashes( trim( rawurldecode($_GET['term']) ) ) ), ENT_COMPAT, $config['charset'] ), $config['charset'] ) );

	if( !$term ) die("[]");
	
	$db->query("SELECT tag, COUNT(*) AS count FROM " . PREFIX . "_tags WHERE LOWER(`tag`) like '{$term}%' GROUP BY tag ORDER by count DESC LIMIT 15");
	
}

while($row = $db->get_row()){
	
	$row['tag'] = html_entity_decode($row['tag'], ENT_QUOTES | ENT_XML1, 'UTF-8');
	$row['tag'] = str_replace('"', '\"', $row['tag']);

	
	$tags[] = $row['tag'];

}

if (count($tags)) $buffer = "[\"".implode("\",\"",$tags)."\"]";

echo $buffer;

?>