<?php
//
//	file: inc/groups.membership.users.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.3 - 26/03/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_membership_users extends users_list
{
	function display()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$tpl->add('mute_admin');
		return parent::display();
	}

	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$actor = &$api->user;

		$class = $sys->ini_get('user', 'class');
		$user = new $class();
		$user->set($this->api_name);
		$user->read($item_id);
		if ( !$user->data )
		{
			return false;
		}
		$user->read_groups();

		// check if the actor can manage the viewed profile
		$is_manager = $actor->is_admin;
		if ( !$is_manager && $user->group_ids )
		{
			// get groups to check allowance
			$class = $sys->ini_get('groups', 'class');
			$groups = new $class();
			$groups->set($this->api_name);
			$groups->read_membership($user);
			foreach ( $user->group_ids as $group_id => $dummy )
			{
				if ( $groups->allowed($group_id, 'manage') )
				{
					$is_manager = true;
					break;
				}
			}
			sys::kill($groups);
			unset($groups);
		}

		$itself = $user->data && $actor->data && ($user->data['user_id'] == $actor->data['user_id']);
		$can_edit = $itself || ($is_manager && ($actor->is_admin || !$user->is_admin));
		$can_delete = $can_edit && !$user->is_admin && !$itself;

		// breadscrumb
		$tpl->add(array(
			'CONTENT_TYPE' => 'user',
			'CONTENT_ID' => $item_id,
			'CONTENT_NAME' => $user->data['user_realname'],
			'CAN_EDIT' => $can_edit,
			'CAN_DELETE' => $can_delete,
		));

		// groups
		$class = $sys->ini_get('groups.list', 'class');
		$groups_list = new $class();
		$groups_list->set($this->api_name);
		$groups_list->read_membership($user);
		$groups_list->set_switch('content');
		$groups_list->display_tree();
		sys::kill($groups_list);
		unset($groups_list);

		return true;
	}
}

?>