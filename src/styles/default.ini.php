<?php

if ( !defined('SYS') )
{
	die('Not allowed');
}

// use api sub-sub menu css (other api css are already loaded, including rounded styles)
$sys->tpl->add('css', array('VALUE' => $sys->root . 'styles/api.css/api.subsubmenu.css'));
$sys->tpl->add('css_ie8', array('VALUE' => $sys->root . 'styles/api.css/api.subsubmenu.ie8.css'));

// default style css
$sys->tpl->add('css', array('VALUE' => $sys->root . 'styles/default.css/default.header.css'));
$sys->tpl->add('css_ie8', array('VALUE' => $sys->root . 'styles/default.css/default.header.ie8.css'));

// tpls
$sys->tpl->register_tpl($sys->root . 'styles/default.tpls/', '.tpl', $sys->root . 'cache/', 'default');

?>