<?php
//
//	file: inc/tree.auths.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/01/2009
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

// tree handling with auths and cached opened/closed list
class tree_auths extends tree
{
	var $auth_list;
	var $auth_manage;
	var $minimal_auth;

	function __construct()
	{
		parent::__construct();
		$this->auth_list = false;
		$this->auth_manage = false;
		$this->minimal_auth = false;
	}

	function __destruct()
	{
		unset($this->minimal_auth);
		unset($this->auth_manage);
		unset($this->auth_list);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);

		$this->auth_list = array('view', 'manage');
		$this->auth_manage = 'manage';
		$this->minimal_auth = 'view';
	}

	function set_minimal_auth($auth_name=false)
	{
		$this->minimal_auth = $auth_name;
	}

	function insert($fields)
	{
		if ( ($id = parent::insert($fields)) )
		{
			// copy management auths
			$this->copy_managers($id);
		}
		return $id;
	}

	// check auths: first parm: id, other, auths to check ("||" used)
	function allowed()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;

		$actor->read_groups();
		if ( $actor->is_admin )
		{
			return true;
		}

		// auths haven't been loaded yet
		if ( !isset($actor->auths[$this->type]) )
		{
			$this->load_auths();
		}

		// check auths
		$args = func_get_args();
		$count_args = count($args);

		// no parms
		if ( !$count_args )
		{
			return false;
		}
		// one id only, any auth will do
		if ( $count_args == 1 )
		{
			return intval($args[0]) && $actor->auth($this->type, false, $args[0]) ? true : false;
		}

		// an id and some auths, first granted will do
		$obj_id = intval(array_shift($args));
		$auth_names = array();
		while ( !empty($args) )
		{
			$arg = array_shift($args);
			if ( is_array($arg) )
			{
				$auth_names = array_merge($auth_names, $arg);
			}
			else
			{
				$auth_names[] = $arg;
			}
		}
		if ( $auth_names )
		{
			foreach ( $auth_names as $auth_name )
			{
				if ( $actor->auth($this->type, $auth_name, $obj_id) )
				{
					return true;
				}
			}
		}
		return false;
	}

	function load_auths()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;

		// propage view & manage auth to root with view attribute
		$actor->auths[$this->type] = array();
		foreach ( $this->auth_list as $auth_name )
		{
			$actor->auths[$this->type][$auth_name] = array();
		}

		// same node, grant lesser auths to the actor
		$sql = 'SELECT obj_id, auth_type, auth_name
					FROM ' . $db->table('groups_auths') . '
					WHERE auth_type = ' . $db->escape((string) $this->type) . '
						AND auth_name IN(' . implode(', ', array_map(array(&$db, 'escape'), $this->auth_list)) . ')
						AND group_id ' . (count($actor->group_ids) == 1 ? ' = ' . intval(key($actor->group_ids)) : ' IN(' . implode(', ', array_keys($actor->group_ids)) . ')') . '
					GROUP BY obj_id, auth_type, auth_name';
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			foreach ( $this->auth_list as $auth_name )
			{
				$actor->auths[$this->type][$auth_name][ (int) $row['obj_id'] ] = true;
				if ( $row['auth_name'] == $auth_name )
				{
					break;
				}
			}
		}
		$db->free($result);

		// parent nodes: grant the view auth if any child has any auth
		$sql = 'SELECT DISTINCT p.' . $this->field_id . '
					FROM ' . $db->table('groups_auths') . ' ga, ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
					WHERE p.' . $this->field_rid . ' <> 0
						AND n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' AND p.' . $this->field_rid . '
						AND n.' . $this->field_id . ' = ga.obj_id
						AND ga.auth_type = ' . $db->escape($this->type) . '
						AND ga.auth_name IN(' . implode(', ', array_map(array(&$db, 'escape'), $this->auth_list)) . ')
						AND ga.group_id ' . (count($actor->group_ids) == 1 ? ' = ' . intval(key($actor->group_ids)) : ' IN(' . implode(', ', array_keys($actor->group_ids)) . ')');
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$actor->auths[$this->type]['view'][ (int) $row[$this->field_id] ] = true;
		}
		$db->free($result);
	}

	function get_reattach($moved_id, $after_id, $parent_id, $at_end=false)
	{
		return ($fields = parent::get_reattach($moved_id, $after_id, $parent_id, $at_end)) && $this->reattach_allowed($moved_id, $fields) ? $fields : false;
	}

	function reattach_allowed($moved_id, $fields)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// check auth: we must be able to manage the both parents, and be able to manage all childs of the moved id if the parents are not the same
		$ok = $this->allowed($moved_id, $this->auth_manage) && $this->allowed($this->data[$moved_id][$this->field_pid], $this->auth_manage) && (($fields[$this->field_pid] == $this->data[$moved_id][$this->field_pid]) || $this->allowed($fields[$this->field_pid], $this->auth_manage));

		// check we are authorized to manage all childs of moved_id
		if ( $ok && ($this->data[$moved_id][$this->field_pid] != $fields[$this->field_pid]) && ($this->data[$moved_id][$this->field_lid] + 1 < $this->data[$moved_id][$this->field_rid]) )
		{
			$sql = 'SELECT ' . $this->field_id . '
						FROM ' . $db->table($this->table) . '
						WHERE ' . $this->field_lid . ' BETWEEN ' . (intval($this->data[$moved_id][$this->field_lid]) + 1) . ' AND ' . intval($this->data[$moved_id][$this->field_rid]) . '
						ORDER BY ' . $this->field_lid;
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( $ok && ($row = $db->fetch($result)) )
			{
				$ok = $this->allowed($row[$this->field_id], $this->auth_manage);
			}
			$db->free($result);
		}
		return $ok;
	}

	function delete_allowed($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$ok = $this->allowed($id, $this->auth_manage) && $this->allowed($this->data[$id][$this->field_pid], $this->auth_manage);
		if ( $ok && ($this->data[$id][$this->field_lid] + 1 > $this->data[$id][$this->field_rid]) )
		{
			$sql = 'SELECT ' . $this->field_id . '
						FROM ' . $db->table($this->table) . '
						WHERE ' . $this->field_lid . ' BETWEEN ' . (intval($this->data[$id][$this->field_lid]) + 1) . ' AND ' . intval($this->data[$id][$this->field_rid]) . '
						ORDER BY ' . $this->field_lid;
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( $ok && ($row = $db->fetch($result)) )
			{
				$ok = $this->allowed($row[$this->field_id], $this->auth_manage);
			}
			$db->free($result);
		}
		return $ok;
	}

	// delete authorizations
	function delete_dependancies($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'DELETE FROM ' . $db->table('groups_auths') . '
					WHERE auth_type = ' . $db->escape($this->type) . '
						AND obj_id IN(' . $db->sub_query('
							SELECT ' . $this->field_id . '
								FROM ' . $db->table($this->table) . '
								WHERE ' . $this->field_lid . ' BETWEEN ' . intval($this->data[$id][$this->field_lid]) . ' AND ' . intval($this->data[$id][$this->field_rid]) . '
						', __LINE__, __FILE__) . ')';
		$db->query($sql, __LINE__, __FILE__);

		$sql = 'DELETE FROM ' . $db->table('users_tree') . '
					WHERE tree_type = ' . $db->escape($this->type) . '
						AND tree_id IN(' . $db->sub_query('
							SELECT ' . $this->field_id . '
								FROM ' . $db->table($this->table) . '
								WHERE ' . $this->field_lid . ' BETWEEN ' . intval($this->data[$id][$this->field_lid]) . ' AND ' . intval($this->data[$id][$this->field_rid]) . '
						', __LINE__, __FILE__) . ')';
		$db->query($sql, __LINE__, __FILE__);

		parent::delete_dependancies($id);
	}

	// static
	function hook($api_name, $action)
	{
		$api = &$GLOBALS[$api_name];
		$db = &$api->db;

		if ( !($parm = $api->hooks->get_data($action)) )
		{
			return false;
		}
		switch ( $action )
		{
			case 'user.delete':
				$user_id = (int) $parm['user_id'];
				$group_id = (int) $parm['individual_group_id'];
				$sql = 'DELETE FROM ' . $db->table('users_tree') . '
							WHERE user_id = ' . intval($user_id);
				$db->query($sql, __LINE__, __FILE__);

				if ( $group_id )
				{
					$sql = 'DELETE FROM ' . $db->table('groups_auths') . '
								WHERE group_id = ' . intval($group_id);
					$db->query($sql, __LINE__, __FILE__);
				}
			break;
		}
	}

	//
	// deal with manager auths
	//
	function copy_managers($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// check id
		if ( !$id || !isset($this->data[$id]) || !$this->data[$id][$this->field_pid] )
		{
			return false;
		}

		// get parent node auths
		$res = false;
		$fields = array();
		$count_fields = 0;
		$sql = 'SELECT group_id, auth_name
					FROM ' . $db->table('groups_auths') . '
					WHERE auth_type = ' . $db->escape((string) $this->type) . '
						AND obj_id = ' . intval($this->data[$id][$this->field_pid]);
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$fields[] = array(
				'group_id' => (int) $row['group_id'],
				'obj_id' => (int) $id,
				'auth_type' => (string) $this->type,
				'auth_name' => (string) $row['auth_name'],
			);
			$count_fields++;
			if ( $count_fields > $db->per_turn )
			{
				$res = true;
				$sql = 'INSERT INTO ' . $db->table('groups_auths') . '
							(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
				$fields = array();
				$count_fields = 0;
				$db->query($sql, __LINE__, __FILE__);
				unset($sql);
			}
		}
		$db->free($result);

		if ( $fields )
		{
			$res = true;
			$sql = 'INSERT INTO ' . $db->table('groups_auths') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$fields = array();
			$count_fields = 0;
			$db->query($sql, __LINE__, __FILE__);
			unset($sql);
		}
		return $res;
	}

	function read_opening_all()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;

		$this->data = $this->_root();

		// return if the actor is not allowed to any node
		$this->allowed(0);
		if ( !$actor->is_admin && (!isset($actor->auths[$this->type]) || !$actor->auths[$this->type]) )
		{
			return true;
		}

		// get auth list & ids
		if ( !$actor->is_admin )
		{
			$auth_ids = array();
			$auth_list = array();
			if ( isset($actor->auths[$this->type]) && $actor->auths[$this->type] )
			{
				$found = false;
				foreach ( $this->auth_list as $auth_name )
				{
					if ( !($found = $found || ($auth_name == $this->minimal_auth)) )
					{
						continue;
					}
					$auth_list[] = $auth_name;
					if ( isset($actor->auths[$this->type][$auth_name]) && $actor->auths[$this->type][$auth_name] )
					{
						$auth_ids = array_merge($auth_ids, array_keys($actor->auths[$this->type][$auth_name]));
					}
				}
				$auth_ids = $auth_ids ? array_keys(array_flip($auth_ids)) : array();
			}
			if ( !$auth_ids )
			{
				$this->data = false;
				return false;
			}
		}

		// all the viewable nodes, eg with any auth
		if ( $actor->is_admin )
		{
			$sql = 'SELECT *
						FROM ' . $db->table($this->table) . '
						WHERE ' . $this->field_rid . ' > 0
						ORDER BY ' . $this->field_lid;
		}
		else
		{
			$sql = 'SELECT p.*
						FROM ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
						WHERE n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' AND p.' . $this->field_rid . '
							AND n.' . $this->field_id . ' IN(' . implode(', ', $auth_ids) . ')
							AND p.' . $this->field_rid . ' > 0
						ORDER BY p.' . $this->field_lid;
			unset($auth_ids);
		}
		$result = $db->query($sql, __LINE__, __FILE__);
		unset($sql);
		while ( ($row = $db->fetch($result)) )
		{
			$this->data[ (int) $row[$this->field_id] ] = $row;
		}
		$db->free($result);
		return true;
	}

	// retrieve opened branch
	function read_open($id=false)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;
		$session = &$api->session;

		$this->data = array();
		if ( ($id !== false) && ($id !== 0) && !intval($id) )
		{
			return false;
		}

		// get auths
		$this->allowed(0);

		// get left & right ids, and verify we are allowed to the node
		$lid = $rid = 0;
		if ( $id )
		{
			// any auth will do
			if ( !$this->read_item($id) || !$this->allowed($id) )
			{
				return false;
			}
			$lid = (int) $this->data[$id][$this->field_lid];
			$rid = (int) $this->data[$id][$this->field_rid];
		}
		$this->data = $id ? array() : $this->_root();

		// get auth list & ids
		if ( !$actor->is_admin )
		{
			$auth_ids = array();
			$auth_list = array();
			if ( isset($actor->auths[$this->type]) && $actor->auths[$this->type] )
			{
				$found = false;
				foreach ( $this->auth_list as $auth_name )
				{
					if ( !($found = $found || ($auth_name == $this->minimal_auth)) )
					{
						continue;
					}
					$auth_list[] = $auth_name;
					if ( isset($actor->auths[$this->type][$auth_name]) && $actor->auths[$this->type][$auth_name] )
					{
						$auth_ids = array_merge($auth_ids, array_keys($actor->auths[$this->type][$auth_name]));
					}
				}
				$auth_ids = $auth_ids ? array_keys(array_flip($auth_ids)) : array();
			}
			if ( !$auth_ids )
			{
				$this->data = false;
				return false;
			}

			// grant view auths to parents and add them to $auth_ids
			$sql = 'SELECT p.' . $this->field_id . '
						FROM ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
						WHERE n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' + 1 AND p.' . $this->field_rid . '
							AND n.' . $this->field_id . ' IN(' . implode(', ', $auth_ids) . ')
						GROUP BY p.' . $this->field_id;
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				$auth_ids[] = (int) $row[$this->field_id];
			}
			$db->free($result);
		}

		// all the viewable & open tree
		$leaf_nodes = array();
		$sql = $this->_read_open_sql($id, $lid, $rid, $auth_ids);
		$result = $db->query($sql, __LINE__, __FILE__);
		unset($sql);
		while ( ($row = $db->fetch($result)) )
		{
			if ( $actor->is_admin && (($row[$this->field_lid] + 1)  > $row[$this->field_rid]) )
			{
				$row['_closed'] = true;
			}
			else
			{
				if ( intval($row[$this->field_pid]) && isset($leaf_nodes[ (int) $row[$this->field_pid] ]) )
				{
					unset($leaf_nodes[ (int) $row[$this->field_pid] ]);
				}
				if ( ($row[$this->field_lid] + 1)  < $row[$this->field_rid] )
				{
					$leaf_nodes[ (int) $row[$this->field_id] ] = true;
				}
			}
			$this->data[ (int) $row[$this->field_id] ] = $row;
		}
		$db->free($result);

		// check leaf nodes for authorizations
		if ( $leaf_nodes )
		{
			$sql = 'SELECT p.' . $this->field_id . '
						FROM ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
						WHERE p.' . $this->field_id . ' IN(' . implode(', ', array_keys($leaf_nodes)) . ')
							AND n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' + 1 AND p.' . $this->field_rid . ($actor->is_admin ? '' : '
							AND n.' . $this->field_id . ' IN(' . implode(', ', $auth_ids) . ')') . '
						GROUP BY p.' . $this->field_id;
			unset($leaf_nodes);
			unset($auth_ids);
			$result = $db->query($sql, __LINE__, __FILE__);
			unset($sql);
			while ( ($row = $db->fetch($result)) )
			{
				if ( !isset($this->data[ (int) $row[$this->field_id] ]['_closed']) )
				{
					$this->data[ (int) $row[$this->field_id] ]['_closed'] = true;
				}
			}
			$db->free($result);
		}
		return true;
	}

	function _read_open_sql($id, $lid, $rid, $auth_ids)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;
		$session = &$api->session;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_rid . ' > 0' . (!$id ? '' : '
						AND ' . $this->field_lid . ' BETWEEN ' . intval($lid) . ' AND ' . intval($rid)) . '
						AND ' . $this->field_id . ' IN(' . $db->sub_query('
							SELECT DISTINCT p.' . $this->field_id . '
								FROM ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
									LEFT JOIN ' . $db->table('users_tree') . ' ut
										ON ut.user_id = ' . ($actor->data ? intval($actor->data['user_id']) : 0) . '
											AND ut.session_id = ' . ($actor->data ? $db->escape('') : $db->escape($session->id)) . '
											AND ut.tree_type = ' . $db->escape($this->type) . '
											AND ut.tree_id = n.' . $this->field_pid . '
								WHERE n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' AND p.' . $this->field_rid . '
									AND (ut.tree_id IS NOT NULL OR p.' . $this->field_pid . ' = 0)' . ($actor->is_admin ? '' : '
									AND n.' . $this->field_id . ' IN(' . implode(', ', array_map('intval', $auth_ids)) . ')') . '
						', __LINE__, __FILE__) . ')
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	// read content
	function read_content($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$actor = &$api->user;

		// read the item
		if ( $id )
		{
			$this->read_item($id);
		}
		else
		{
			$this->data = $this->_root();
		}
		if ( !isset($this->data[$id]) )
		{
			return false;
		}

		// any auth will do
		if ( !$this->allowed($id, $this->auth_list) )
		{
			return false;
		}
		$lid = $this->data[$id][$this->field_lid];
		$current = $this->data[$id];
		$this->data = $this->_root();

		// get parents
		if ( $id && $current[$this->field_pid] )
		{
			$sql = $this->_read_content_sql_parent($id, $lid, $rid);
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				$row['_closed'] = false;
				$this->data[ (int) $row[$this->field_id] ] = $row;
			}
			$db->free($result);
		}

		// re-add the id
		if ( $id )
		{
			$current['_closed'] = false;
			$this->data[$id] = $current;
		}

		// actor is a main admin: get all direct children plus ancestors
		if ( $actor->is_admin )
		{
			// get direct children
			$sql = $this->_read_content_sql_direct_children($id, $lid, $rid);
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				if ( $row[$this->field_lid + 1] > $row[$this->field_rid] )
				{
					$row['_closed'] = true;
				}
				$this->data[ (int) $row[$this->field_id] ] = $row;
				$this->data[$id]['_closed'] = true;
			}
			$db->free($result);
			return true;
		}

		// actor is not a main admin, so get ids authorized with the minimal auth requirement
		$auth_ids = array();
		$auth_list = array();
		if ( isset($actor->auths[$this->type]) && $actor->auths[$this->type] )
		{
			$found = false;
			foreach ( $this->auth_list as $auth_name )
			{
				if ( !($found = $found || ($auth_name == $this->minimal_auth)) )
				{
					continue;
				}
				$auth_list[] = $auth_name;
				if ( isset($actor->auths[$this->type][$auth_name]) && $actor->auths[$this->type][$auth_name] )
				{
					$auth_ids = array_merge($auth_ids, array_keys($actor->auths[$this->type][$auth_name]));
				}
			}
			$auth_ids = $auth_ids ? array_keys(array_flip($auth_ids)) : array();
		}
		if ( !$auth_ids )
		{
			$this->data = false;
			return false;
		}

		// check if we have authorized children
		$ids = array();
		$sql = 'SELECT p.' . $this->field_id . ', COUNT(n.' . $this->field_id . ') AS count_node_id
					FROM ' . $db->table($this->table) . ' p, ' . $db->table($this->table) . ' n
					WHERE p.' . $this->field_pid . ' = ' . intval($id) . '
						AND p.' . $this->field_rid . ' > 0
						AND n.' . $this->field_lid . ' BETWEEN p.' . $this->field_lid . ' AND p.' . $this->field_rid . '
						AND n.' . $this->field_id . ' IN(' . implode(', ', $auth_ids) . ')
					GROUP BY p.' . $this->field_id;
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$ids[ (int) $row[$this->field_id] ] = $row['count_node_id'] ? true : false;
		}
		$db->free($result);

		if ( !$ids && !$this->allowed($id, $auth_list) )
		{
			$this->data = false;
			return false;
		}

		// now get data
		if ( $ids )
		{
			$sql = $this->_read_content_sql_authorized_children($id, $lid, $rid, $ids);
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				$row['_closed'] = $ids[ (int) $row[$this->field_id] ] ? true : false;
				$this->data[ (int) $row[$this->field_id] ] = $row;
			}
			$db->free($result);
		}

		if ( !isset($this->data[$id]) )
		{
			$this->data = false;
			return false;
		}
		return true;
	}

	function _read_content_sql_parent($id, $lid, $rid)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_lid . ' < ' . intval($lid) . '
						AND ' . $this->field_rid . ' > ' . intval($lid) . '
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	function _read_content_sql_direct_children($id, $lid, $rid)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_pid . ' = ' . intval($id) . '
						AND ' . $this->field_rid . ' > 0
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	function _read_content_sql_authorized_children($id, $lid, $rid, $ids)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_id . ' IN(' . implode(', ', array_keys($ids)) . ')
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	// read ancestors list for breadscrumb
	function read_ancestors($id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !$id || !$this->read_item($id) || !$this->allowed($id, $this->auth_list) )
		{
			$this->data = $this->_root();
			return false;
		}
		$lid = (int) $this->data[$id][$this->field_lid];
		$rid = (int) $this->data[$id][$this->field_rid];

		$this->data = $this->_root();
		$sql = $this->_read_ancestors_sql($id, $lid, $rid);
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$this->data[ (int) $row[$this->field_id] ] = $row;
		}
		$db->free($result);

		return true;
	}

	function _read_ancestors_sql($id, $lid, $rid)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sql = 'SELECT *
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_lid . ' <= ' . intval($lid) . ' AND ' . $this->field_rid . ' >= ' . intval($rid) . '
						AND ' . $this->field_rid . ' > 0
					ORDER BY ' . $this->field_lid;
		return $sql;
	}

	// open/close a branch
	function open_close_branch($id, $open)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$user = &$api->user;
		$session = &$api->session;

		// no id or id not found: end there
		if ( !$id || !$this->read_item($id) )
		{
			return false;
		}

		// get lid/rid
		$lid = (int) $this->data[$id][$this->field_lid];
		$rid = (int) $this->data[$id][$this->field_rid];

		// get ancestors
		$parent_ids = array($id);
		$sql = 'SELECT ' . $this->field_id . '
					FROM ' . $db->table($this->table) . '
					WHERE ' . $this->field_lid . ' < ' . $lid . ' AND ' . $this->field_rid . ' > ' . $lid;
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$parent_ids[] = (int) $row[$this->field_id];
		}
		$db->free($result);

		// delete this item plus its ancestors (plus its children opened if we close it)
		$sql = 'DELETE FROM ' . $db->table('users_tree') . '
					WHERE user_id = ' . ($user->data ? intval($user->data['user_id']) : 0) . '
						AND session_id = ' . ($user->data ? $db->escape('') : $db->escape($session->id)) . '
						AND tree_type = ' . $db->escape($this->type) . '
						AND ' . ($rid <= $lid + 1 ? '' : '(
							') . 'tree_id ' . (count($parent_ids) == 1 ? ' = ' . intval($parent_ids[0]) : ' IN(' . implode(', ', $parent_ids) . ')') . ($rid <= $lid + 1 ? '' : '
							OR tree_id IN(' . $db->sub_query('
								SELECT ' . $this->field_id . '
									FROM ' . $db->table($this->table) . '
									WHERE ' . $this->field_lid . ' BETWEEN ' . intval($lid + 1) . ' AND ' . intval($rid) . '
								', __LINE__, __FILE__) . ')
						)');
		$db->query($sql, __LINE__, __FILE__);
		unset($sql);

		// we except the id in the re-insertion if we close it or if it has no children
		$except_id = !$open || ($rid == $lid + 1) ? $id : false;

		// re-insert ancestors plus this item id
		$fields = array();
		foreach ( $parent_ids as $tree_id )
		{
			if ( $tree_id !== $except_id )
			{
				$fields[] = array(
					'user_id' => $user->data ? intval($user->data['user_id']) : 0,
					'session_id' => $user->data ? '' : (string) $session->id,
					'tree_type' => $this->type,
					'tree_id' => (int) $tree_id,
				);
			}
		}
		if ( $fields )
		{
			$sql = 'INSERT INTO ' . $db->table('users_tree') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}
}

?>