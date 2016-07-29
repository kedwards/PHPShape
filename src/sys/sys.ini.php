<?php
//
//	file: sys/sys.ini.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 31/01/2009
//	version: 0.0.1 - 31/01/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

$ini = array(
	// shutdown function when php < 5.0.0: sys::destroy()
	'shutdown_function' => array('callback' => array('sys', 'destroy')),

	// main handlers (SYS object)
	'rstats' => array('class' => 'sys_rstats', 'file' => 'SYS/rstats.class'),

	// lang management
	'lang' => array('class' => 'sys_lang', 'file' => 'SYS/lang.class', 'dir' => 'ROOT/languages'),

	// errors handler
	'error' => array('class' => 'sys_error', 'file' => 'SYS/error.mixed', 'tpl' => 'page.errors'),

	// string & utf-8
	'utf8' => array('class' => 'sys_utf8', 'file' => 'SYS/utf8/utf8.class'),
	'string' => array('class' => 'sys_string', 'file' => 'SYS/sys.string.class'),

	// input handler
	'io' => array('class' => 'sys_io', 'file' => 'SYS/io.class'),

	// template parser
	'tpl' => array('class' => 'sys_tpl', 'file' => 'SYS/tpl/tpl.class'),
	'tpl.compiler' => array('class' => 'sys_tpl_compiler', 'file' => 'SYS/tpl/tpl.compiler.class'),
	'tpl.img' => array('class' => 'sys_tpl_img', 'file' => 'SYS/tpl/tpl.img.class'),

	// various tpl files
	'tpl.file.errors' => 'page.errors',
	'tpl.file.errors.xml' => 'page.errors.xml',
	'tpl.file.feedback' => 'page.feedback',
);

?>