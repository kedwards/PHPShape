<?php
//
//	file: api/hooks.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 29/03/2010
//	version: 0.0.1 - 29/03/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

class hooks extends sys_stdclass
{
	var $def;
	var $data;

	function __construct()
	{
		parent::__construct();
		$this->def = array();
		$this->data = array();
	}

	function __destruct()
	{
		unset($this->data);
		unset($this->def);
		parent::__destruct();
	}

	function register($def)
	{
		if ( !$def )
		{
			return false;
		}
		if ( !$this->def )
		{
			$this->def = $def;
		}
		else
		{
			foreach ( $def as $action => $items )
			{
				$this->def[$action] = isset($this->def[$action]) ? array_keys(array_flip(array_merge($this->def[$action], $items))) : $items;
			}
		}
		return true;
	}

	function process($action)
	{
		$sys = &$GLOBALS[SYS];

		if ( $this->def && isset($this->def[$action]) )
		{
			foreach ( $this->def[$action] as $item )
			{
				if ( ($class = $sys->ini_get($item, 'class')) && method_exists($class, 'hook') )
				{
					call_user_func(array($class, 'hook'), $this->api_name, $action);
				}
			}
		}
	}

	function set_data($action, $data)
	{
		$this->data[$action] = $data;
	}

	function get_data($action)
	{
		return isset($this->data[$action]) ? $this->data[$action] : false;
	}

	function unset_data($action)
	{
		if ( isset($this->data[$action]) )
		{
			unset($this->data[$action]);
		}
	}
}

?>