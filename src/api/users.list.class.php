<?php
//
//	file: inc/users.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 19/05/2009
//	version: 0.0.1 - 19/05/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class users_list extends sys_stdclass
{
	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);
		$sys->lang->register('api.users');
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$io = &$sys->io;
		$db = $api->db;

		// pagination
		$start = $io->read('rstart', 0);
		$ppage = max(5, min(50, $sys->ini_get('users.tree.ppage')));

		// filter
		$filter = $io->read('usearch', '');
		$filters = $filter ? preg_split('#[\s,]+#', $filter) : false;
		if ( $filters )
		{
			$t_filters = array();
			foreach ( $filters as $idx => $item )
			{
				if ( sys_string::strlen($item) == 1 )
				{
					$t_filters[] = '(user_realname LIKE ' . str_replace('*', '%', $db->escape($item . '*')) . ' OR user_realname LIKE ' . str_replace('*', '%', $db->escape('* ' .$item . '*')) . ')';
				}
				else
				{
					$t_filters[] = 'user_realname LIKE ' . str_replace('*', '%', $db->escape('*' . $item . '*'));
				}
			}
			$filters = $t_filters;
			unset($t_filters);
		}

		// get owners list
		$owner_ids = array();
		$sql = 'SELECT user_id
					FROM ' . $db->table('users_groups') . '
					WHERE group_id = ' . intval(SYS_GROUP_OWNERS);
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$owner_ids[ (int) $row['user_id'] ] = true;
		}
		$db->free($result);

		// total users
		$sql = 'SELECT COUNT(*) AS count_rows
					FROM ' . $db->table('users') . ($filters ? '
					WHERE ' . implode('
						AND ', $filters) : '');
		$result = $db->query($sql, __LINE__, __FILE__);
		$total = ($row = $db->fetch($result)) ? intval($row['count_rows']) : 0;
		$db->free($result);

		// fix start
		$start = floor($start / $ppage) * $ppage;
		$last = floor(($total - 1) / $ppage) * $ppage;
		if ( $start > $last )
		{
			$start = $last < 0 ? 0 : $last;
		}
		$limits = $start ? array($start, $ppage) : array($ppage);

		// all users list
		$tpl->add('user', array(
			'FILTER' => $filter ? sys_string::htmlspecialchars($filter) : '',
		));
		$sql = 'SELECT u.*, ug.group_id AS group_id_guest
					FROM ' . $db->table('users') . ' u
						LEFT JOIN ' . $db->table('users_groups') . ' ug
							ON ug.user_id = u.user_id
								AND ug.group_id = ' . intval(SYS_GROUP_GUESTS) . ($filters ? '
					WHERE ' . implode('
						AND ', $filters) : '') . '
					ORDER BY u.user_realname
					LIMIT ' . implode(', ', array_map('intval', $limits));
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$is_guest = isset($row['group_id_guest']) && $row['group_id_guest'];
			$tpl->add('user.row', array(
				'ID' => (int) $row['user_id'],
				'REALNAME' => sys_string::htmlspecialchars($row['user_realname']),
				'EMAIL' => $row['user_email'],
				'PHONE' => sys_string::htmlspecialchars($row['user_phone']),
				'LOCATION' => sys_string::htmlspecialchars($row['user_location']),
				'REGDATE' => (int) $row['user_regdate'],
				'IS_GUEST' => $is_guest,
				'IS_ADMIN' => !$is_guest && isset($owner_ids[ (int) $row['user_id'] ]),
			));
		}
		$db->free($result);

		// pagination
		$class = $sys->ini_get('pagination', 'class');
		$pagination = new $class();
		$pagination->set($this->api_name);
		$pagination->display($total, $start, 'user.pagination', $ppage);
		sys::kill($pagination);
		unset($pagination);

		return 'users.tree';
	}
}

?>