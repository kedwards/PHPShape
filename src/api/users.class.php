<?php
//
//	file: inc/user.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 06/08/2005
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class user extends sys_stdclass
{
	var $data;
	var $group_ids;
	var $individual_group_id;
	var $is_admin;
	var $lang;

	var $last_connection;

	function __construct()
	{
		parent::__construct();
		$this->data = false;
		$this->group_ids = false;
		$this->individual_group_id = false;
		$this->is_admin = false;
		$this->last_connection = false;
		$this->lang = false;
	}

	function __destruct()
	{
		unset($this->lang);
		unset($this->last_connection);
		unset($this->is_admin);
		unset($this->individual_group_id);
		unset($this->group_ids);
		unset($this->data);
		parent::__destruct();
	}

	function reset()
	{
		$this->data = false;
		$this->group_ids = false;
		$this->is_admin = false;
		$this->last_connection = false;
	}

	function read($user_id)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$this->data = false;
		if ( intval($user_id) )
		{
			$sql = 'SELECT *
						FROM ' . $db->table('users') . '
						WHERE user_id = ' . intval($user_id);
			$result = $db->query($sql, __LINE__, __FILE__);
			$this->data = ($row = $db->fetch($result)) ? $row : false;
			$db->free($result);
		}
		return $this->data !== false;
	}

	function update($fields=false)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !$this->data )
		{
			return false;
		}
		if ( !$fields )
		{
			$fields = array();
		}

		// reset login attempts
		if ( !isset($fields['user_login_tries']) )
		{
			$fields['user_login_tries'] = 0;
		}
		if ( !isset($fields['user_login_tries_last']) )
		{
			$fields['user_login_tries_last'] = 0;
		}
		$fields['user_login_tries'] = (int) $fields['user_login_tries'];
		$fields['user_login_tries_last'] = (int) $fields['user_login_tries_last'];

		// force the user ident
		if ( isset($fields['user_name']) )
		{
			$fields['user_ident'] = $sys->utf8->get_identifier($fields['user_name']);
		}

		// force the user realname ident
		if ( isset($fields['user_realname']) )
		{
			$fields['user_realname_ident'] = $sys->utf8->get_identifier($fields['user_realname']);
		}

		// update buffer
		if ( !$this->data['user_regdate'] )
		{
			$fields['user_regdate'] = time();
		}

		// force numerics
		if ( isset($fields['user_regdate']) )
		{
			$fields['user_regdate'] = (int) $fields['user_regdate'];
		}
		if ( isset($fields['user_timeshift_disable']) )
		{
			$fields['user_timeshift_disable'] = (int) $fields['user_timeshift_disable'];
		}
		if ( isset($fields['user_disabled']) )
		{
			$fields['user_disabled'] = (int) $fields['user_disabled'];
		}

		// maybe more to do ?
		$api->hooks->set_data('user.update', $fields);
		$api->hooks->process('user.update');
		$fields = $api->hooks->get_data('user.update');
		$api->hooks->unset_data('user.update');

		// store this
		$this->data = array_merge($this->data, $fields);

		// remove user_id if any
		if ( isset($fields['user_id']) )
		{
			unset($fields['user_id']);
		}

		// update database
		$sql = 'UPDATE ' . $db->table('users') . '
					SET ' . $db->fields('update', $fields) . '
					WHERE user_id = ' . intval($this->data['user_id']);
		$db->query($sql, __LINE__, __FILE__);

		// check if the user is grouped
		$sql = 'SELECT group_id
					FROM ' . $db->table('users_groups') . '
					WHERE user_id = ' . intval($this->data['user_id']) . '
					LIMIT 1';
		$result = $db->query($sql, __LINE__, __FILE__);
		$exists = $db->fetch($result) ? true : false;
		$db->free($result);
		if ( !$exists )
		{
			// create membership to default users group
			$dft_group = SYS_GROUP_GUESTS;
			$fields = array(
				'user_id' => $this->data['user_id'],
				'group_id' => (int) $dft_group,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}

	function insert($fields=false, $group_id=false)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$fields = $fields ? $fields : $this->data;
		if ( !$fields )
		{
			return false;
		}
		if ( isset($fields['user_id']) )
		{
			unset($fields['user_id']);
		}
		$fields['user_login_tries'] = 0;
		$fields['user_login_tries_last'] = 0;
		$fields['user_ident'] = $sys->utf8->get_identifier($fields['user_name']);
		$fields['user_realname_ident'] = $sys->utf8->get_identifier($fields['user_realname']);
		$fields['user_regdate'] = time();

		// maybe more to do ?
		$api->hooks->set_data('user.insert', $fields);
		$api->hooks->process('user.insert');
		$fields = $api->hooks->get_data('user.insert');
		$api->hooks->unset_data('user.insert');

		$sql = 'INSERT INTO ' . $db->table('users') . '
					(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
		$db->query($sql, __LINE__, __FILE__);
		$this->data = array('user_id' => $db->next_id()) + $fields;

		// create membership to default users group
		$dft_group = $group_id ? $group_id : SYS_GROUP_GUESTS;
		$fields = array(
			'user_id' => $this->data['user_id'],
			'group_id' => (int) $dft_group,
		);
		$sql = 'INSERT INTO ' . $db->table('users_groups') . '
					(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
		$db->query($sql, __LINE__, __FILE__);
		return true;
	}

	function delete()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !$this->data )
		{
			return false;
		}

		// delete dependant data
		$api->hooks->set_data('user.delete', $this->data + array('individual_group_id' => (int) $this->individual_group_id));
		$api->hooks->process('user.delete');
		$api->hooks->unset_data('user.delete');

		// delete login
		$sql = 'DELETE FROM ' . $db->table('users_logins') . '
					WHERE user_id = ' . intval($this->data['user_id']);
		$db->query($sql, __LINE__, __FILE__);

		// delete sessions
		$sql = 'DELETE FROM ' . $db->table('sessions') . '
					WHERE session_data LIKE \'%' . $db->escape('"user_id";i:' . intval($this->data['user_id']) . ';', false) . '%\'';
		$db->query($sql, __LINE__, __FILE__);

		// delete histo
		$sql = 'DELETE FROM ' . $db->table('users_histo') . '
					WHERE user_id = ' . intval($this->data['user_id']);
		$db->query($sql, __LINE__, __FILE__);

		// and finaly delete user
		$sql = 'DELETE FROM ' . $db->table('users') . '
					WHERE user_id = ' . intval($this->data['user_id']);
		$db->query($sql, __LINE__, __FILE__);

		return true;
	}

	function read_groups()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( $this->group_ids !== false )
		{
			$this->is_admin = isset($this->group_ids[SYS_GROUP_OWNERS]);
			return true;
		}

		// get all group ids the actor belongs to
		$this->group_ids = array();
		$this->individual_group_id = false;
		$this->lang = '';

		// not logged in
		if ( !$this->data )
		{
			$this->group_ids = array(SYS_GROUP_GUESTS => true);
			return false;
		}

		// get individual user group if any
		$sql = 'SELECT g.group_id, g.user_id
					FROM ' . $db->table('users_groups') . ' ug, ' . $db->table('groups') . ' g
					WHERE ug.user_id = ' . intval($this->data['user_id']) . '
						AND g.group_id = ug.group_id';
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			if ( intval($row['group_id']) )
			{
				$this->group_ids[ (int) $row['group_id'] ] = true;
				if ( intval($row['user_id']) )
				{
					$this->individual_group_id = (int) $row['group_id'];
				}
			}
		}
		$db->free($result);

		// get parent membership
		if ( $this->group_ids )
		{
			$sql = 'SELECT p.group_lid, p.group_id, p.group_lang
						FROM ' . $db->table('groups') . ' p, ' . $db->table('groups') . ' n
						WHERE n.group_lid BETWEEN p.group_lid AND p.group_rid
							AND n.group_id IN(' . implode(', ', array_keys($this->group_ids)) . ')
							AND p.group_rid <> 0
						GROUP BY p.group_id, p.group_lid, p.group_lang';
			$result = $db->query($sql, __LINE__, __FILE__);
			unset($sql);
			while ( ($row = $db->fetch($result)) )
			{
				if ( intval($row['group_id']) && !isset($this->group_ids[ (int) $row['group_id'] ]) )
				{
					$this->group_ids[ (int) $row['group_id'] ] = false;
				}
				if ( $row['group_lang'] !== '' )
				{
					$this->lang = $row['group_lang'];
				}
			}
			$db->free($result);
		}
		// create membership to default users group
		else
		{
			$dft_group = SYS_GROUP_GUESTS;
			$fields = array(
				'user_id' => (int) $this->data['user_id'],
				'group_id' => (int) $dft_group,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
			$this->group_ids[ $fields['group_id'] ] = true;
		}
		$this->is_admin = isset($this->group_ids[SYS_GROUP_OWNERS]);
		if ( $this->data['user_lang'] !== '' )
		{
			$this->lang = $this->data['user_lang'];
		}
		return true;
	}

	function auto_group()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !$this->data || !$this->data['user_id'] )
		{
			return false;
		}

		// is the user part of the guests group ?
		$is_guest = false;
		$guests_lid = $guests_rid = false;
		$sql = 'SELECT ug.group_id, p.group_lid, p.group_rid
					FROM ' . $db->table('users_groups') . ' ug, ' . $db->table('groups') . ' g, ' . $db->table('groups') . ' p
					WHERE ug.user_id = ' . intval($this->data['user_id']) . '
						AND g.group_id = ug.group_id
						AND g.group_lid BETWEEN p.group_lid AND p.group_rid
						AND p.group_id = ' . intval(SYS_GROUP_GUESTS) . '
					LIMIT 1';
		$result = $db->query($sql, __LINE__, __FILE__);
		if ( ($is_guest = ($row = $db->fetch($result)) ? true : false) )
		{
			$guests_lid = intval($row['group_lid']);
			$guests_rid = intval($row['group_rid']);
		}
		$db->free($result);

		// on activation, guests become members
		if ( $sys->ini_get('login.activation') && $is_guest )
		{
			// delete guests membership
			$sql = 'DELETE FROM ' . $db->table('users_groups') . '
						WHERE user_id = ' . intval($this->data['user_id']) . '
							AND group_id IN(' . $db->sub_query('
								SELECT group_id
									FROM ' . $db->table('groups') . '
									WHERE group_lid BETWEEN ' . intval($guests_lid) . ' AND ' . intval($guests_rid) . '
								', __LINE__, __FILE__) . ')';
			$db->query($sql, __LINE__, __FILE__);

			// add members membership
			$fields = array(
				'user_id' => (int) $this->data['user_id'],
				'group_id' => (int) SYS_GROUP_MEMBERS,
			);
			$sql = 'INSERT INTO ' . $db->table('users_groups') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}

	function last_connection()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$user = &$api->user;
		$session = &$api->session;

		if ( $this->last_connection === false )
		{
			$limits = array();
			if ( $user->data && ($user->data['user_id'] == $this->data['user_id']) )
			{
				$limits[] = 1;
			}
			$limits[] = 1;
			$sql = 'SELECT session_start
						FROM ' . $db->table('users_histo') . '
						WHERE user_id = ' . intval($this->data['user_id']) . '
							AND session_id = \'\'
						ORDER BY user_id, session_id, session_start DESC
						LIMIT ' . implode(', ', $limits);
			$result = $db->query($sql, __LINE__, __FILE__);
			$this->last_connection = ($row = $db->fetch($result)) ? (int) $row['session_start'] : 0;
			$db->free($result);
			if ( !$this->last_connection && ($user->data && ($user->data['user_id'] == $this->data['user_id'])) )
			{
				$this->last_connection = $session->start;
			}
			if ( !$this->last_connection )
			{
				$this->last_connection = 0;
			}
		}
		return $this->last_connection;
	}

	function encode_password($password)
	{
		return md5($password);
	}
}

