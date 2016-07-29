<?php
//
//	file: sys/tree.list.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 15/02/2008
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class tree_list extends sys_stdclass
{
	var $switch;
	var $tree;

	function __construct()
	{
		parent::__construct();
		$this->switch = 'row';
		$this->tree = false;
	}

	function __destruct()
	{
		if ( isset($this->tree) )
		{
			sys::kill($this->tree);
			unset($this->tree);
		}
		unset($this->switch);
		parent::__destruct();
	}

	function set_switch($switch)
	{
		$this->switch = $switch;
	}

	function process()
	{
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
		// read the tree
		$this->tree->read();
		return true;
	}

	function check() {}
	function validate() {}

	function display($id=false)
	{
		return $this->display_tree($id);
	}

	// display from a node
	function display_tree($id=false)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		// full tree switch
		$tpl->add($this->switch ? implode('.', array_slice(explode('.', $this->switch), 0, -1)) : '', array(
			'FULL_TREE' => !$id,
		));

		// get keys
		$keys = $this->tree->data ? array_keys($this->tree->data) : array();
		$count_keys = count($keys);

		// display
		$stack = array();
		$tpl_data = false;
		$start = $id ? 0 : 1;
		for ( $i = $start; $i < $count_keys; $i++ )
		{
			// if rid > parent.rid, we have finished with this parent, so remove it from the stack
			$count_close = 0;
			while ( ($count_stack = count($stack)) && ($this->tree->data[ $keys[$i] ][$this->tree->field_rid] > $this->tree->data[ $stack[ ($count_stack - 1) ] ][$this->tree->field_rid]) )
			{
				array_pop($stack);
				if ( $count_close )
				{
					if ( $tpl_data )
					{
						if ( ($tpl_data['LID'] + 1 < $tpl_data['RID']) && isset($this->tree->data[ $tpl_data['ID'] ]['_closed']) )
						{
							$tpl_data['IS_OPENABLE'] = true;
						}
						$tpl->add($this->switch, $tpl_data);
						$this->tree->after_options($tpl_data);
						$tpl_data = false;
					}
					$tpl->add($this->switch . '.close');
				}
				$count_close++;
			}

			// send to tpl
			$option = $this->tree->get_option($keys[$i]);
			if ( $tpl_data )
			{
				if ( $tpl_data['ID'] == $option['PID'] )
				{
					$tpl_data['IS_OPENED'] = true;
				}
				else if ( ($tpl_data['LID'] + 1 < $tpl_data['RID']) && isset($this->tree->data[ $tpl_data['ID'] ]['_closed']) )
				{
					$tpl_data['IS_OPENABLE'] = true;
				}
				$tpl->add($this->switch, $tpl_data);
				$this->tree->after_options($tpl_data);
				$tpl_data = false;
			}
			$tpl_data = $option;
			array_push($stack, $keys[$i]);
		}

		// send last option
		if ( $tpl_data )
		{
			if ( ($tpl_data['LID'] + 1 < $tpl_data['RID']) && isset($this->tree->data[ $tpl_data['ID'] ]['_closed']) )
			{
				$tpl_data['IS_OPENABLE'] = true;
			}
			$tpl->add($this->switch, $tpl_data);
			$this->tree->after_options($tpl_data);
			$tpl_data = false;
		}

		// close remaining level
		for ( $i = count($stack); $i > 1; $i-- )
		{
			$tpl->add($this->switch . '.close');
		}
		return true;
	}
}

?>