<?php
//
//	file: inc/groups.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.2 - 26/11/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_list extends tree_auths_list
{
	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$class = $sys->ini_get('groups', 'class');
		$this->tree = new $class();
		$this->tree->set($this->api_name);
	}

	function get_action()
	{
		$api = &$GLOBALS[$this->api_name];
		return $api->mode_sub;
	}

	function is_authorized()
	{
		$api = &$GLOBALS[$this->api_name];
		$actor = &$api->user;
		return $actor->is_admin || ($actor->auth_types_manager && isset($actor->auth_types_manager[SYS_U_GROUP]));
	}

	function process()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$user = &$api->user;

		if ( !$this->is_authorized() )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}
		$this->tree->set_minimal_auth('manage');

		// item checked
		$this->item_id = $io->read(SYS_U_ITEM, 0);

		// check if we jump here from another menu
		if ( !$api->ajax )
		{
			$content_type = $io->read('jct', '');
			$content_id = $io->read('jcid', 0);
			if ( $content_type && $content_id && ($content_type == SYS_U_GROUP) )
			{
				$api->mode_sub = '';
				$api->mode_action = '';
				$api->mode = $api->mode_base;
				$this->item_id = $content_id;
			}
		}

		$action = $this->get_action();
		if ( ($handler = $this->process_action($action, $this->item_id)) )
		{
			return $handler;
		}

		switch ( $action )
		{
			case 'move':
				if ( $this->item_id )
				{
					$after_id = $io->read('tid', 0);
					$parent_id = $io->read('pid', 0);
					if ( ($at_end = $io->exists('eid', '_GET')) )
					{
						$parent_id = $io->read('eid', 0);
					}
					$this->tree->move($this->item_id, $after_id, $parent_id, $at_end);
					$this->item_id = 0;
					if ( $api->ajax )
					{
						$this->tree->read_open();
						return $this->display_tree();
					}
				}
			break;

			case 'create':
			case 'edit':
			case 'delete':
				if ( ($class = $sys->ini_get('groups.form', 'class')) )
				{
					$form = new $class();
					$form->set($this->api_name);
					$handle = $form->process();
					sys::kill($form);
					unset($form);
					if ( $handle )
					{
						return $handle;
					}
					if ( $api->mode_sub != 'create' )
					{
						$this->item_id = $this->item_id && $this->tree->read_item($this->item_id) && $this->tree->data[$this->item_id] ? $this->tree->data[$this->item_id][$this->tree->field_pid] : 0;
					}
				}
			break;
		}
		return parent::process();
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;

		parent::display();

		// constants
		$tpl->add(array(
			'SCRIPT_TITLE' => 'group_list_title',
		));
		$api->mode = $api->mode_base;
		return 'groups.list';
	}

	function display_tree($item_id=false)
	{
		parent::display_tree($item_id);
		return 'groups.list.tree';
	}

	// display content
	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		parent::display_content($item_id);

		$tpl->add(array(
			'CAN_CREATE' => $item_id && ($item_id != SYS_GROUP_OWNERS) && $this->tree->allowed($item_id, 'manage'),
		));
		return 'groups.list.content';
	}

	function read_membership($user)
	{
		if ( !$user || !$user->data || !$user->group_ids )
		{
			return false;
		}
		return $this->tree->read_membership($user);
	}
}

?>