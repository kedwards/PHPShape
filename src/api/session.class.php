<?php
//
//	file: inc/session.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 06/08/2005
//	version: 0.0.2 - 19/11/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class session extends sys_stdclass
{
	var $id;
	var $ip;
	var $agent;
	var $time;
	var $start;
	var $data;
	var $channel;
	var $created;
	var $updated;
	var $deleted;

	var $sid_name;
	var $max_length;
	var $ip_length;

	function __construct()
	{
		parent::__construct();
		$this->id = false;
		$this->ip = false;
		$this->agent = false;
		$this->time = false;
		$this->start = false;
		$this->data = false;
		$this->channel = false;
		$this->created = false;
		$this->updated = false;
		$this->deleted = false;

		// constants
		$this->sid_name = false;
		$this->max_length = false;
		$this->ip_length = false;
	}

	function __destruct()
	{
		unset($this->max_length);
		unset($this->sid_name);
		unset($this->ip_length);

		unset($this->deleted);
		unset($this->updated);
		unset($this->created);
		unset($this->channel);
		unset($this->data);
		unset($this->start);
		unset($this->time);
		unset($this->agent);
		unset($this->ip);
		unset($this->id);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		// constants
		$this->sid_name = $sys->ini_get('session.sid_name');
		$this->max_length = $sys->ini_get('session.max_length');
		$this->ip_length = $sys->ini_get('session.iplength');
	}

	function open()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$db = &$api->db;

		$now = time();

		// get ip
		$this->id = $this->get_id();
		$this->ip = ($ip = implode('', array_map(create_function('$digit', 'return sprintf(\'%02x\', max(0, min(255, (int) $digit)));'), array_slice(explode('.', $io->read('REMOTE_ADDR', '', '_SERVER') . '.0.0.0.0'), 0, 4)))) && ($ip != '00000000') ? $ip : false;
		$this->agent = $io->read('HTTP_USER_AGENT', '', '_SERVER');
		$this->time = $now;
		$this->start = $now;
		$this->data = array();

		// we have an id: check if it matches a session
		$data = false;
		if ( $this->id )
		{
			$sql = 'SELECT *
						FROM ' . $db->table('sessions') . '
						WHERE session_id = ' . $db->escape((string) $this->id);
			$result = $db->query($sql, __LINE__, __FILE__);
			$data = ($row = $db->fetch($result)) ? $row : false;
			$db->free($result);
		}
		$data['session_data'] = isset($data['session_data']) && $data['session_data'] ? unserialize($data['session_data']) : array();

		// check session ip against the current ip & verify the session duration
		if ( $data && (substr($data['session_ip'], 0, $this->ip_length) == substr($this->ip, 0, $this->ip_length)) && (($data['session_agent'] === md5($this->agent)) || (($this->agent === 'Shockwave Flash') && isset($data['session_data']['flashseed']) && ($data['session_data']['flashseed'] === $io->read('nsd', '', '_POST')))) && (($this->time - intval($data['session_time'])) < $this->max_length) )
		{
			if ( !$this->id )
			{
				$this->channel = '_IP';
			}
			// recover data
			$this->id = $data['session_id'];
			$this->start = (int) $data['session_start'];
			$this->data = $data['session_data'];

			// check if the ipv8 changed or if the session is older than 1 minute. If so, update
			if ( ($data['session_ip'] != $this->ip) || ($this->time - $data['session_time'] > 60) )
			{
				$this->update();
			}
			return true;
		}

		// no ip, or no session id, or no existing session, or outdated, or relative to another ip
		$this->id = false;
		$this->start = false;
		$this->data = false;
		return false;
	}

	function create($data=false)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// clear some old data
		$this->id = false;
		$this->close();
		if ( !$this->ip )
		{
			return false;
		}

		// add a flash seed
		$data['flashseed'] = $this->generate_id();

		// prepare data
		$this->id = $this->generate_id();
		$this->time = time();
		$this->start = $this->time;
		$this->data = $data;

		// create a new session
		$fields = array(
			'session_id' => (string) $this->id,
			'session_ip' => (string) $this->ip,
			'session_agent' => (string) md5($this->agent),
			'session_start' => $this->start,
			'session_time' => $this->time,
			'session_data' => empty($data) ? '' : serialize($data),
		);
		$sql = 'INSERT INTO ' . $db->table('sessions') . '
					(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
		if ( !$db->query($sql, __LINE__, __FILE__, false) )
		{
			// the session_id already exists (should not happen): try another
			$this->id = $this->generate_id();
			$fields['session_id'] = (string) $this->id;
			$sql = 'INSERT INTO ' . $db->table('sessions') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			if ( !$db->query($sql, __LINE__, __FILE__, false) )
			{
				// this session_id exists too (highly improbable), consider the previous outdated, and let the message pop if fails
				unset($fields['session_id']);
				$sql = 'UPDATE ' . $db->table('sessions') . '
							SET ' . $db->fields('update', $fields) . '
							WHERE session_id = ' . $db->escape((string) $this->id);
				$db->query($sql, __LINE__, __FILE__);
			}
		}
		$this->created = true;
		return true;
	}

	function update()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$this->updated = false;
		if ( $this->id )
		{
			$fields = array(
				'session_ip' => $this->ip,
				'session_time' => $this->time,
				'session_data' => $this->data ? serialize($this->data) : '',
			);
			$sql = 'UPDATE ' . $db->table('sessions') . '
						SET ' . $db->fields('update', $fields) . '
						WHERE session_id = ' . $db->escape((string) $this->id);
			$db->query($sql, __LINE__, __FILE__);
			$this->updated = true;
		}
		return $this->updated;
	}

	function close()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$edge = time() - $this->max_length;

		// outdate the current session
		if ( $this->id )
		{
			$sql = 'UPDATE ' . $db->table('sessions') . '
						SET session_time = ' . intval($edge - 1) . '
						WHERE session_id = ' . $db->escape($this->id);
			$db->query($sql, __LINE__, __FILE__);
		}

		// delete cascading if any
		$api->hooks->set_data('session.delete', $edge);
		$api->hooks->process('session.delete');
		$api->hooks->unset_data('session.delete');

		// delete outdated sessions
		$sql = 'DELETE FROM ' . $db->table('sessions') . '
					WHERE session_time < ' . intval($edge);
		$db->query($sql, __LINE__, __FILE__);

		// raz
		$this->id = false;
		$this->start = false;
		$this->data = false;
		$this->deleted = true;
	}

	function generate_id()
	{
		return md5(uniqid(mt_rand(), true));
	}

	function get_id()
	{
		$sys = &$GLOBALS[SYS];
		$io = &$sys->io;

		$cookie_name = $sys->ini_get('session.cookie_name');
		$this->channel = $cookie_name && ($id = $io->read($cookie_name . '_' . $this->sid_name, '', '_COOKIE')) ? '_COOKIE' : (($id = $io->read($this->sid_name, '')) ? '_GET' : false);
		if ( !$id || !preg_match('#^[a-z0-9]{32}$#i', $id) )
		{
			$this->channel = false;
		}
		return $this->channel ? $id : false;
	}

	function get_cookies_def()
	{
		$sys = &$GLOBALS[SYS];
		$io = &$sys->io;

		// forced cookie domain ?
		$domain = $sys->ini_get('session.cookie_domain');
		if ( $domain === false )
		{
			// try to compute a cookie domain
			$domain_split = array_slice(explode('.', sys_string::strtolower(sys_string::htmlspecialchars($io->read('SERVER_NAME', '', '_SERVER')))), -3);
			$domain = empty($domain_split) || ($domain_split[0] == 'localhost') ? '' : (count($domain_split) < 3 ? '.' : '') . implode('.', $domain_split);
		}

		// return result
		return array(
			'cookie_secure' => preg_match('#https#i', sys_string::htmlspecialchars($io->read('SERVER_PROTOCOL', '', '_SERVER'))),
			'cookie_domain' => $domain,
			'cookie_path' => '/',
		);
	}

	function setcookie($unset=false)
	{
		$sys = &$GLOBALS[SYS];

		$cookie_name = $sys->ini_get('session.cookie_name');
		$cookie_def = $this->get_cookies_def();

		// the user is loged in
		if ( $this->id && !$unset )
		{
			if ( $cookie_def['cookie_domain'] )
			{
				setcookie($cookie_name . '_' . $this->sid_name, $this->id, time() + $this->max_length, $cookie_def['cookie_path'], $cookie_def['cookie_domain'], $cookie_def['cookie_secure'], true);
			}
			else
			{
				setcookie($cookie_name . '_' . $this->sid_name, $this->id, time() + $this->max_length, $cookie_def['cookie_path']);
			}
		}
		// the user is loged out: remove the cookies
		else
		{
			if ( $cookie_def['cookie_domain'] )
			{
				setcookie($cookie_name . '_' . $this->sid_name, false, time() - 3600, $cookie_def['cookie_path'], $cookie_def['cookie_domain'], $cookie_def['cookie_secure'], true);
			}
			else
			{
				setcookie($cookie_name . '_' . $this->sid_name, false, time() - 3600, $cookie_def['cookie_path']);
			}
		}
	}
}

?>