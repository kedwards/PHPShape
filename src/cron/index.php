<?php
//
//	file: cron/index.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 29/03/2010
//	version: 0.0.1 - 29/03/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

define('SYS', 'sys');
$root = '../';
$requester = 'cron/index';
$ext = strrchr(__FILE__, '.');
include($root . 'sys/sys.class' . $ext);
include($root . 'sys/sys.api.class' . $ext);
include($root . 'sys/debug.func' . $ext);
include($root . 'api/api.class' . $ext);
include($root . 'inc/totem.api.class' . $ext);

class cron_api extends main_api
{
	function process()
	{
		$sys = &$GLOBALS[SYS];

		if ( $this->crons )
		{
			$this->init();
			foreach ( $this->crons as $cron )
			{
				if ( ($class = $sys->ini_get($cron, 'class')) )
				{
					$handler = new $class();
					$handler->set($this->api_name);
					$handler->process();
					sys::kill($handler);
					unset($handler);
				}
			}
		}
		trigger_error('Done !', E_USER_ERROR);
	}
}

// process
$api = new cron_api();
$api->set('api', $root, $requester);
$api->process();
sys::kill($api);
unset($api);

?>