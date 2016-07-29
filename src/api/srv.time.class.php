<?php
//
//	file: srv.time.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 01/06/2009
//	version: 0.0.1 - 01/06/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class srv_time extends sys_stdclass
{
	function process()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$io = &$sys->io;
		$db = &$api->db;
		$user = &$api->user;

		if ( !$api->ajax || !$api->user->data )
		{
			return false;
		}
		$timeshift = $io->read('ts', 0);
		$fields = array(
			'user_timeshift' => (string) $timeshift,
		);
		$sql = 'UPDATE ' . $db->table('users') . '
					SET ' . $db->fields('update', $fields) . '
					WHERE user_id = ' . intval($user->data['user_id']);
		$db->query($sql, __LINE__, __FILE__);

		$tpl->set_xml(true);
		trigger_error('done', E_USER_ERROR);
	}
}

?>