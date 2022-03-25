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
 File: addcomments.php
-----------------------------------------------------
 Use: AJAX for comments
=====================================================
*/

if(!defined('DATALIFEENGINE')) {
	die( "Hacking attempt!" );
}

$banned_info = get_vars ( "banned" );

if (!is_array ( $banned_info )) {
	$banned_info = array ();
	
	$db->query ( "SELECT * FROM " . USERPREFIX . "_banned" );
	while ( $row = $db->get_row () ) {
		
		if ($row['users_id']) {
			
			$banned_info['users_id'][$row['users_id']] = array (
																'users_id' => $row['users_id'], 
																'descr' => stripslashes ( $row['descr'] ), 
																'date' => $row['date'] );
		
		} else {
			
			if (count ( explode ( ".", $row['ip'] ) ) == 4)
				$banned_info['ip'][$row['ip']] = array (
														'ip' => $row['ip'], 
														'descr' => stripslashes ( $row['descr'] ), 
														'date' => $row['date']
														);
			elseif (strpos ( $row['ip'], "@" ) !== false)
				$banned_info['email'][$row['ip']] = array (
															'email' => $row['ip'], 
															'descr' => stripslashes ( $row['descr'] ), 
															'date' => $row['date'] );
			else $banned_info['name'][$row['ip']] = array (
															'name' => $row['ip'], 
															'descr' => stripslashes ( $row['descr'] ), 
															'date' => $row['date'] );
		
		}
	
	}
	set_vars ( "banned", $banned_info );
	$db->free ();
}

if ( check_ip ( $banned_info['ip'] ) OR ($is_logged AND $member_id['banned'] == "yes") ) {
	echo "{\"error\":true, \"content\":\"banned\"}";
	die();	
}

if ( !$config['allow_registration'] ) {
	$dle_login_hash = sha1( SECURE_AUTH_KEY . $_IP );
}

$tpl = new dle_template( );
$tpl->dir = ROOT_DIR . '/templates/' . $config['skin'];
define( 'TEMPLATE_DIR', $tpl->dir );

$ajax_adds = true;

require_once (DLEPlugins::Check(ENGINE_DIR . '/modules/addcomments.php'));

if( !$CN_HALT ) {

	if ( !defined('BANNERS') ) {
		if ($config['allow_banner']) include_once (DLEPlugins::Check(ENGINE_DIR . '/modules/banners.php'));
	}

	$comments = new DLE_Comments( $db, 1, 1 );
	$comments->intern_count = -1;
	if($parent) $comments->indent = $indent+1;
	
	$comments->query = "SELECT " . PREFIX . "_comments.id, post_id, " . PREFIX . "_comments.user_id, date, autor as gast_name, " . PREFIX . "_comments.email as gast_email, text, ip, is_register, " . PREFIX . "_comments.rating, " . PREFIX . "_comments.vote_num, name, " . USERPREFIX . "_users.email, news_num, comm_num, user_group, lastdate, reg_date, signature, foto, fullname, land, xfields FROM " . PREFIX . "_comments LEFT JOIN " . USERPREFIX . "_users ON " . PREFIX . "_comments.user_id=" . USERPREFIX . "_users.user_id WHERE " . PREFIX . "_comments.id = '{$added_comments_id}'";
	$comments->build_comments('comments.tpl', 'ajax' );

}

if( $_POST['editor_mode'] == "wysiwyg" ) {

	if( $config['allow_comments_wysiwyg'] == "1") $clear_value = "\$('#comments').froalaEditor('html.set', '');";
	else $clear_value = "tinyMCE.activeEditor.setContent('');";

} else {
	
	$clear_value = "form.comments.value = '';";

}

if( $user_group[$member_id['user_group']]['comments_question'] ) {
	$qs = $db->super_query("SELECT id, question FROM " . PREFIX . "_question ORDER BY RAND() LIMIT 1");
	$qs['question'] = htmlspecialchars( stripslashes( $qs['question'] ), ENT_QUOTES, $config['charset'] );
	$_SESSION['question'] = $qs['id'];
}

