<?php
//
//	file: sys/sys.api.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/06/2007
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

// this is our main object, but without the page handling
class sys_api extends sys_stdclass
{
	var $start;
	var $local_db;
	var $local_session;
	var $db;
	var $session;

	function __construct()
	{
		$this->start = sys::microtime();

		parent::__construct();
		$this->local_db = false;
		$this->local_session = false;
		$this->db = false;
		$this->session = false;
	}

	function __destruct()
	{
		if ( !isset($this->api_name) )
		{
			return;
		}

		if ( isset($this->session) )
		{
			if ( $this->local_session )
			{
				sys::kill($this->session);
			}
			unset($this->session);
		}
		if ( isset($this->db) )
		{
			if ( $this->local_db )
			{
				sys::kill($this->db);
			}
			unset($this->db);
		}
		unset($this->local_session);
		unset($this->local_db);
		unset($this->start);
		parent::__destruct();
	}

	function set($api_name, $root, $requester)
	{
		parent::set($api_name);

		$new = sys::factory($this->api_name, $root, $requester);
		$sys = &$GLOBALS[SYS];

		// api settings
		$this->ini_set();

		// this api may define a specific context, so only start the context now if new
		if ( $new )
		{
			$sys->set_context($this->start);
		}

		// open the database
		$this->db_start();

		// open session
		$this->session_start();

		// add some tpl function
		$this->set_tpl_functions();
	}

	function ini_set()
	{
		$sys = &$GLOBALS[SYS];
		$sys->ini_load('SYS/sys.api.ini');
	}

	function db_start()
	{
		$sys = &$GLOBALS[SYS];

		// db config filename
		if ( !($filename = $sys->ini_get('db.config', 'file')) )
		{
			return false;
		}

		// read the config file
		$dbms = $dbhost = $dbname = $dbuser = $dbpasswd = $dbpersistency = $dbprefix = $table_prefix = $internal_prefix = false;
		include($filename);
		$dbprefix = $table_prefix === false ? $dbprefix : $table_prefix;
		unset($table_prefix);
		if ( ($dbms === false) || empty($dbms) || ($dbhost === false) || ($dbuser === false) || ($dbpasswd === false) || ($dbprefix === false) )
		{
			return false;
		}

		// we may have already the db object
		if ( ($this->local_db = !isset($this->db) || ($this->db === false)) )
		{
			// get the available db layers
			if ( !($class = $sys->ini_get('db', 'class')) )
			{
				trigger_error('err_sys_db_layer_not_found', E_USER_ERROR);
			}
			if ( !($class = call_user_func(array($class, 'get_layer'), $dbms)) )
			{
				trigger_error('err_sys_db_not_supported', E_USER_ERROR);
			}
			$this->db = new $class();
			$this->db->set($this->api_name);
		}

		// try to connect the database
		if ( isset($this->db) && is_object($this->db) && !$this->db->open($dbprefix, $internal_prefix, $dbhost, $dbuser, $dbpasswd, $dbname, $dbpersistency) )
		{
			if ( $this->local_db )
			{
				sys::kill($this->db);
			}
			// nb.: {unset($this->db); + $this->db = false;} won't destroy $existing_object if we used {$this->db = &$existing_object;}
			// It only destroy the alias. This is a bit strange behaviour, but a very convenient one for our purpose :).
			unset($this->db);
			$this->db = false;
			trigger_error('err_sys_db_not_connected', E_USER_ERROR);
		}
		return true;
	}

	// open a session
	function session_start()
	{
		$sys = &$GLOBALS[SYS];

		// open session
		if ( isset($this->db) && is_object($this->db) && ($this->local_session = !isset($this->session) || !is_object($this->session)) )
		{
			if ( ($class = $sys->ini_get('session', 'class')) )
			{
				$this->session = new $class();
				$this->session->set($this->api_name);
			}
		}
		if ( isset($this->session) && is_object($this->session) )
		{
			$this->session->open();
			return true;
		}
		return false;
	}

	// register tpl functions
	function set_tpl_functions()
	{
		$sys = &$GLOBALS[SYS];

		if ( !isset($sys->tpl) || !is_object($sys->tpl) )
		{
			return false;
		}
		if ( !isset($sys->tpl->funcs['url']) )
		{
			$sys->tpl->register_function('url', array(&$this, 'url_straight'));
			$sys->tpl->register_function('url_noamp', array(&$this, 'url_straight_js'));
		}
		if ( !isset($sys->tpl->funcs['shorten']) )
		{
			$sys->tpl->register_function('shorten', array(&$this, 'shorten'));
		}
		return true;
	}

