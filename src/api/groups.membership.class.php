<?php
//
//	file: inc/groups.membership.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.3 - 26/03/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_membership extends sys_stdclass
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

		$class = $sys->ini_get('groups.membership.groups.list', 'class');
		$this->groups = new $class();
		$this->groups->set($this->api_name);

		$class = $sys->ini_get('groups.membership.users', 'class');
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
				if ( ($handler = $this->groups->process($action[1], $item_id)) )
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
						switch ( $this->content_type )
						{
							case 'group':
								return $this->groups->display_content($this->content_id);
							break;
							case 'user':
								$this->users->display_content($this->content_id);
								return 'groups.membership.users.content';
							break;
						}
						return false;
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
						switch ( $this->content_type )
						{
							case 'group':
								return $this->groups->display_content($this->content_id);
							break;
							case 'user':
								$this->users->display_content($this->content_id);
								return 'groups.membership.users.content';
							break;
						}
						return false;
					}
				}
			break;
			case 'group.filter':
				if ( $api->ajax && $item_id )
				{
					$this->groups->display_content($item_id);
					return 'groups.membership.groups.content';
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
						$this->users->display_content($this->content_id);
						return 'groups.membership.users.content';
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
						switch ( $this->content_type )
						{
							case 'group':
								$this->groups->display_content($this->content_id);
								return 'groups.membership.groups.content';
							break;
							case 'user':
								$this->users->display_content($this->content_id);
								return 'groups.membership.users.content';
							break;
						}
						return false;
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
						switch ( $this->content_type )
						{
							case 'group':
								$this->groups->display_content($this->content_id);
								return 'groups.membership.groups.content';
							break;
							case 'user':
								$this->users->display_content($this->content_id);
								return 'groups.membership.users.content';
							break;
						}
						return false;
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

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		$tpl = &$sys->tpl;

		$this->groups->display_tree();
		switch ( $this->content_type )
		{
			case 'group':
				$this->groups->display_content($this->content_id);
			break;
			case 'user':
				$this->users->display_content($this->content_id);
			break;
		}

		// constants
		$tpl->add(array(
			'SCRIPT_TITLE' => 'users_list_title',
			'CONTENT_TYPE' => $this->content_type ? $this->content_type : '',
		));

		// all users list
		$this->users->display();

		$api->mode = $api->mode_base . '.' . $api->mode_sub;
		return 'groups.membership';
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

		// check if the users belongs to a child group of this one
		$sql = 'SELECT ug.group_id
					FROM ' . $db->table('users_groups') . ' ug, ' . $db->table($group->table) . ' g
					WHERE ug.user_id = ' . intval($user_id) . '
						AND ug.group_id = g.' . $group->field_id . '
						AND g.' . $group->field_lid . ' BETWEEN ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . intval($group->data[$group_id][$group->field_rid]) . '
					LIMIT 1';
		$result = $db->query($sql, __LINE__, __FILE__);
		$exists = ($row = $db->fetch($result)) ? true : false;
		$db->free($result);
		if ( $exists )
		{
			return false;
		}

		// we may be moving a member to guests
		if ( ($to_guests = ($group->data[$group_id][$group->field_lid] >= $group->data[SYS_GROUP_GUESTS][$group->field_lid]) && ($group->data[$group_id][$group->field_rid] <= $group->data[SYS_GROUP_GUESTS][$group->field_rid])) )
		{
			// actor can not shoot himself
			if ( $user_id == $api->user->data['user_id'] )
			{
				return false;
			}

			// check if this user is part of the admin group. If so, it should be first dismissed
			$sql = 'SELECT group_id
						FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user_id) . '
							AND group_id = ' . intval(SYS_GROUP_OWNERS) . '
						LIMIT 1';
			$result = $db->query($sql, __LINE__, __FILE__);
			$is_admin = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $is_admin )
			{
				return false;
			}
		}

		// go and add it to the groups list
		$fields = array(
			'group_id' => (int) $group_id,
			'user_id' => (int) $user_id,
		);
		$sql = 'INSERT INTO ' . $db->table('users_groups') . '
					(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
		$db->query($sql, __LINE__, __FILE__);

		// remove any ancestor of this group
		$sql = 'DELETE FROM ' . $db->table('users_groups') . '
					WHERE user_id = ' . intval($user_id) . '
						AND group_id IN(' . $db->sub_query('
							SELECT ' . $group->field_id . '
								FROM ' . $db->table($group->table) . '
								WHERE ' . $group->field_lid . ' < ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . $group->field_rid . ' > ' . intval($group->data[$group_id][$group->field_lid]) . '
						', __LINE__, __FILE__) . ')';
		$db->query($sql, __LINE__, __FILE__);

		// remove guests from guests branch if moved to members, and members from members branch if moved to guests
		if ( $to_guests )
		{
			$sql = 'DELETE FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user_id) . '
							AND group_id IN(' . $db->sub_query('
								SELECT ' . $group->field_id . '
									FROM ' . $db->table($group->table) . '
									WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[SYS_GROUP_MEMBERS][$group->field_lid]) . ' AND ' . intval($group->data[SYS_GROUP_MEMBERS][$group->field_rid]) . '
							', __LINE__, __FILE__) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		else
		{
			$sql = 'DELETE FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user_id) . '
							AND group_id IN(' . $db->sub_query('
								SELECT ' . $group->field_id . '
									FROM ' . $db->table($group->table) . '
									WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[SYS_GROUP_GUESTS][$group->field_lid]) . ' AND ' . intval($group->data[SYS_GROUP_GUESTS][$group->field_rid]) . '
							', __LINE__, __FILE__) . ')';
			$db->query($sql, __LINE__, __FILE__);
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

		// for now, we can not remove someone from guests: it would mean "delete him"
		if ( $group_id == SYS_GROUP_GUESTS )
		{
			return false;
		}

		// we remove the user from members
		if ( $group_id == SYS_GROUP_MEMBERS )
		{
			// administrator can not be directly dismissed
			$sql = 'SELECT group_id
						FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user_id) . '
							AND group_id = ' . intval(SYS_GROUP_OWNERS) . '
						LIMIT 1';
			$result = $db->query($sql, __LINE__, __FILE__);
			$is_admin = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $is_admin )
			{
				return false;
			}
		}
		// we may remove the actor from admin group
		else if ( ($user_id == $api->user->data['user_id']) && ($group->data[SYS_GROUP_OWNERS][$group->field_lid] >= $group->data[$group_id][$group->field_lid]) && ($group->data[SYS_GROUP_OWNERS][$group->field_lid] <= $group->data[$group_id][$group->field_rid]) )
		{
			// ohoh: check if the actor is admin
			$sql = 'SELECT group_id
						FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($user_id) . '
							AND group_id = ' . intval(SYS_GROUP_OWNERS) . '
						LIMIT 1';
			$result = $db->query($sql, __LINE__, __FILE__);
			$is_admin = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $is_admin )
			{
				return false;
			}
		}

		// get the parent id and check it exists
		$pid = 0;
		if ( !in_array($group_id, array(SYS_GROUP_MEMBERS, SYS_GROUP_GUESTS)) )
		{
			$pid = $group->data[$group_id][$group->field_pid];
			if ( !$group->read_item($pid) )
			{
				return false;
			}
		}

		// delete links to this group and its childs
		$sql = 'DELETE FROM ' . $db->table('users_groups') . '
					WHERE user_id = ' . intval($user_id) . '
						AND group_id IN(' . $db->sub_query('
							SELECT ' . $group->field_id . '
								FROM ' . $db->table($group->table) . '
								WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[$group_id][$group->field_lid]) . ' AND ' . intval($group->data[$group_id][$group->field_rid]) . '
						', __LINE__, __FILE__) . ')';
		$db->query($sql, __LINE__, __FILE__);

		// if we remove the user from members, register him to guests
		if ( $group_id == SYS_GROUP_MEMBERS )
		{
			$fields = array(
				'user_id' => (int) $user_id,
				'group_id' => (int) SYS_GROUP_GUESTS,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
			return true;
		}
		// is the user already member of the parent ?
		$sql = 'SELECT group_id
					FROM ' . $db->table('users_groups') . '
					WHERE user_id = ' . intval($user_id) . '
						AND group_id IN(' . $db->sub_query('
							SELECT ' . $group->field_id . '
								FROM ' . $db->table($group->table) . '
								WHERE ' . $group->field_lid . ' BETWEEN ' . intval($group->data[$pid][$group->field_lid]) . ' AND ' . intval($group->data[$pid][$group->field_rid]) . '
						', __LINE__, __FILE__) . ')
					LIMIT 1';
		$result = $db->query($sql, __LINE__, __FILE__);
		$exists = $db->fetch($result) ? true : false;
		$db->free($result);
		if ( $exists )
		{
			return true;
		}

		// add the user to the parent
		if ( !in_array($group_id, array(SYS_GROUP_MEMBERS, SYS_GROUP_GUESTS)) )
		{
			$fields = array(
				'user_id' => (int) $user_id,
				'group_id' => (int) $pid,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}
}

?>