class actor extends user
{
	var $login_id;
	var $auths;
	var $auth_types_manager;

	function __construct()
	{
		parent::__construct();
		$this->login_id = false;
		$this->auths = false;
		$this->auth_types_manager = false;
	}

	function __destruct()
	{
		unset($this->auth_types_manager);
		unset($this->auths);
		unset($this->login_id);
		parent::__destruct();
	}

	function reset()
	{
		parent::reset();
		$this->login_id = false;
		$this->auths = false;
		$this->auth_types_manager = false;
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$session = &$api->session;
		$db = &$api->db;

		if ( ($actkey = ($io->read(SYS_U_ACTKEY, '', '_GET')  && $sys->ini_get('login.activation'))) )
		{
			$session->close();
		}

		if ( !$session->data && !$actkey )
		{
			$this->autologin();
		}
		if ( !$session->id || $actkey )
		{
			$session->create();
		}

		// try to get the user
		if ( $session->data && isset($session->data['user_id']) && intval($session->data['user_id']) )
		{
			$this->read($session->data['user_id']);
		}
		if ( !$this->data )
		{
			$this->reset();
		}

		// set lang
		$sys->lang->set_lang($this->data && isset($this->data['user_lang']) && $this->data['user_lang'] ? $this->data['user_lang'] : false);

		// update login history
		$this->login_history();

		// some usefull tpl functions
		$sys->tpl->register_function('datetime', array(&$this, 'datetime'));
		$sys->tpl->register_function('date', array(&$this, 'date'));
		$sys->tpl->register_function('dhms', array(&$this, 'dhms'));
		$sys->tpl->register_function('realname', array(&$this, 'tpl_fmt_realname'));

		// get basic auths
		$this->read_groups();
		if ( $this->group_ids && !$this->is_admin )
		{
			$this->auth_types_manager = array();
			$sql = 'SELECT auth_type
						FROM ' . $db->table('groups_auths') . '
						WHERE group_id IN(' . implode(', ', array_map('intval', array_keys($this->group_ids))) . ')
							AND auth_name = ' . $db->escape('manage') . '
						GROUP BY auth_type';
			$result = $db->query($sql, __LINE__, __FILE__);
			while ( ($row = $db->fetch($result)) )
			{
				$this->auth_types_manager[ $row['auth_type'] ] = true;
			}
			$db->free($result);
			if ( !$this->auth_types_manager )
			{
				$this->auth_types_manager = false;
			}
		}
	}