	// build an url with sid
	function url($script='', $parms=false, $no_ampersand=false, $no_sid=false)
	{
		$sys = &$GLOBALS[SYS];
		if ( is_array($script) )
		{
			$no_sid = $no_ampersand;
			$no_ampersand = $parms;
			$parms = $script;
			$script = '';
		}
		if ( empty($script) )
		{
			$script = $sys->requester;
		}
		if ( !$parms )
		{
			$parms = array();
		}
		if ( !$no_sid && isset($this->session) && is_object($this->session) && $this->session->id && !in_array($this->session->channel, array('_IP', '_COOKIE')) )
		{
			$parms += array($this->session->sid_name => $this->session->id);
		}
		if ( $parms && ($parms = array_values(array_map(create_function('$key, $value', 'return empty($value) ? \'\' : $key . \'=\' . $value;'), array_keys($parms), array_values($parms)))) )
		{
			foreach ( $parms as $idx => $parm )
			{
				if ( empty($parm) )
				{
					unset($parms[$idx]);
				}
			}
		}
		return preg_replace('#^(\.\/)+#', '', $sys->root) . $script . $sys->ext . ($parms ? '?' . implode($no_ampersand ? '&' : '&amp;', $parms) : '');
	}

	function url_straight()
	{
		$args = func_get_args();
		if ( !($count_args = count($args)) || ($count_args % 2) )
		{
			return $this->url();
		}
		$parms = array();
		for ( $i = 0; $i < $count_args; $i += 2 )
		{
			$parms[ $args[$i] ] = $args[$i + 1];
		}
		return $this->url($parms);
	}

	function url_straight_js()
	{
		$args = func_get_args();
		if ( !($count_args = count($args)) || ($count_args % 2) )
		{
			return $this->url();
		}
		$parms = array();
		for ( $i = 0; $i < $count_args; $i += 2 )
		{
			$parms[ $args[$i] ] = $args[$i + 1];
		}
		return $this->url($parms, true);
	}

	function shorten($str, $length=false, $txt=false)
	{
		$size = $txt ? 3 : 1;
		$length = $length && intval($length) ? max(1, intval($length) - $size) : 30 - $size;
		return sys_string::strlen($str) > $length ? sys_string::substr($str, 0, $length) . ($txt ? '...' : '&hellip;') : $str;
	}
}

// this is the usual class to inherit, with the page handling
class sys_page extends sys_api
{
	var $rsc;

	function __construct()
	{
		parent::__construct();
		$this->rsc = array();
	}

	function __destruct()
	{
		unset($this->rsc);
		parent::__destruct();
	}

	// add uri ressources to page
	// - entry:
	//		o $name: ressource name: will be used as tpl switch by page_header
	//		o $data: flat value or single structure, or array of one of the both
	// nb.: in case $data is a structure, it must be a ready to send to tpl once, eg array('VARNAME1' => $value1, 'VARNAME2' => $value2, ...)
	function set_resource($name, $data)
	{
		if ( empty($name) || empty($data) )
		{
			return false;
		}
		if ( !isset($this->rsc[$name]) )
		{
			$this->rsc[$name] = array();
		}

		// we receive an array
		if ( is_array($data) )
		{
			// we receive a list of flat values
			$key = key($data);
			if ( $key === 0 )
			{
				foreach ( $data as $value )
				{
					$this->set_resource($name, $value);
				}
				return true;
			}

			// we receive a structure
			// php < 4.2.0 does not support in_array(array(), array()), so let's check doing a walk
			if ( !empty($this->rsc[$name]) )
			{
				foreach ( $this->rsc[$name] as $value )
				{
					$found = true;
					foreach ( $data as $key => $val )
					{
						if ( !($found = isset($value[$key]) && ($value[$key] == $val)) )
						{
							break;
						}
					}
					if ( $found )
					{
						return false;
					}
				}
			}
			$this->rsc[$name][] = $data;
			return true;
		}

		// we receive a flat value
		if ( empty($this->rsc[$name]) || !in_array($data, $this->rsc[$name]) )
		{
			$this->rsc[$name][] = $data;
			return true;
		}
		return false;
	}

	// display methods
	function page_header()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;
		$error = &$sys->error;

