<?php
//
//	file: inc/groups.membership.groups.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.3 - 26/03/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_membership_groups_list extends groups_list
{
	function set($api_name)
	{
		parent::set($api_name);
		$this->set_switch('grp.row');
	}

	function process($action, $item_id)
	{
		$this->item_id = $item_id;
		if ( ($handler = $this->process_action($action, $this->item_id)) )
		{
			return $handler;
		}
		return false;
	}

	function display()
	{
		$this->display_tree();
		$this->display_content($this->item_id);
		return 'groups.membership';
	}

	function display_tree($item_id=false)
	{
		if ( parent::display_tree($item_id) )
		{
			return 'groups.membership.groups.tree';
		}
		return false;
	}

	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$io = &$sys->io;
		$db = &$api->db;
		$user = &$api->user;

		$is_manager = $item_id && $this->tree->allowed($item_id, 'manage');
		$this->tree->read_content($item_id);
		$tpl->add(array(
			'CONTENT_TYPE' => 'group',
			'CONTENT_ID' => $item_id,
			'CAN_CREATE' => $is_manager,
		));

		// get admins
		$admin_ids = array();
		$sql = 'SELECT user_id
					FROM ' . $db->table('users_groups') . '
					WHERE group_id = ' . intval(SYS_GROUP_OWNERS);
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$admin_ids[ (int) $row['user_id'] ] = true;
		}
		$db->free($result);

		// display breadscrumb
		$this->tree->read_ancestors($item_id);
		foreach ( $this->tree->data as $id => $data )
		{
			if ( $data[$this->tree->field_lid] > $this->tree->data[$item_id][$this->tree->field_lid] )
			{
				break;
			}
			$tpl->add('breadscrumb', $this->tree->get_option($id));
		}

		if ( !$item_id || !isset($this->tree->data[$item_id]) )
		{
			return false;
		}

		// get all the impacted groups sub query
		if ( $this->tree->data[$item_id][$this->tree->field_lid] + 1 < $this->tree->data[$item_id][$this->tree->field_rid] )
		{
			$sub_query = 'SELECT DISTINCT ug.user_id
							FROM ' . $db->table('users_groups') . ' ug, ' . $db->table($this->tree->table) . ' g
							WHERE ug.group_id = g.group_id
								AND g.' . $this->tree->field_lid . ' BETWEEN ' . intval($this->tree->data[$item_id][$this->tree->field_lid]) . ' AND ' . intval($this->tree->data[$item_id][$this->tree->field_rid]);
		}
		else
		{
			$sub_query = 'SELECT DISTINCT user_id
							FROM ' . $db->table('users_groups') . '
							WHERE group_id = ' . intval($item_id);
		}

		// pagination
		$start = $io->read('cstart', 0);
		$ppage = max(5, min(50, $sys->ini_get('users.content.ppage')));

		// filter
		$filter = $io->read('ucsearch', '');
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

		// all users list
		$tpl->add(array(
			'CONTENT_FILTER' => $filter ? sys_string::htmlspecialchars($filter) : '',
		));
		if ( $api->ajax && ($api->mode_action === 'group.filter') )
		{
			$tpl->add('pagination_content');
		}

		// get total
		$sql = 'SELECT COUNT(*) AS count_rows
					FROM ' . $db->table('users') . ' u
						LEFT JOIN ' . $db->table('users_groups') . ' ug
							ON ug.user_id = u.user_id
								AND ug.group_id = ' . intval($item_id) . '
					WHERE u.user_id IN(' . $db->sub_query($sub_query, __LINE__, __FILE__) . ')' . ($filters ? '
						AND ' . implode('
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

		// now get users
		if ( $total )
		{
			$sql = 'SELECT u.*, ug.group_id
						FROM ' . $db->table('users') . ' u
							LEFT JOIN ' . $db->table('users_groups') . ' ug
								ON ug.user_id = u.user_id
									AND ug.group_id = ' . intval($item_id) . '
						WHERE u.user_id IN(' . $db->sub_query($sub_query, __LINE__, __FILE__) . ')' . ($filters ? '
							AND ' . implode('
							AND ', $filters) : '') . '
						ORDER BY u.user_realname
						LIMIT ' . implode(', ', $limits);
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				$belongs = isset($row['group_id']) && $row['group_id'];
				$user_is_admin = isset($admin_ids[ (int) $row['user_id'] ]);
				$itself = $user->data && intval($row['user_id']) && ($user->data['user_id'] == $row['user_id']);
				$can_edit = $itself || ($is_manager && ($user->is_admin || !$user_is_admin));
				$can_delete = $can_edit && !$user_is_admin && !$itself;
				$tpl->add('content', array(
					'ID' => (int) $row['user_id'],
					'REALNAME' => sys_string::htmlspecialchars($row['user_realname']),
					'EMAIL' => $row['user_email'],
					'PHONE' => sys_string::htmlspecialchars($row['user_phone']),
					'LOCATION' => sys_string::htmlspecialchars($row['user_location']),
					'IS_OWN' => $belongs,
					'REGDATE' => (int) $row['user_regdate'],
					'CAN_EDIT' => $can_edit,
					'CAN_DELETE' => $can_delete,
				));
			}
			$db->free($result);
		}
		unset($admin_ids);

		// pagination
		$class = $sys->ini_get('pagination', 'class');
		$pagination = new $class();
		$pagination->set($this->api_name);
		$pagination->display($total, $start, 'cpagination', $ppage);
		sys::kill($pagination);
		unset($pagination);

		return 'groups.membership.groups.content';
	}
}

?>