	function set_lang($lang=false)
	{
		$sys = &$GLOBALS[SYS];

		if ( $lang !== false )
		{
			$sys->lang->set_lang($lang);
		}
		else if ( isset($this->data['user_lang']) && ($this->data['user_lang'] !== '') )
		{
			$sys->lang->set_lang($this->data['user_lang']);
		}
		else if ( $this->lang !== '' )
		{
			$sys->lang->set_lang($this->lang);
		}
	}

	function autologin()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$db = &$api->db;
		$session = &$api->session;

		// check if we have a cookie with autologin
		$this->login_id = false;
		if ( ($max_relog_time = $sys->ini_get('session.max_relog_time')) )
		{
			$cookie_name = $sys->ini_get('session.cookie_name');
			if ( ($data = $io->read($cookie_name . '_data', '', '_COOKIE')) && ($data = unserialize($data)) && isset($data['user_id']) && intval($data['user_id']) && isset($data['login_id']) && preg_match('#^[a-z0-9]{32}$#i', $data['login_id']) )
			{
				$sql = 'SELECT login_id, login_agent
							FROM ' . $db->table('users_logins') . '
							WHERE user_id = ' . intval($data['user_id']) . '
								AND login_time > ' . (time() - $max_relog_time);
				$result = $db->query($sql, __LINE__, __FILE__);
				$login_id = $login_agent = false;
				if ( ($row = $db->fetch($result)) )
				{
					$login_id = $row['login_id'];
					$login_agent = $row['login_agent'];
				}
				$db->free($result);
				if ( $login_id && ($data['login_id'] === $login_id) && $login_agent && (md5($session->agent) == $login_agent) )
				{
					$this->login_id = $login_id;
					$session->close();
					$session->create(array('user_id' => (int) $data['user_id']));
				}
			}
		}
	}

	function generate_autologin($set=false)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$db = &$api->db;
		$session = &$api->session;

		$this->login_id = false;
		if ( !($max_relog_time = $sys->ini_get('session.max_relog_time')) )
		{
			return false;
		}

		// delete any login id outdated, or belonging to this user
		$sql = 'DELETE FROM ' . $db->table('users_logins') . '
					WHERE ' . (intval($this->data['user_id']) ? 'user_id = ' . intval($this->data['user_id']) . '
						OR ' : '') . 'login_time <= ' . (time() - $max_relog_time);
		$db->query($sql, __LINE__, __FILE__);

		// generate a new one
		if ( $set && $session->data && intval($this->data['user_id']) )
		{
			$this->login_id = $session->generate_id();
			$fields = array(
				'user_id' => (int) $this->data['user_id'],
				'login_id' => $this->login_id,
				'login_time' => (int) $session->time,
				'login_agent' => md5($io->read('HTTP_USER_AGENT', '', '_SERVER')),
			);
			$sql = 'INSERT INTO ' . $db->table('users_logins') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
		}
		return true;
	}

	function setcookie($unset=false)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$session = &$api->session;

		if ( !($max_relog_time = $sys->ini_get('session.max_relog_time')) )
		{
			$unset = true;
		}
		$cookie_name = $sys->ini_get('session.cookie_name');
		$cookie_def = $session->get_cookies_def();
		if ( $session->id && !$unset )
		{
			if ( $cookie_def['cookie_domain'] )
			{
				setcookie($cookie_name . '_data', serialize(array('user_id' => (int) $this->data['user_id'], 'login_id' => $this->login_id)), time() + $max_relog_time, $cookie_def['cookie_path'], $cookie_def['cookie_domain'], $cookie_def['cookie_secure'], true);
			}
			else
			{
				setcookie($cookie_name . '_data', serialize(array('user_id' => (int) $this->data['user_id'], 'login_id' => $this->login_id)), time() + $max_relog_time, $cookie_def['cookie_path']);
			}
		}
		else
		{
			if ( $cookie_def['cookie_domain'] )
			{
				setcookie($cookie_name . '_data', false, time() - 3600, $cookie_def['cookie_path'], $cookie_def['cookie_domain'], $cookie_def['cookie_secure'], true);
			}
			else
			{
				setcookie($cookie_name . '_data', false, time() - 3600, $cookie_def['cookie_path']);
			}
		}
	}

	function login_history()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;
		$session = &$api->session;

		// login history
		$fields = array(
			'user_id' => $this->data && intval($this->data['user_id']) ? (int) $this->data['user_id'] : 0,
			'session_id' => $this->data && $this->data['user_id'] ? '' : (string) $session->id,
			'session_start' => (int) $session->start,
			'session_time' => (int) $session->time,
			'session_ip' => (string) $session->ip,
			'session_agent' => (string) $session->agent,
			'session_lang' => (string) $sys->lang->active,
		);
		if ( $session->created )
		{
			$sql = 'INSERT INTO ' . $db->table('users_histo') . '
							(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__, false);
		}
		if ( $session->updated )
		{
			$_user_id = $fields['user_id'];
			$_session_id = $fields['session_id'];
			$_session_start = $fields['session_start'];
			unset($fields['user_id']);
			unset($fields['session_id']);
			unset($fields['session_start']);
			$sql = 'UPDATE ' . $db->table('users_histo') . '
						SET ' . $db->fields('update', $fields) . '
						WHERE user_id = ' . $db->escape($_user_id) . '
							AND session_id = ' . $db->escape($_session_id) . '
							AND session_start = ' . $db->escape($_session_start);
			$db->query($sql, __LINE__, __FILE__);
		}
	}

	// auths
	function read_auths($auth_type)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// already readed ?
		if ( isset($this->auths[$auth_type]) )
		{
			return false;
		}
		$this->auths[$auth_type] = array();

		// we need the groups
		$this->read_groups();

		// administrator: we need no auths, we have all
		if ( $this->is_admin )
		{
			return false;
		}

		// read all auths for the types provided
		$sql = 'SELECT obj_id, auth_type, auth_name
					FROM ' . $db->table('groups_auths') . '
					WHERE auth_type = ' . $db->escape((string) $auth_type) . '
						AND group_id ' . (count($this->group_ids) == 1 ? ' = ' . intval(key($this->group_ids)) : ' IN(' . implode(', ', array_keys($this->group_ids)) . ')') . '
					GROUP BY auth_type, obj_id, auth_name';
		$result = $db->query($sql, __LINE__, __FILE__);
		while ( ($row = $db->fetch($result)) )
		{
			$this->auths[ $row['auth_type'] ][ $row['auth_name'] ][ (int) $row['obj_id'] ] = true;
		}
		$db->free($result);

		return true;
	}

	function auth($auth_type, $auth_name=false, $object_id=false)
	{
		// owners can see everything
		if ( $this->is_admin )
		{
			return true;
		}
		if ( is_array($auth_name) )
		{
			if ( $auth_name )
			{
				foreach ( $auth_name as $name )
				{
					if ( $this->auth($auth_type, $name, $object_id) )
					{
						return true;
					}
				}
			}
			return false;
		}

		// everybody can see the root, but only main admin can do other thing onto than view
		if ( !$object_id && (($auth_name === false) || ($auth_name === 'view')) )
		{
			return true;
		}

		// we may want to check the presence of a single auth, whatever the object_id
		if ( ($object_id === false) && ($auth_name !== false) )
		{
			if ( $this->auths && isset($this->auths[$auth_type]) && isset($this->auths[$auth_type][$auth_name]) && $this->auths[$auth_type][$auth_name] )
			{
				return true;
			}
		}

		// from there, we expect the auth_type to exists and to have data, and object_id to have a value
		if ( $object_id && $this->auths && isset($this->auths[$auth_type]) && $this->auths[$auth_type] )
		{
			// we don't check a specific auth, any will do for the id
			if ( $auth_name === false )
			{
				foreach ( $this->auths[$auth_type] as $name => $ids )
				{
					if ( isset($ids[$object_id]) )
					{
						return true;
					}
				}
				return false;
			}
			// we have auth name & object_id
			if ( $auth_name && isset($this->auths[$auth_type][$auth_name]) && $this->auths[$auth_type][$auth_name] && isset($this->auths[$auth_type][$auth_name][$object_id]) )
			{
				return $this->auths[$auth_type][$auth_name][$object_id];
			}
		}
		return false;
	}

	// basic display
	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;

		$tpl->add(array(
			'ACTOR_ID' => $this->data ? (int) $this->data['user_id'] : false,
			'ACTOR_REALNAME' => $this->data ? $this->data['user_realname'] : '',
			'IS_OWNER' => $this->data && $this->is_admin,
		));
		$api->hooks->process('actor.display');
	}


	// time manipulation
	function timeshift()
	{
		$sys = &$GLOBALS[SYS];
		return $this->data && ($this->data['user_timeshift'] !== '') ? intval($this->data['user_timeshift']) : intval($sys->ini_get('timeshift'));
	}

	// remove user offset & add server offset
	function sys_time($timestamp)
	{
		return call_user_func_array('gmmktime', explode(', ', date('H, i, s, m, d, Y', $timestamp - intval($this->timeshift()))));
	}

	// specific tpl functions
	function datetime($time=0, $fmt=false)
	{
		$sys = &$GLOBALS[SYS];
		$dft_fmt = $fmt ? $fmt : $sys->lang->get('time_long');
		return strtr(gmdate($dft_fmt, ($time ? $time : time()) + $this->timeshift()), $sys->lang->times);
	}

	function date($time, $fmt='', $ts=false)
	{
		$sys = &$GLOBALS[SYS];
		$long_fmt = $sys->lang->get('date_long');
		$short_fmt = $sys->lang->get('date_short');
		return $time ? strtr(gmdate(!$fmt || ($fmt == 'long') ? $long_fmt : ($fmt == 'short' ? $short_fmt : $fmt), $time + ($ts === false ? $this->timeshift() : intval($ts))), $sys->lang->times) : '';
	}

	function dhms($time)
	{
		$seconds = $time % 60;
		$time = intval($time / 60);
		$minutes = $time % 60;
		$time = intval($time / 60);
		$hours = $time % 24;
		$time = intval($time / 60);
		$days = $time;
		return $days ? sprintf('%d, %02d:%02d:%02d', $days, $hours, $minutes, $seconds) : ($hours ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) : sprintf('%d\'%02d&quot;', $minutes, $seconds));
	}

	function tpl_fmt_realname($value)
	{
		if ( $value )
		{
			$xvalue = explode(', ', $value);
			$first = array_shift($xvalue);
			array_push($xvalue, $first);
			$value = implode(' ', array_map('trim', $xvalue));
		}
		return $value;
	}
}

?>