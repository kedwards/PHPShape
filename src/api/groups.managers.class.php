<?php
//
//	file: inc/groups.managers.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.3 - 26/03/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_managers extends sys_stdclass
{
	var $groups;
	var $users;
	var $content_id;
	var $content_type;

	function __construct()
	{
		parent::__construct();
		$this->groups = false;
		$this->users = false;
		$this->content_id = false;
		$this->content_type = false;
	}

	function __destruct()
	{
		unset($this->content_type);
		unset($this->content_id);
		if ( isset($this->users) )
		{
			sys::kill($this->users);
			unset($this->users);
		}
		if ( isset($this->groups) )
		{
			sys::kill($this->groups);
			unset($this->groups);
		}
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$class = $sys->ini_get('groups.managers.groups.list', 'class');
		$this->groups = new $class();
		$this->groups->set($this->api_name);

		$class = $sys->ini_get('groups.managers.users', 'class');
		$this->users = new $class();
		$this->users->set($this->api_name);
	}

	function process()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$user = &$api->user;

		if ( !$user->is_admin && (!$user->auth_types_manager || !isset($user->auth_types_manager[SYS_U_GROUP])) )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		// item checked
		$item_id = $io->read(SYS_U_ITEM, 0);

		// check if we jump here from another menu
		if ( !$api->ajax )
		{
			$content_type = $io->read('jct', '');
			$content_id = $io->read('jcid', 0);
			if ( $content_type && $content_id && in_array($content_type, array(SYS_U_GROUP, SYS_U_USER)) )
			{
				switch ( $content_type )
				{
					case SYS_U_GROUP:
						$api->mode_action = 'group.content';
						$api->mode = preg_replace('#[\.]+#', '.', implode('.', array($api->mode_base, $api->mode_sub, $api->mode_action)));
						$item_id = $content_id;
					break;
					case SYS_U_USER:
						$api->mode_action = 'user.content';
						$api->mode = preg_replace('#[\.]+#', '.', implode('.', array($api->mode_base, $api->mode_sub, $api->mode_action)));
						$item_id = $content_id;
					break;
				}
			}
		}

		$action = explode('.', $api->mode_action);
		switch ( $action[0] )
		{
			case 'group':
				if ( ($action[1] != 'content') && ($handler = $this->groups->process($action[1], $item_id)) )
				{
					return $handler;
				}
			break;
		}
		switch ( $api->mode_action )
		{
			// groups
			case 'group.content':
				if ( $item_id )
				{
					$this->content_type = 'group';
					$this->content_id = $item_id;
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;
			case 'group.move':
				if ( $item_id )
				{
					$this->content_type = ($type = $io->read('ct', '')) && in_array($type, array('group', 'user')) ? $type : false;
					$this->content_id = $this->content_type && ($id = $io->read('cid', 0)) ? $id : false;
					$to_id = $io->read('tid', 0);
					$this->move('group', $item_id, $to_id);
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;
			case 'group.remove':
				if ( $item_id )
				{
					$this->content_type = ($type = $io->read('ct', '')) && in_array($type, array('group', 'user')) ? $type : false;
					$this->content_id = $this->content_type && ($id = $io->read('cid', 0)) ? $id : false;
					$this->remove('group', $item_id, $this->content_id);
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;

			// users
			case 'user.content':
				if ( $item_id )
				{
					$this->content_type = 'user';
					$this->content_id = $item_id;
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;
			case 'user.move':
				if ( $item_id )
				{
					$this->content_type = ($type = $io->read('ct', '')) && in_array($type, array('group', 'user')) ? $type : false;
					$this->content_id = $this->content_type && ($id = $io->read('cid', 0)) ? $id : false;
					$to_id = $io->read('tid', 0);
					$this->move('user', $item_id, $to_id);
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;
			case 'user.remove':
				if ( $item_id )
				{
					$this->content_type = ($type = $io->read('ct', '')) && in_array($type, array('group', 'user')) ? $type : false;
					$this->content_id = $this->content_type && ($id = $io->read('cid', 0)) ? $id : false;
					$this->remove('user', $item_id, $this->content_id);
					if ( $api->ajax )
					{
						return $this->display_content();
					}
				}
			break;
			case 'user.filter':
				if ( $api->ajax )
				{
					return $this->users->display();
				}
			break;
		}

		if ( $this->init() )
		{
			$this->check();
			$this->validate();
			return $this->display();
		}
		return false;
	}

	function init()
	{
		return $this->groups->tree->read_open();
	}

	function check() {}
	function validate() {}

	function display($item_id=false)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;

		$this->users->display();
		$this->groups->display_tree($item_id);
		$this->display_content();

		// constants
		$tpl->add(array(
			'SCRIPT_TITLE' => 'users_list_title',
			'CONTENT_TYPE' => $this->content_type ? $this->content_type : '',
		));

		$api->mode = $api->mode_base . '.' . $api->mode_sub;
		return 'groups.managers';
	}

	function display_content()
	{
		switch ( $this->content_type )
		{
			case 'group':
				return $this->groups->display_content($this->content_id);
			break;
			case 'user':
				return $this->users->display_content($this->content_id);
			break;
		}
		return false;
	}

	function move($item_type, $item_id, $to_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$user_id = $item_type == 'group' ? $to_id : $item_id;
		$group_id = $item_type == 'group' ? $item_id : $to_id;

		// check if the user exists
		$class = $sys->ini_get('user', 'class');
		$user = new $class();
		$user->set($this->api_name);
		$user->read($user_id);
		if ( !$user->data )
		{
			return false;
		}
		$user->read_groups();

		// check if the group exists
		$class = $sys->ini_get('groups', 'class');
		$group = new $class();
		$group->set($this->api_name);
		$group->read_item($group_id, SYS_GROUP_GUESTS, SYS_GROUP_MEMBERS);
		if ( !isset($group->data[$group_id]) )
		{
			return false;
		}

		// check if the actor is allowed to manage this group
		if ( !$group->allowed($group_id, 'manage') )
		{
			return false;
		}

		// if the user is an admin or a guest, or if the group is the admin one, deny him
		if ( ($group_id == SYS_GROUP_OWNERS) || $user->is_admin || isset($user->group_ids[SYS_GROUP_GUESTS]) )
		{
			return false;
		}

		// if the actor tries to act on himlself, deny it
		if ( $api->user->data['user_id'] && ($user->data['user_id'] == $api->user->data['user_id']) )
		{
			return false;
		}

		// delete all child groups authorizations for this user
		if ( $user->individual_group_id )
		{
			$sql = 'DELETE FROM ' . $db->table('groups_auths') . '
						WHERE group_id = ' . intval($user->individual_group_id) . '
							AND obj_id IN(' . $db->sub_query('
								SELECT ' . $group->field_id . '
									FROM ' . $db->table($group->table) . '
									WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . intval($group->data[$group_id][$group->field_rid]) . '
							', __LINE__, __FILE__) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}

		// create the individual group if missing
		if ( !$user->individual_group_id )
		{
			$fields = array(
				'group_pid' => 0,
				'group_lid' => 0,
				'group_rid' => 0,
				'group_name' => (string) $user->data['user_ident'],
				'group_name_trs' => false,
				'group_desc' => (string) $user->data['user_realname'],
				'user_id' => intval($user_id),
			);
			$sql = 'INSERT INTO ' . $db->table($group->table) . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
			$user->individual_group_id = $db->next_id();

			$fields = array(
				'user_id' => (int) $user->data['user_id'],
				'group_id' => (int) $user->individual_group_id,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);

			$user->group_ids[$user->individual_group_id] = true;
		}

		// create the auths
		$fields_names = array('group_id', 'obj_id', 'auth_type', 'auth_name');
		$fields_values = array();
		$sql = 'SELECT *
					FROM ' . $db->table($group->table) . '
					WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . intval($group->data[$group_id][$group->field_rid]) . '
						AND ' . $group->field_id . ' <> ' . intval(SYS_GROUP_OWNERS) . '
					ORDER BY ' . $group->field_lid;
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$fields_values[] = array(
				(int) $user->individual_group_id,
				(int) $row[$group->field_id],
				(string) $group->type,
				(string) 'manage',
			);
		}
		$db->free($result);
		if ( $fields_values )
		{
			$sql = 'INSERT INTO ' . $db->table('groups_auths') . '
						(' . $db->fields('fields', $fields_names) . ') VALUES(' . $db->fields('values', $fields_values) . ')';
			unset($fields_values);
			$db->query($sql, __LINE__, __FILE__);
			unset($sql);
		}
		return true;
	}

	function remove($item_type, $item_id, $from_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		$db = &$api->db;

		$user_id = $item_type == 'group' ? $from_id : $item_id;
		$group_id = $item_type == 'group' ? $item_id : $from_id;

		// check if the user exists
		$class = $sys->ini_get('user', 'class');
		$user = new $class();
		$user->set($this->api_name);
		$user->read($user_id);
		if ( !$user->data )
		{
			return false;
		}
		$user->read_groups();

		// if the user has no invidual group, there are no authorization to remove
		if ( !$user->individual_group_id )
		{
			return false;
		}

		// check if the group exists
		$class = $sys->ini_get('groups', 'class');
		$group = new $class();
		$group->set($this->api_name);
		$group->read_item($group_id, SYS_GROUP_GUESTS, SYS_GROUP_MEMBERS, SYS_GROUP_OWNERS);
		if ( !isset($group->data[$group_id]) )
		{
			return false;
		}

		// check if the actor is allowed to manage this group
		if ( !$group->allowed($group_id, 'manage') )
		{
			return false;
		}

		// if the user is an admin or a guest, or if the group is the admin one, deny them
		if ( ($group_id == SYS_GROUP_OWNERS) || $user->is_admin || isset($user->group_ids[SYS_GROUP_GUESTS]) )
		{
			return false;
		}

		// if the actor tries to act on himlself, deny it
		if ( $api->user->data['user_id'] && ($user->data['user_id'] == $api->user->data['user_id']) )
		{
			return false;
		}

		// delete all child groups authorizations for this user
		$sql = 'DELETE FROM ' . $db->table('groups_auths') . '
					WHERE group_id = ' . intval($user->individual_group_id) . '
						AND obj_id IN(' . $db->sub_query('
							SELECT ' . $group->field_id . '
								FROM ' . $db->table($group->table) . '
								WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . intval($group->data[$group_id][$group->field_rid]) . '
						', __LINE__, __FILE__) . ')';
		$db->query($sql, __LINE__, __FILE__);

		// does the user has still individual group authorization ?
		$sql = 'SELECT *
					FROM ' . $db->table('groups_auths') . '
					WHERE group_id = ' . intval($user->individual_group_id) . '
					LIMIT 1';
		$result = $db->query($sql, __LINE__, __FILE__);
		$exists = ($row = $db->fetch($result)) ? true : false;
		$db->free($result);
		if ( !$exists )
		{
			$sql = 'DELETE FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user->data['user_id']) . '
							AND group_id = ' . intval($user->individual_group_id);
			$db->query($sql, __LINE__, __FILE__);

			$sql = 'DELETE FROM ' . $db->table('groups') . '
						WHERE group_id = ' . intval($user->individual_group_id);
			$db->query($sql, __LINE__, __FILE__);

			unset($user->group_ids[$user->individual_group_id]);
			$user->individual_group_id = false;
		}

		return true;
	}
}

?>