<?php

if ( !defined('SYS') )
{
	die('Not allowed');
}

$base = $GLOBALS[SYS]->root . 'styles/api.gifs/';
// $i18n = ($i18n = $base . $lang . '/') && sys::dir_exists($i18n) ? $i18n : $base . 'en/';

// IE < 7

// groups tree
$images['cmd_group_open'] = $base . 'group_closed.gif';
$images['cmd_group_close'] = $base . 'group_opened.gif';
$images['cmd_group_empty'] = $base . 'group_empty.gif';

$images['cmd_group_open_over'] = $base . 'group_closed_over.gif';
$images['cmd_group_close_over'] = $base . 'group_opened_over.gif';
$images['cmd_group_empty_over'] = $base . 'group_empty_over.gif';

$images['cmd_group_breadscrumb'] = $base . 'group_root.gif';
$images['cmd_group_content'] = $base . 'group_large.gif';
$images['cmd_group_content_over'] = $base . 'group_large_over.gif';

$images['cmd_group_light'] = $base . 'group_light.gif';
$images['cmd_group_light_open'] = $base . 'group_light_closed.gif';
$images['cmd_group_light_close'] = $base . 'group_light_opened.gif';
$images['cmd_group_disabled'] = $base . 'group_disabled.gif';

$images['cmd_group_create'] = $base . 'group_create.gif';
$images['cmd_group_edit'] = $base . 'tiny_edit.gif';
$images['cmd_group_delete'] = $base . 'tiny_delete.gif';

// users
$images['cmd_user_root'] = $base . 'user_root.gif';
$images['cmd_user_content'] = $base . 'user_large.gif';
$images['cmd_user_content_light'] = $base . 'user_large_light.gif';
$images['cmd_user_small'] = $base . 'user.gif';
$images['cmd_user_small_light'] = $base . 'user_light.gif';

$images['cmd_user_create'] = $base . 'user_create.gif';
$images['cmd_user_edit'] = $base . 'tiny_edit.gif';
$images['cmd_user_delete'] = $base . 'tiny_delete.gif';

// unmodified from open crystal pack (everaldo)
$images['cmd_actor_logout'] = $base . 'exit.gif';

// pagination
$images['cmd_page_first'] = $base . 'arrow_first.gif';
$images['cmd_page_previous'] = $base . 'arrow_previous.gif';
$images['cmd_page_next'] = $base . 'arrow_next.gif';
$images['cmd_page_last'] = $base . 'arrow_last.gif';
$images['cmd_search_open'] = $base . 'search_open.gif';
$images['cmd_search_close'] = $base . 'search_close.gif';

?>