<?php
//
//	file: index.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 01/06/2010
//	version: 0.0.1 - 30/06/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

define('SYS', 'sys');
$root = './';
$requester = 'index';
$ext = strrchr(__FILE__, '.');
include($root . 'sys/sys.class' . $ext);
include($root . 'sys/sys.api.class' . $ext);
include($root . 'sys/debug.func' . $ext);
include($root . 'api/api.class' . $ext);

// extends the api class: purpose: set a new style, add a new lang file, load the "myapi" ini settings
class main_api extends api
{
	function set($api_name, $root, $requester)
	{
		parent::set($api_name, $root, $requester);

		$sys = &$GLOBALS[SYS];

		$sys->tpl->register_ini($sys->root . 'styles/default.ini' . $sys->ext);
		$sys->lang->register('myapi.api');
	}

	function ini_set()
	{
		parent::ini_set('ROOT/inc/myapi.api.ini');
	}
}

// process
$api = new main_api();
$api->set('api', $root, $requester);
$api->process();
sys::kill($api);
unset($api);

?>