		// headers and script url
		$tpl->send_headers();
		$tpl->add(array(
			'U_REQUESTER' => $this->url($sys->requester),
		));

		// ressources (meta, jscripts, ...)
		if ( $this->rsc )
		{
			$rsc_names = array_keys($this->rsc);
			foreach ( $rsc_names as $rsc_name )
			{
				foreach ( $this->rsc[$rsc_name] as $rsc_data )
				{
					$tpl->add($rsc_name, is_array($rsc_data) ? $rsc_data : array('VALUE' => $rsc_data));
				}
			}
		}

		// warnings
		$error->send_warnings();
	}

	function page_footer()
	{
	}

	// display the page
	function display($tpl_name)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( $tpl_name && ($tpl_name !== true) )
		{
			$this->page_header();
			$tpl->parse($tpl_name);
			$this->page_footer();
		}
		return true;
	}

	// send the message and redirect (delay is the delay prior redirecting):
	// feedback($msg, array(txt1 => url1, txt2 => url2), ..., delay)
	function feedback($msg, $links_back=false, $delay=false, $tpl_name=false)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		// check links back
		if ( !$links_back )
		{
			$links_back = array();
		}
		if ( !$tpl_name )
		{
			$tpl_name = $sys->ini_get('tpl.file.feedback');
		}

		// add the "back to index" link
		$links_back['backto_index'] = $this->url($sys->requester);

		// default redirect delay
		if ( $delay === false )
		{
			$delay = $sys->ini_get('feedback.time');
		}
		if ( $delay === false )
		{
			$delay = 5;
		}
		if ( ($delay === 0) && (!isset($sys->rstats) || !is_object($sys->rstats) || !$sys->rstats->debug) )
		{
			reset($links_back);
			list(,$url) = each($links_back);
			$this->redirect($url);
			exit;
		}

		// send message to tpl
		$tpl->add(array(
			'SCRIPT_TITLE' => 'sys_information',
			'MESSAGE' => $msg,
		));
		foreach ( $links_back as $title => $url )
		{
			$tpl->add('links', array(
				'L_BACK' => $title,
				'U_BACK' => $url,
			));
		}
		// if we are debugging, don't redirect automaticaly
		if ( !isset($sys->rstats) || !is_object($sys->rstats) || !$sys->rstats->debug )
		{
			@reset($links_back);
			$this->set_resource('meta', array(
				'HTTP_EQUIV' => 'refresh',
				'CONTENT' => $delay . '; url=' . current($links_back),
			));
			header('Refresh: ' . $delay . '; url=' . str_replace('&amp;', '&', current($links_back)));
		}
		$this->display($tpl_name);
		exit;
	}

	function redirect($url)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( $this->db && is_object($this->db) )
		{
			$this->db->close();
		}
		if ( strstr(urldecode($url), "\n") || strstr(urldecode($url), "\r") || strstr(urldecode($url), ';url') )
		{
			trigger_error('Tried to redirect to potentially insecure url.', E_USER_ERROR);
		}

		$url = preg_replace('#^/*(.*?)/*$#', '$1', trim(preg_replace('#^\./#', '', $url)));
		$url = explode('#', $url);
		$fragment = isset($url[1]) ? '#' . $url[1] : '';
		$url = $url[0];
		if ( !preg_match('#^(ht|f)tp(s?)\://#i', $url) )
		{
			if ( $sys->session->id && !preg_match('#(\?|&amp;)sid=#', $url) )
			{
				$url .= (strpos(' ' . $url, '?') ? '&amp;' : '?') . 'sid=' . $sys->session->id;
			}
		}
		$url .= $fragment;
		$url_ampersand = $url;
		$url = str_replace('&amp;', '&', $url);

		// Send a page for some server that does not accept uri's as redirection links through header() (inspired by phpBB)
		if ( ($server_software = getenv('SERVER_SOFTWARE')) && preg_match('#Microsoft|WebSTAR|Xitami#i', $server_software) )
		{
			$this->set_resource('meta', array(
				'HTTP_EQUIV' => 'refresh',
				'CONTENT' => '0; url=' . current($url_ampersand),
			));
			$tpl->add(array(
				'REDIRECT_URI' => $url,
			));

			header('Refresh: 0; URL=' . $url);
			$this->display('page.redirect.exception');
		}
		// Behave as per HTTP/1.1 spec for others
		else
		{
			header('Location: ' . $url);
		}
		exit;
	}
}

?>