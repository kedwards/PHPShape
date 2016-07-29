<?php
//
//	file: inc/tree.auths.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/03/2009
//	version: 0.0.1 - 28/03/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class tree_auths_list extends tree_list
{
	var $item_id;

	function __construct()
	{
		parent::__construct();
		$this->item_id = false;
	}

	function __destruct()
	{
		unset($this->item_id);
		parent::__destruct();
	}

	function process_action($action, $item_id)
	{
		$api = &$GLOBALS[$this->api_name];
		switch ( $action )
		{
			case 'open':
				if ( $this->open_tree($item_id) )
				{
					return $this->display_tree($item_id);
				}
			break;

			case 'close':
				if ( $this->close_tree($item_id) )
				{
					return $this->display_tree($item_id);
				}
			break;

			case 'content':
				if ( $api->ajax )
				{
					return $this->display_content($item_id);
				}
			break;
		}
		return false;
	}

	function init()
	{
		return $this->tree->read_open();
	}

	function display()
	{
		$this->display_tree();
		$this->display_content($this->item_id);
		return true;
	}

	// display content
	function display_content($item_id)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$this->tree->read_content($item_id);
		$tpl->add(array(
			'CONTENT_ID' => $item_id,
		));
		if ( $this->tree->data )
		{
			$found = false;
			foreach ( $this->tree->data as $id => $dummy )
			{
				$tpl_data = $this->tree->get_option($id);
				if ( !$found )
				{
					$tpl->add('breadscrumb', $tpl_data);
				}
				else if ( $this->tree->data[$id][$this->tree->field_pid] == $item_id )
				{
					$tpl->add('content', $this->tree->get_option($id));
				}
				$found |= ($id == $item_id);
			}
		}
		return true;
	}

	function open_tree($item_id)
	{
		$api = &$GLOBALS[$this->api_name];
		if ( $item_id )
		{
			$this->tree->open_close_branch($item_id, true);
			if ( $api->ajax )
			{
				$this->tree->read_open($item_id);
				return true;
			}
		}
		return false;
	}

	function close_tree($item_id)
	{
		$api = &$GLOBALS[$this->api_name];
		if ( $item_id )
		{
			$this->tree->open_close_branch($item_id, false);
			if ( $api->ajax )
			{
				$this->tree->read_open($item_id);
				return true;
			}
		}
		return false;
	}
}

?>