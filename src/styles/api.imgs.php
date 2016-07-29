<?php

if ( !defined('SYS') )
{
	die('Not allowed');
}

$base = $GLOBALS[SYS]->root . 'styles/api.imgs/';
// $i18n = ($i18n = $base . $lang . '/') && sys::dir_exists($i18n) ? $i18n : $base . 'en/';

// All but IE < 7

// groups tree
$images['cmd_group_open'] = $base . 'group_closed.png';
$images['cmd_group_close'] = $base . 'group_opened.png';
$images['cmd_group_empty'] = $base . 'group_empty.png';

$images['cmd_group_open_over'] = $base . 'group_closed_over.png';
$images['cmd_group_close_over'] = $base . 'group_opened_over.png';
$images['cmd_group_empty_over'] = $base . 'group_empty_over.png';

$images['cmd_group_breadscrumb'] = $base . 'group_root.png';
$images['cmd_group_content'] = $base . 'group_large.png';
$images['cmd_group_content_over'] = $base . 'group_large_over.png';

$images['cmd_group_light'] = $base . 'group_light.png';
$images['cmd_group_light_open'] = $base . 'group_light_closed.png';
$images['cmd_group_light_close'] = $base . 'group_light_opened.png';
$images['cmd_group_disabled'] = $base . 'group_disabled.png';

$images['cmd_group_create'] = $base . 'group_create.png';
$images['cmd_group_edit'] = $base . 'tiny_edit.png';
$images['cmd_group_delete'] = $base . 'tiny_delete.png';

// users
$images['cmd_user_root'] = $base . 'user_root.png';
$images['cmd_user_content'] = $base . 'user_large.png';
$images['cmd_user_content_light'] = $base . 'user_large_light.png';
$images['cmd_user_small'] = $base . 'user.png';
$images['cmd_user_small_light'] = $base . 'user_light.png';

$images['cmd_user_create'] = $base . 'user_create.png';
$images['cmd_user_edit'] = $base . 'tiny_edit.png';
$images['cmd_user_delete'] = $base . 'tiny_delete.png';

// unmodified from open crystal pack (everaldo)
$images['cmd_actor_logout'] = $base . 'exit.png';

// pagination
$images['cmd_page_first'] = $base . 'arrow_first.png';
$images['cmd_page_previous'] = $base . 'arrow_previous.png';
$images['cmd_page_next'] = $base . 'arrow_next.png';
$images['cmd_page_last'] = $base . 'arrow_last.png';
$images['cmd_search_open'] = $base . 'search_open.png';
$images['cmd_search_close'] = $base . 'search_close.png';

// IE < 7
if ( $gif_only )
{
	include($GLOBALS[SYS]->root . 'styles/api.imgs.ie6' . $GLOBALS[SYS]->ext);
}

?>