if( $CN_HALT ) {
	
	$stop = implode( '<br><br>', $stop );

	if($parent) {

		$replyclear="";
		
		if($user_group[$member_id['user_group']]['comments_question']) {
			
				$replyclear .= <<<HTML
	
		jQuery('#dle-question{$parent}').text('{$qs['question']}');
		jQuery('#question_answer{$parent}').val('');

HTML;
	
		}
	
		if( $user_group[$member_id['user_group']]['captcha'] AND $config['allow_recaptcha'] ) {

				$replyclear .= <<<HTML
	if ( dle_captcha_type == "1" ) {
		grecaptcha.reset(recaptcha_widget);
    } else if (dle_captcha_type == "2") {
		var recaptcha_public_key = $('#comments-recaptcha-response{$parent}').data('key');
		grecaptcha.execute(recaptcha_public_key, {action: 'comments'}).then(function(token) {
		$('#comments-recaptcha-response{$parent}').val(token);
		});
	}	
HTML;
			
		}

		if( $user_group[$member_id['user_group']]['captcha'] AND !$config['allow_recaptcha'] ) {

				$replyclear .= <<<HTML
	
		reload{$parent} ();
		
HTML;
			
		}
		
	} else  {

		$replyclear = <<<HTML
	
	if ( dle_captcha_type == "1" ) {
		if ( typeof grecaptcha != "undefined"  ) {
		   grecaptcha.reset();
		}
    } else if (dle_captcha_type == "2") {
		if ( typeof grecaptcha != "undefined"  ) {
			var recaptcha_public_key = $('#g-recaptcha-response').data('key');
			grecaptcha.execute(recaptcha_public_key, {action: 'comments'}).then(function(token) {
			$('#g-recaptcha-response').val(token);
			});
		}
	}

	if ( form.question_answer ) {

	   form.question_answer.value ='';
       jQuery('#dle-question').text('{$qs['question']}');
    }

	if ( document.getElementById('dle-captcha') ) {
		form.sec_code.value = '';
		document.getElementById('dle-captcha').innerHTML = '<img src="' + dle_root + 'engine/modules/antibot/antibot.php?rand=' + timeval + '" width="160" height="80" alt="">';
	}
		
HTML;
		
	} 
	
	$tpl->result['content'] = "<script>\nvar form = document.getElementById('dle-comments-form');\n";
	
	if( ! $where_approve ) {
		$tpl->result['content'] .= "\n{$clear_value}\n";
		
		if($parent) $tpl->result['content'] .= "\n jQuery('#dlereplypopup').remove(); jQuery('#dlefastreplycomments').remove(); \n";
	}
	
	$tpl->result['content'] .= "\n DLEalert('" . $stop . "', '". $lang['add_comm']."');\n var timeval = new Date().getTime();\n

	{$replyclear}\n </script>";

} else {
	
	$js_script = <<<HTML
	var timeval = new Date().getTime();
	
	if( document.getElementById('dle-comments-form') ) {
	
		var form = document.getElementById('dle-comments-form');
		
		if ( form.question_answer ) {
	
		   form.question_answer.value ='';
		   jQuery('#dle-question').text('{$qs['question']}');
	
		}
	
		{$clear_value}
	}
HTML;

	if( strpos ( $tpl->result['content'], "dleplyrplayer" ) !== false ) {
		
		if( strpos ( $tpl->result['content'], ".m3u8" ) !== false ) {
			$load_more = "\$.getCachedScript( dle_root + 'engine/classes/html5player/plyr.js');";
			$js_name = "hls.js"; 
		} else {
			$load_more = "";
			$js_name = "plyr.js"; 
		}
		
		$js_script .= <<<HTML
		
			if (typeof DLEPlayer == "undefined") {
			
                $('<link>').appendTo('head').attr({type: 'text/css', rel: 'stylesheet',href: dle_root + 'engine/classes/html5player/plyr.css'});
				  
				$.getCachedScript( dle_root + 'engine/classes/html5player/{$js_name}').done(function() {
				  {$load_more} 
				});
				
			} else {
			
				var containers = document.querySelectorAll("#comm-id-{$added_comments_id} .dleplyrplayer");Array.from(containers).forEach(function (container) {new DLEPlayer(container);});
				
			}
HTML;

	}

	if( strpos ( $tpl->result['content'], 'class="highslide' ) !== false ) {
		
		switch ( $config['outlinetype'] ) {
	
			case 1 :
				$hs_type = "hs.wrapperClassName = 'wide-border';";
				break;
	
			case 2 :
				$hs_type = "hs.wrapperClassName = 'borderless';";
				break;
	
			case 3 :
				$hs_type = "hs.wrapperClassName = 'less';\nhs.outlineType = null;";
				break;
	
			default :
				$hs_type = "hs.wrapperClassName = 'rounded-white';\nhs.outlineType = 'rounded-white';";
				break;
	
	
		}
	
		if ($config['thumb_dimming']) $hs_dimming = "hs.dimmingOpacity = 0.60;"; else $hs_dimming = "";
		
		$js_script .= <<<HTML
		
			if (typeof hs == "undefined" ) {
				$.getCachedScript( dle_root + 'engine/classes/highslide/highslide.js').done(function() {
					hs.graphicsDir = dle_root + 'engine/classes/highslide/graphics/';
					{$hs_type}
					{$hs_dimming}
					hs.numberOfImagesToPreload = 0;
					hs.captionEval = 'this.thumb.alt';
					hs.showCredits = false;
					hs.align = 'center';
					hs.transitions = ['expand', 'crossfade'];
					hs.lang = { loadingText : '{$lang['loading']}', playTitle : '{$lang['thumb_playtitle']}', pauseTitle:'{$lang['thumb_pausetitle']}', previousTitle : '{$lang['thumb_previoustitle']}', nextTitle :'{$lang['thumb_nexttitle']}',moveTitle :'{$lang['thumb_movetitle']}', closeTitle :'{$lang['thumb_closetitle']}',fullExpandTitle:'{$lang['thumb_expandtitle']}',restoreTitle:'{$lang['thumb_restore']}',focusTitle:'{$lang['thumb_focustitle']}',loadingTitle:'{$lang['thumb_cancel']}' };
				});
			}
		
HTML;

		$tpl->result['content'] = str_replace ( 'class="highslide', 'onclick="return hs.expand(this); return false;" class="highslide', $tpl->result['content'] );
		
	}

	$needwrap = isset($_POST['needwrap']) ? intval($_POST['needwrap']) : 0 ;
	
	if ( $config['tree_comments'] ) {
		
		if (!$parent) $class = ' blind-animation';
		else $class = '';
		
		if ($config['tree_comments_level'] AND $indent >= $config['tree_comments_level'] ) {
			
			$tpl->result['content'] = "<div id=\"comments-tree-item-{$added_comments_id}\" style=\"display:none\" class=\"comments-tree-item{$class}\" >".$tpl->result['content']."</div>";
			
		} else {
			
			$tpl->result['content'] = "<li id=\"comments-tree-item-{$added_comments_id}\" style=\"display:none\" class=\"comments-tree-item{$class}\" >".$tpl->result['content']."</li>";
			
			if($needwrap OR !$parent) {
				$tpl->result['content'] = "<ol class=\"comments-tree-list\">".$tpl->result['content']."</ol>";
			}
			
		}

	} else {

		$tpl->result['content'] = "<div id=\"blind-animation-{$added_comments_id}\" style=\"display:none\">".$tpl->result['content']."<div>";
	}
	
}

$tpl->result['content'] = str_replace( '{THEME}', $config['http_home_url'] . 'templates/' . $config['skin'], $tpl->result['content'] );

if ($CN_HALT) {

	echo json_encode(array("error" => true, "content" => $tpl->result['content'] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

} else {
	
	echo json_encode(array("success" => true, "id" => $added_comments_id, "content" => $tpl->result['content'], "scripts" => $js_script), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	
}

?>