<?php
//
//	file: inc/groups.managers.users.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/04/2009
//	version: 0.0.1 - 02/04/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_managers_users extends users_list
{
	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$db = &$api->db;

		$class = $sys->ini_get('user', 'class');
		$selected_user = new $class();
		$selected_user->set($this->api_name);
		$selected_user->read($item_id);
		if ( !$selected_user->data )
		{
			return false;
		}
		$selected_user->read_groups();

		// breadscrumb
		$tpl->add(array(
			'CONTENT_TYPE' => 'user',
			'CONTENT_ID' => $item_id,
			'CONTENT_NAME' => $selected_user->data['user_realname'],
		));

		// groups
		$class = $sys->ini_get('groups.list', 'class');
		$list = new $class();
		$list->set($this->api_name);

		// get items this user manages
		$list->tree->data = $list->tree->_root();
		if ( $selected_user->individual_group_id )
		{
			$ids_managed = array();
			$sql = 'SELECT obj_id
						FROM ' . $db->table('groups_auths') . '
						WHERE auth_type = ' . $db->escape($list->tree->type) . '
							AND auth_name = ' . $db->escape('manage') . '
							AND group_id = ' . intval($selected_user->individual_group_id);
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				// actor can check this entry
				if ( $list->tree->allowed((int) $row['obj_id'], 'view', 'manage') )
				{
					$ids_managed[ (int) $row['obj_id'] ] = true;
				}
			}
			$db->free($result);

			if ( $ids_managed )
			{
				$sql = 'SELECT *
							FROM ' . $db->table($list->tree->table) . '
							WHERE ' . $list->tree->field_id . ' IN(' . $db->sub_query('
								SELECT DISTINCT p.' . $list->tree->field_id . '
									FROM ' . $db->table($list->tree->table) . ' c, ' . $db->table($list->tree->table) . ' p, ' . $db->table('groups_auths') . ' ga
									WHERE c.' . $list->tree->field_lid . ' BETWEEN p.' . $list->tree->field_lid . ' AND p.' . $list->tree->field_rid . '
										AND c.' . $list->tree->field_id . ' IN(' . implode(', ', array_keys($ids_managed)) . ')
									', __LINE__, __FILE__) . ')
							ORDER BY ' . $list->tree->field_lid;
				$result = $db->query($sql, __LINE__, __FILE__);
				while ( ($row = $db->fetch($result)) )
				{
					if ( $ids_managed && isset($ids_managed[ (int) $row[$list->tree->field_id] ]) )
					{
						$row['_is_manager'] = true;
					}
					// actor can check this entry
					if ( $list->tree->allowed((int) $row['obj_id'], 'view', 'manage') )
					{
						$list->tree->data[ (int) $row[$list->tree->field_id] ] = $row;
					}
				}
				$db->free($result);
			}
			unset($ids_managed);
		}
		$list->set_switch('content');
		$list->display_tree();
		sys::kill($list);
		unset($list);

		return 'groups.managers.users.content';
	}
}

?>