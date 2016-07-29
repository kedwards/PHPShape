<?php
//
//	file: inc/myapi.api.ini.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 01/06/2010
//	version: 0.0.1 - 01/06/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

// processes
$ini = array(

	// disable debug
//	'rstats' => false,

	// session constants
	'session.cookie_name' => 'myapi', // cookie name
	'session.cookie_domain' => '', // do not try to guess "domain" to generate the cookies: do not use a domain at all for setcookie()

	// modes
	'default.mode' => 'groups',
	'modes' => array(
	),

	// hooks
	'hooks' => array(
	),
);

?>