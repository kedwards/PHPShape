<?php
//
//	file: inc/groups.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 06/08/2005
//	version: 0.0.2 - 26/11/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups extends tree_auths
{
	var $admin_owners;

	function __construct()
	{
		parent::__construct();
		$this->admin_owners = true;
	}

	function __destruct()
	{
		unset($this->admin_owners);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];

		$this->table = 'groups';
		$this->field = 'group';
		$this->type = SYS_U_GROUP;
		$this->legend = 'group';

		parent::set($api_name);

		$sys->lang->register('api.groups');
	}

	function disable_owners()
	{
		$this->admin_owners = false;
	}

	function reattach_allowed($moved_id, $fields)
	{
		if ( (!$fields[$this->field_pid] && $this->data[$moved_id][$this->field_pid]) || ($fields[$this->field_pid] && !$this->data[$moved_id][$this->field_pid]) )
		{
			return false;
		}
		if ( $fields[$this->field_pid] == SYS_GROUP_OWNERS )
		{
			return false;
		}

		// we can not move a group from guests to members (so the contrary)
		$this->read_item(SYS_GROUP_GUESTS, SYS_GROUP_MEMBERS, $fields[$this->field_pid]);
		if ( ($this->_belongs($moved_id, SYS_GROUP_GUESTS) && $this->_belongs($fields[$this->field_pid], SYS_GROUP_MEMBERS)) || ($this->_belongs($moved_id, SYS_GROUP_MEMBERS) && $this->_belongs($fields[$this->field_pid], SYS_GROUP_GUESTS)) )
		{
			return false;
		}
		return parent::reattach_allowed($moved_id, $fields[$this->field_pid]);
	}

	// check if a group belongs to a specific branch
	function _belongs($id, $parent_id)
	{
		return ($this->data[$id][$this->field_lid] >= $this->data[$parent_id][$this->field_lid]) && ($this->data[$id][$this->field_lid] <= $this->data[$parent_id][$this->field_rid]);
	}

	function read_membership($user)
	{
		$this->data = $this->_root();
		$group_ids = $user->group_ids;
		if ( $user->individual_group_id && isset($group_ids[$user->individual_group_id]) )
		{
			unset($group_ids[$user->individual_group_id]);
		}
		if ( $group_ids )
		{
			foreach ( $group_ids as $group_id => $dummy )
			{
				if ( !$this->allowed($group_id, 'view') )
				{
					unset($group_ids[$group_id]);
				}
			}
		}
		if ( !$group_ids )
		{
			return false;
		}
		$this->read_item(array_keys($group_ids));
		if ( $group_ids )
		{
			foreach ( $group_ids as $group_id => $is_own )
			{
				if ( isset($this->data[$group_id]) )
				{
					$this->data[$group_id]['_is_own'] = $is_own;
				}
			}
		}
	}

	// return a formated option for the list
	function get_option($id)
	{
		$basis = parent::get_option($id);
		$manage = $id && $this->data[$id][$this->field_pid] && ($id != SYS_GROUP_OWNERS) && $this->allowed($id, 'manage');
		$manage_pid = $manage && $this->data[$id][$this->field_pid] && $this->allowed($this->data[$id][$this->field_pid], 'manage');
		return !$basis ? false : $basis + array(
			'CAN_EDIT' => $manage,
			'CAN_DELETE' => $manage_pid,
			'CAN_MANAGE' => $id && $this->allowed($id, 'manage') && ($this->admin_owners || ($id != SYS_GROUP_OWNERS)),
			'IS_OWN' => isset($this->data[$id]['_is_own']) && $this->data[$id]['_is_own'],
			'IS_PARENT' => isset($this->data[$id]['_is_parent']) && $this->data[$id]['_is_parent'],
			'IS_MANAGER' => isset($this->data[$id]['_is_manager']) && $this->data[$id]['_is_manager'],
		);
	}

	// delete dependant data
	function delete_dependancies($id)
	{
		$api = &$GLOBALS[$this->api_name];

		// delete cascading
		$api->hooks->set_data('group.delete', array(
			'id' => (int) $id,
			'lid' => (int) $this->data[$id][$this->field_lid],
			'rid' => (int) $this->data[$id][$this->field_rid],
		));
		$api->hooks->process('group.delete');
		$api->hooks->unset_data('group.delete');

		parent::delete_dependancies($id);
	}

	// static
	function hook($api_name, $action)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$api_name];
		$tpl = &$sys->tpl;
		$db = &$api->db;
		$actor = &$api->user;

		$parm = $api->hooks->get_data($action);

		switch ( $action )
		{
			case 'actor.display':
				$actor->read_auths(SYS_U_GROUP);
				$tpl->add(array(
					'IS_GROUP_MANAGER' => $actor->data && ($actor->is_admin || ($actor->auth_types_manager && isset($actor->auth_types_manager[SYS_U_GROUP]))),
				));
			break;

			case 'user.delete':
				$user_id = (int) $parm['user_id'];
				$group_id = (int) $parm['individual_group_id'];
				$sql = 'DELETE FROM ' . $db->table('users_groups') . '
							WHERE user_id = ' . intval($user_id);
				$db->query($sql, __LINE__, __FILE__);

				if ( $group_id )
				{
					$sql = 'DELETE FROM ' . $db->table('groups') . '
								WHERE group_id = ' . intval($group_id);
					$db->query($sql, __LINE__, __FILE__);
				}
			break;
		}
	}
}

?>