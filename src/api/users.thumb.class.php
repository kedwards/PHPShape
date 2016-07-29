<?php
//
//	file: inc/user.thumb.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 01/03/2009
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class user_thumb extends sys_stdclass
{
	var $switch;

	function __construct()
	{
		parent::__construct();
		$this->switch = false;
	}

	function __destruct()
	{
		unset($this->switch);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
	}

	function set_switch($switch)
	{
		$this->switch = $switch;
	}

	function display($user)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;

		if ( !$user || !$user->data )
		{
			return false;
		}
		$tpl_data = array(
			'ID' => (int) $user->data['user_id'],
			'NAME' => $user->data['user_realname'],
			'EMAIL' => $user->data['user_email'],
			'PHONE' => sys_string::htmlspecialchars($user->data['user_phone']),
			'LOCATION' => sys_string::htmlspecialchars($user->data['user_location']),
			'REGDATE' => $user->data['user_regdate'],
			'LAST_CONNECT' => $user->last_connection(),
		);
		if ( $this->switch === '' )
		{
			$tpl->add($tpl_data);
		}
		else
		{
			$tpl->add(!$this->switch ? 'user' : $this->switch, $tpl_data);
		}
		return true;
	}
}

?>