<?php

if ( !defined('SYS') )
{
	die('Not allowed');
}

// static method unsed for ie rounded corners
if ( !class_exists('rounded_tplfunc') )
{
	class rounded_tplfunc
	{
		// statics
		function rounded_begin($mode=false)
		{
			if ( $mode === false )
			{
				$mode = 'full';
			}
			switch ( $mode )
			{
				case 'full':
					?><!--[if lte IE 8]><fieldset class="rounded-d"><![endif]--><?php
				break;
				case 'top':
					?><!--[if lte IE 8]><div class="rounded-t"><div class="rounded-r"><div class="rounded-l"><div class="rounded-tl"><div class="rounded-tr"><![endif]--><?php
				break;
				case 'bottom':
					?><!--[if lte IE 8]><div class="rounded-b"><div class="rounded-r"><div class="rounded-l"><div class="rounded-bl"><div class="rounded-br"><![endif]--><?php
				break;
				default:
					?><!--[if lte IE 8]><div class="rounded-t"><div class="rounded-r"><div class="rounded-b"><div class="rounded-l"><div class="rounded-tl"><div class="rounded-tr"><div class="rounded-br"><div class="rounded-bl"><![endif]--><?php
				break;
			}
		}
		function rounded_end($mode=false)
		{
			if ( $mode === false )
			{
				$mode = 'full';
			}
			switch ( $mode )
			{
				case 'full':
					?><!--[if lte IE 8]></fieldset><![endif]--><?php
				break;
				case 'top':
				case 'bottom':
					?><!--[if lte IE 8]></div></div></div></div></div><![endif]--><?php
				break;
				default:
					?><!--[if lte IE 8]></div></div></div></div></div></div></div></div><![endif]--><?php
				break;
			}
		}
	}
}
$sys->tpl->register_function('rounded_begin', array('rounded_tplfunc', 'rounded_begin'));
$sys->tpl->register_function('rounded_end', array('rounded_tplfunc', 'rounded_end'));

// standard javascripts
$sys->ini_set(array(
	'func.js' => array('file' => 'ROOT/styles/api.js/func.js'),

	'admin.menu.js' => array('file' => 'ROOT/styles/api.js/admin.menu.js'),
	'mousehandler.js' => array('file' => 'ROOT/styles/api.js/mousehandler.js'),
	'tree.openclose.js' => array('file' => 'ROOT/styles/api.js/tree.openclose.js'),
	'tree.list.js' => array('file' => 'ROOT/styles/api.js/tree.list.js'),
	'groups.managers.js' => array('file' => 'ROOT/styles/api.js/groups.managers.js'),
	'groups.membership.js' => array('file' => 'ROOT/styles/api.js/groups.membership.js'),
	'groups.managers.js' => array('file' => 'ROOT/styles/api.js/groups.managers.js'),
	'users.tree.js' => array('file' => 'ROOT/styles/api.js/users.tree.js'),
));

// basic css (does not include tab'ed sub-sub menus)
$sys->tpl->add('css', array('VALUE' => $sys->root . 'styles/api.css/api.css'));
$sys->tpl->add('css_ie', array('VALUE' => $sys->root . 'styles/api.css/api.ie.css'));
$sys->tpl->add('css_ie6', array('VALUE' => $sys->root . 'styles/api.css/api.ie6.css'));
$sys->tpl->add('css_other', array('VALUE' => $sys->root . 'styles/api.css/api.other.css'));

// rounded styles
$sys->tpl->add('css_ie8', array('VALUE' => $sys->root . 'styles/api.css/api.rounded.ie8.css'));
$sys->tpl->add('css_other', array('VALUE' => $sys->root . 'styles/api.css/api.rounded.other.css'));

// tpls & images
$sys->tpl->register_tpl($sys->root . 'styles/api.tpls/', '.tpl', $sys->root . 'cache/', 'api');
$sys->tpl->register_img($sys->root . 'styles/api.imgs' . $sys->ext);

?>