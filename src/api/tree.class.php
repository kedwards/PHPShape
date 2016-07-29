<?php
//
//	file: inc/tree.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 13/02/2008
//	version: 0.0.3 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class tree extends sys_stdclass
{
	var $data;
	var $table;
	var $type;
	var $field;
	var $legend;

	var $field_id;
	var $field_pid;
	var $field_lid;
	var $field_rid;

	function __construct()
	{
		parent::__construct();
		$this->data = false; // data indexed per id

		$this->table = false; // table name
		$this->type = false; // url parm name
		$this->field = false; // field prefix
		$this->legend = false; // legend prefix

		$this->field_id = false; // field . _id
		$this->field_pid = false; // field . _pid
		$this->field_lid = false; // field . _lid
		$this->field_rid = false; // field . _rid
	}

	function __destruct()
	{
		unset($this->field_rid, $this->field_lid, $this->field_pid, $this->field_id);

		unset($this->legend);
		unset($this->field);
		unset($this->type);
		unset($this->table);
		unset($this->data);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->field_id = $this->field . '_id';
		$this->field_pid = $this->field . '_pid';
		$this->field_lid = $this->field . '_lid';
		$this->field_rid = $this->field . '_rid';
	}

	// private: root def
	function _root($rid=false)
	{
		return array(0 => array(
			$this->field . '_name' => $this->legend . '_root',
			$this->field . '_name_trs' => true,
			$this->field_id => 0,
			$this->field_lid => 0,
			$this->field_rid => $rid,
		));
	}

	// read the whole tree
	function read()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$this->data = $this->_root();
		$sql = $this->_read_sql();
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$this->data[ (int) $row[$this->field_id] ] = $row;
		}
		$db->free($result);
		$this->data[0][$this->field_rid] = (count($this->data) - 1) * 2 + 1;
		return true;
	}

	function _read_sql()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table)  . '
					WHERE ' . $this->field_rid . ' <> 0
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	// read one node
	function read_item($args)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !is_array($args) )
		{
			$args = func_get_args();
		}
		if ( !$args )
		{
			return false;
		}
		$count = 0;
		$ids = array();
		while ( $args )
		{
			if ( ($id = intval(array_pop($args))) )
			{
				if ( isset($this->data[$id]) )
				{
					$count++;
				}
				else
				{
					$ids[] = $id;
				}
			}
		}
		unset($args);
		if ( !($count_ids = count($ids)) )
		{
			return $count;
		}
		$sql = $this->_read_item_sql($ids);
		unset($ids);
		$result = $db->query($sql, __LINE__, __FILE__);
		unset($sql);
		while ( ($row = $db->fetch($result)) )
		{
			$this->data[ (int) $row[$this->field_id] ] = $row;
			$count++;
		}
		$db->free($result);
		return $count;
	}

	function _read_item_sql($ids)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$count_ids = count($ids);
		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_id . ($count_ids > 1 ? ' IN(' . implode(', ', $ids) . ')' : ' = ' . intval($ids[0])) . '
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	//
	// the following methods require the tree to be reread
	//

	// insert a node in the tree
	function insert($fields)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// make some room at the insert point
		$sql = 'UPDATE ' . $db->table($this->table) . '
					SET ' . $this->field_lid . ' = ' . $this->field_lid . ' + 2, ' . $this->field_rid . ' = ' . $this->field_rid . ' + 2
					WHERE ' . $this->field_lid . ' >= ' . intval($fields[$this->field_lid]);
		$db->query($sql, __LINE__, __FILE__);

		// adjust ancestors
		$sql = 'UPDATE ' . $db->table($this->table) . '
					SET ' . $this->field_rid . ' = ' . $this->field_rid . ' + 2
					WHERE ' . $this->field_lid . ' < ' . intval($fields[$this->field_lid]) . '
						AND ' . $this->field_rid . ' >= ' . intval($fields[$this->field_lid]);
		$db->query($sql, __LINE__, __FILE__);

		// create the node
		$sql = 'INSERT INTO ' . $db->table($this->table) . '
					(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
		$db->query($sql, __LINE__, __FILE__);
		$id = $db->next_id();
		$this->data[$id] = array($this->field_id => $id) + $fields;

		return $id;
	}

	// update
	function update($id, $fields)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// do we need to move the branch ?
		$lid = $this->data[$id][$this->field_lid];
		$rid = $this->data[$id][$this->field_rid];
		if ( isset($fields[$this->field_lid]) && (($fields[$this->field_lid] != $lid) || ($fields[$this->field_rid] != $rid)) )
		{
			$nodes = $rid - $lid + 1; // actually the number of nodes - including this one - would be: (rid - lid + 1) / 2, but this is a convenient notation :)

			// make some room at the insert point
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $this->field_lid . ' = ' . $this->field_lid . ' + ' . intval($nodes) . ', ' . $this->field_rid . ' = ' . $this->field_rid . ' + ' . intval($nodes) . '
						WHERE ' . $this->field_lid . ' >= ' . intval($fields[$this->field_lid]);
			$db->query($sql, __LINE__, __FILE__);

			// adjust ancestors
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $this->field_rid . ' = ' . $this->field_rid . ' + ' . intval($nodes) . '
						WHERE ' . $this->field_lid . ' < ' . intval($fields[$this->field_lid]) . '
							AND ' . $this->field_rid . ' >= ' . intval($fields[$this->field_lid]);
			$db->query($sql, __LINE__, __FILE__);

			// the block to move is set after the target point
			if ( $lid >= $fields[$this->field_lid] )
			{
				$lid += $nodes;
				$rid += $nodes;
			}

			// move the block
			$delta = $fields[$this->field_lid] - $lid;
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $this->field_lid . ' = ' . $this->field_lid . ($delta < 0 ? ' - ' : ' + ') . abs($delta) . ', ' . $this->field_rid . ' = ' . $this->field_rid . ($delta < 0 ? ' - ' : ' + ') . abs($delta) . '
						WHERE ' . $this->field_lid . ' BETWEEN ' . intval($lid) . ' AND ' . intval($rid);
			$db->query($sql, __LINE__, __FILE__);

			// remove the gap generated at $lid -> $rid place
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $this->field_lid . ' = ' . $this->field_lid . ' - ' . intval($nodes) . ', ' . $this->field_rid . ' = ' . $this->field_rid . ' - ' . intval($nodes) . '
						WHERE ' . $this->field_lid . ' >= ' . intval($lid);
			$db->query($sql, __LINE__, __FILE__);

			// adjust ancestors
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $this->field_rid . ' = ' . $this->field_rid . ' - ' . intval($nodes) . '
						WHERE ' . $this->field_lid . ' < ' . intval($lid) . '
							AND ' . $this->field_rid . ' >= ' . intval($lid);
			$db->query($sql, __LINE__, __FILE__);
		}

		// update the node
		$this->data[$id] = array_merge($this->data[$id], $fields);
		if ( isset($fields[$this->field_lid]) )
		{
			unset($fields[$this->field_lid], $fields[$this->field_rid]);
		}
		if ( $fields )
		{
			$sql = 'UPDATE ' . $db->table($this->table) . '
						SET ' . $db->fields('update', $fields) . '
						WHERE ' . $this->field_id . ' = ' . intval($id);
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}

	// delete a node (and its sub-branch)
	function delete($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$lid = $this->data[$id][$this->field_lid];
		$rid = $this->data[$id][$this->field_rid];
		$nodes = $rid - $lid + 1;

		// delete dependant data
		$this->delete_dependancies($id);

		// delete all sub-nodes
		$sql = 'DELETE FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_lid . ' BETWEEN ' . intval($lid) . ' AND ' . intval($rid);
		$db->query($sql, __LINE__, __FILE__);

		// remove the gap generated at $lid -> $rid place
		$sql = 'UPDATE ' . $db->table($this->table) . '
					SET ' . $this->field_lid . ' = ' . $this->field_lid . ' - ' . intval($nodes) . ', ' . $this->field_rid . ' = ' . $this->field_rid . ' - ' . intval($nodes) . '
					WHERE ' . $this->field_lid . ' >= ' . intval($lid);
		$db->query($sql, __LINE__, __FILE__);

		// adjust ancestors
		$sql = 'UPDATE ' . $db->table($this->table) . '
					SET ' . $this->field_rid . ' = ' . $this->field_rid . ' - ' . intval($nodes) . '
					WHERE ' . $this->field_lid . ' < ' . intval($lid) . '
						AND ' . $this->field_rid . ' >= ' . intval($lid);
		$db->query($sql, __LINE__, __FILE__);
	}

	// delete dependant data
	function delete_dependancies($id)
	{
	}

	// move a branch to another point
	function move($moved_id, $after_id, $parent_id, $at_end=false)
	{
		if ( ($fields = $this->get_reattach($moved_id, $after_id, $parent_id, $at_end)) )
		{
			$this->update($moved_id, $fields);
		}
		return $fields ? true : false;
	}

	// protected: get pid/lid/rid to where re-attach the moved_id
	function get_reattach($moved_id, $after_id, $parent_id, $at_end=false)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !($moved_id = intval($moved_id)) )
		{
			return false;
		}
		$pid = $lid = $rid = false;

		// move after after id
		if ( $after_id )
		{
			if ( !($after_id = intval($after_id)) || ($this->read_item($moved_id, $after_id) < 2) )
			{
				return false;
			}

			// after_id can not be a child of moved_id
			if ( $after_id && ($this->data[$after_id][$this->field_lid] >= $this->data[$moved_id][$this->field_lid]) && ($this->data[$after_id][$this->field_lid] <= $this->data[$moved_id][$this->field_rid]) )
			{
				return false;
			}

			// fill the vars
			$pid = intval($this->data[$after_id][$this->field_pid]);
			$lid = intval($this->data[$after_id][$this->field_rid]) + 1;
			$rid = $lid + ($this->data[$moved_id][$this->field_rid] - $this->data[$moved_id][$this->field_lid]);
		}
		// move at root of parent id
		else
		{
			if ( (($parent_id = intval($parent_id)) && ($this->read_item($moved_id, $parent_id) < 2)) || (!$parent_id && !$this->read_item($moved_id)) )
			{
				return false;
			}

			// parent_id can not be a child of moved_id
			if ( $parent_id && ($this->data[$parent_id][$this->field_lid] >= $this->data[$moved_id][$this->field_lid]) && ($this->data[$parent_id][$this->field_lid] <= $this->data[$moved_id][$this->field_rid]) )
			{
				return false;
			}

			// if at end, we need the last child
			if ( $at_end )
			{
				$t_lid = 0;
				if ( $parent_id )
				{
					$t_lid = intval($this->data[$parent_id][$this->field_rid]);
				}
				// get the last root child
				else
				{
					$sql = 'SELECT COUNT(*) AS count_rows
								FROM ' . $db->table($this->table);
					$result = $db->query($sql, __LINE__, __FILE__);
					$t_lid = ($row = $db->fetch($result)) ? intval($row['count_rows']) * 2 + 1 : 1;
					$db->free($result);
				}
				$pid = intval($parent_id);
				$lid = intval($t_lid);
				$rid = $lid + ($this->data[$moved_id][$this->field_rid] - $this->data[$moved_id][$this->field_lid]);
			}
			else
			{
				$pid = intval($parent_id);
				$lid = $parent_id ? intval($this->data[$parent_id][$this->field_lid]) + 1 : 1;
				$rid = $lid + ($this->data[$moved_id][$this->field_rid] - $this->data[$moved_id][$this->field_lid]);
			}
		}

		// let's go
		return array(
			$this->field_pid => (int) $pid,
			$this->field_lid => (int) $lid,
			$this->field_rid => (int) $rid,
		);
	}

	function get_option($id)
	{
		return array(
			'ID' => (int) $id,
			'LID' => (int) $this->data[$id][$this->field_lid],
			'RID' => (int) $this->data[$id][$this->field_rid],
			'PID' => (int) $this->data[$id][$this->field_pid],
			'NAME' => isset($this->data[$id][$this->field . '_name']) ? sys_string::htmlspecialchars($this->data[$id][$this->field . '_name']) : '',
			'NAME_TRS' => isset($this->data[$id][$this->field . '_name_trs']) ? (int) $this->data[$id][$this->field . '_name_trs'] : false,
			'DESC' => isset($this->data[$id][$this->field . '_desc']) ? sys_string::htmlspecialchars($this->data[$id][$this->field . '_desc']) : '',
			'DESC_TRS' => isset($this->data[$id][$this->field . '_desc_trs']) ? (int) $this->data[$id][$this->field . '_desc_trs'] : false,
		);
	}

	function after_options($data)
	{
		return false;
	}
}

?>