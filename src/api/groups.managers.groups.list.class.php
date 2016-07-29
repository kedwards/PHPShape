<?php
//
//	file: inc/groups.managers.groups.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/04/2009
//	version: 0.0.1 - 02/04/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_managers_groups_list extends groups_list
{
	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		$this->api_name = $api_name;

		$class = $sys->ini_get('groups', 'class');
		$this->tree = new $class();
		$this->tree->set($this->api_name);
		$this->set_switch('grp.row');
		$this->tree->disable_owners();
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
		return 'groups.managers';
	}

	function display_tree($item_id=false)
	{
		if ( parent::display_tree($item_id) )
		{
			return 'groups.managers.groups.tree';
		}
		return false;
	}

	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$db = &$api->db;

		if ( !($is_manager = $item_id && $this->tree->allowed($item_id, 'manage')) )
		{
			return false;
		}
		$tpl->add(array(
			'CONTENT_TYPE' => 'group',
			'CONTENT_ID' => $item_id,
			'CAN_CREATE' => $is_manager,
		));

		// display breadscrumb
		$this->display_breadscrumb($item_id);
		if ( !$item_id || !isset($this->tree->data[$item_id]) )
		{
			return false;
		}

		// we need all the users that belongs to a group which has the explicit management auth on the targeted group
		$sql = 'SELECT *
					FROM ' . $db->table('users') . '
					WHERE user_id IN(' . $db->sub_query('
						SELECT DISTINCT ug.user_id
							FROM ' . $db->table('users_groups') . ' ug, ' . $db->table('groups_auths') . ' ga
							WHERE ga.auth_type = ' . $db->escape((string) $this->tree->type) . '
								AND ga.auth_name = ' . $db->escape('manage') . '
								AND ga.obj_id = ' . intval($item_id) . '
								AND ug.group_id = ga.group_id
						', __LINE__, __FILE__) . ')
					ORDER BY user_realname';
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$tpl->add('content', array(
				'ID' => (int) $row['user_id'],
				'REALNAME' => sys_string::htmlspecialchars($row['user_realname']),
				'EMAIL' => $row['user_email'],
				'PHONE' => sys_string::htmlspecialchars($row['user_phone']),
				'LOCATION' => sys_string::htmlspecialchars($row['user_location']),
				'REGDATE' => (int) $row['user_regdate'],
			));
		}
		$db->free($result);
		return 'groups.managers.groups.content';
	}

	function display_breadscrumb($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$this->tree->read_ancestors($item_id);
		if ( $this->tree->data )
		{
			foreach ( $this->tree->data as $id => $data )
			{
				if ( $data[$this->tree->field_lid] > $this->tree->data[$item_id][$this->tree->field_lid] )
				{
					break;
				}
				$tpl->add('breadscrumb', $this->tree->get_option($id));
			}
		}
	}
}

?>