<?php
//
//	file: sys/sys.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/06/2007
//	version: 0.0.4 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

// php minimal version:
//	- regexp /u: LINUX: php 4.1.0, WINDOWS: php 4.2.3 (with utf-8)
//	- htmlspecialchars(,, 'UTF-8'): php 4.1.0 (with utf-8)
//	- pg_fetch_row(): php 4.1.0 : (sys_db_pgsql, pg_fetch_row() without row idx)
//	- version_compare(): php 4.0.7
//  - upload: UPLOAD_ERR_OK php 4.2.0

// SYS constants define the varname of our unique object which handles system status
if ( !defined('SYS') )
{
	die('Not allowed');
}
if ( isset($GLOBALS[SYS]) )
{
	die('Conflict with varname $' . htmlspecialchars(SYS));
}

// root class: all classes are inherited from this one
class sys_stdclass
{
	var $api_name;

	function sys_stdclass()
	{
		$this->__construct();
	}

	function __construct()
	{
		$this->api_name = false;
	}

	function __destruct()
	{
		unset($this->api_name);
	}

	function set($api_name)
	{
		$this->api_name = $api_name;
	}
}

// class for SYS object which handles the system objects and properties that are unique (eg rstats, lang, ...).
class sys extends sys_stdclass
{
	var $root;
	var $requester;
	var $ext;
	var $path;
	var $ini;

	var $rstats;
	var $lang;
	var $utf8;
	var $error;
	var $io;
	var $tpl;
	var $objects;
	var $shutdown_registered;
	var $error_reporting;
	var $magic_quotes_runtime;

	// static: create the instance if missing
	function factory($api_name, $root, $requester, $path=false, $skip_server_status=false)
	{
		if ( ($first = !isset($GLOBALS[SYS])) )
		{
			// __CLASS__ is only reliable since php5: uppercase/lowercase thingy in php4.3+, not there before, and so...
			$GLOBALS[SYS] = new sys();
			$GLOBALS[SYS]->set($api_name, $root, $requester, $path, $skip_server_status);
		}
		if ( $api_name )
		{
			$sys = &$GLOBALS[SYS];
			$sys->register($api_name);
		}
		return $first;
	}

	function __construct()
	{
		parent::__construct();

		// paths
		$this->root = false; // relative path from the page displayed to the site root url (./ for the main index)
		$this->requester = false; // script path and name without the extension relative to the site root url ($this->root)
		$this->ext = false; // this file extension
		$this->path = false; // relative path from $this->root to the directory where stands this file (so $this->root . 'sys/')
		$this->ini = false; // ini values

		// system status
		$this->shutdown_registered = false;
		$this->error_reporting = false;
		$this->magic_quotes_runtime = false;

		// main objects
		$this->lang = false;
		$this->utf8 = false;
		$this->error = false;
		$this->io = false;
		$this->tpl = false;

		// registered objects
		$this->objects = false;
	}

	function __destruct()
	{
		if ( !isset($this->api_name) )
		{
			return;
		}

		// close all the registered global objects, starting with the last registered
		while ( !empty($this->objects) )
		{
			$name = array_pop($this->objects);
			if ( isset($GLOBALS[$name]) )
			{
				sys::kill($GLOBALS[$name]);
				unset($GLOBALS[$name]);
			}
		}
		$this->objects = false;

		// destroy tpl
		if ( isset($this->tpl) )
		{
			sys::kill($this->tpl);
			unset($this->tpl);
		}

		// destroy io
		if ( isset($this->io) )
		{
			sys::kill($this->io);
			unset($this->io);
		}

		// destroy error
		if ( isset($this->error) )
		{
			sys::kill($this->error);
			unset($this->error);
		}

		// destroy lang
		if ( isset($this->utf8) )
		{
			sys::kill($this->utf8);
			unset($this->utf8);
		}

		// destroy lang
		if ( isset($this->lang) )
		{
			sys::kill($this->lang);
			unset($this->lang);
		}

		if ( isset($this->rstats) )
		{
			sys::kill($this->rstats);
			unset($this->rstats);
		}

		// restore magic_quotes_runtime status
		if ( $this->magic_quotes_runtime !== false )
		{
			if ( $this->magic_quotes_runtime )
			{
				set_magic_quotes_runtime(1);
			}
		}
		unset($this->magic_quotes_runtime);

		// restore error_reporting level
		if ( $this->error_reporting !== false )
		{
			error_reporting($this->error_reporting);
		}
		unset($this->error_reporting);
		unset($this->shutdown_registered);

		unset($this->ini);
		unset($this->path);
		unset($this->ext);
		unset($this->requester);
		unset($this->root);
		parent::__destruct();
	}

	function set($api_name, $root, $requester, $path=false, $skip_server_status=false)
	{
		parent::set($api_name);

		// requester
		$this->root = $root;
		$this->requester = $requester;
		$this->ext = strrchr(__FILE__, '.');
		$this->path = $path ? $path : $this->root . 'sys/';

		// set the server status
		if ( !$skip_server_status )
		{
			// previous error reporting level
			$this->error_reporting = error_reporting(E_ALL ^ E_NOTICE);

			// init randomizer
			if ( version_compare(PHP_VERSION, '4.2.0', '<') )
			{
				list($usec, $sec) = explode(' ', time());
				$seed = (float) $sec + ((float) $usec * 100000);
				mt_srand($seed);
			}

			// get magic_quotes_runtime status
			if ( ($this->magic_quotes_runtime = function_exists('get_magic_quotes_runtime') ? get_magic_quotes_runtime() : false) )
			{
				set_magic_quotes_runtime(0);
			}
		}

		// set the system ini values
		$this->ini_set();
		$this->ini_get('string', 'layer');
		return true;
	}

	// create the system dependant objects
	function set_context($start_time=false)
	{
		// shutdown function
		if ( ($this->shutdown_registered = !isset($this->shutdown_registered) || ($this->shutdown_registered === false)) && !version_compare(PHP_VERSION, '5.0.0', '>=') )
		{
			register_shutdown_function($this->ini_get('shutdown_function', 'callback'));
		}

		// run stats reports handler
		if ( (!isset($this->rstats) || ($this->rstats === false)) && ($class = $this->ini_get('rstats', 'class')) )
		{
			$this->rstats = new $class();
			$this->rstats->set($this->api_name);
		}
		if ( isset($this->rstats) && is_object($this->rstats) )
		{
			$this->rstats->register('script', array('title' => $this->api_name, 'tick' => $start_time));
		}

		// lang handler
		if ( (!isset($this->lang) || ($this->lang === false)) && ($class = $this->ini_get('lang', 'class')) )
		{
			$this->lang = new $class();
			$this->lang->set($this->api_name);
		}

		// we can now register the scripts to the lang object
		if ( isset($this->lang) && is_object($this->lang) )
		{
			$this->lang->register('sys');
			$this->lang->register('sys.datetime');
		}

		// error handler
		if ( (!isset($this->error) || ($this->error === false)) && ($class = $this->ini_get('error', 'class')) )
		{
			$this->error = new $class();
			$this->error->set($this->api_name);
		}

		// utf-8 handler
		if ( (!isset($this->utf8) || ($this->utf8 === false)) && ($class = $this->ini_get('utf8', 'class')) && ($class = call_user_func(array($class, 'get_layer'))) )
		{
			$this->utf8 = new $class();
			$this->utf8->set($this->api_name);
		}

		// io layer
		if ( (!isset($this->io) || ($this->io === false)) && ($class = $this->ini_get('io', 'class')) )
		{
			$this->io = new $class();
			$this->io->set($this->api_name);
		}

		// template layer
		if ( (!isset($this->tpl) || ($this->tpl === false)) && ($class = $this->ini_get('tpl', 'class')) )
		{
			$this->tpl = new $class();
			$this->tpl->set($this->api_name);
		}

		// register our system tpl path
		if ( isset($this->tpl) && is_object($this->tpl) )
		{
			// we expect the sys tpl cache path to be $this->root . 'cache/'
			$this->tpl->register_tpl($this->root . 'styles/sys.tpls/', '.tpl', $this->root . 'cache/', 'sys');
		}
	}

	// register global objects we want to kill with the system one
	function register($name)
	{
		if ( isset($GLOBALS[$name]) )
		{
			if ( !$this->objects )
			{
				$this->objects = array();
			}
			$id = count($this->objects);
			$this->objects[$id] = $name;
			return $id;
		}
		return false;
	}

	//
	// ini
	//

	// set the ini values
	// entry:
	//		o none: simply set the $this->ini values from sys/sys.ini.php if not already done
	//		o key::string, value::mixed: set value for key
	//		o set::array(string => mixed, ...): merge the array to $this->ini
	function ini_set()
	{
		$args = func_get_args();

		// first call: load our system core values ini file
		if ( !$args )
		{
			return $this->ini ? false : $this->ini_load('SYS/sys.ini');
		}

		// other call: add the values
		$args = is_array($args[0]) ? $args[0] : array($args[0] => isset($args[1]) ? $args[1] : false);
		$this->ini = $this->ini ? array_merge($this->ini, $args) : $args;
		return true;
	}

	// this one will return set the ini values from an ini file
	function ini_load($ini_file)
	{
		$ini = false;
		if ( $ini_file && ($ini_file = $this->get_layer_file($ini_file)) && ($filename = sys::realpath($ini_file)) && file_exists($filename) )
		{
			include($ini_file);
		}
		return $ini ? $this->ini_set($ini) : false;
	}

	function ini_unset($key)
	{
		if ( $this->ini && isset($this->ini[$key]) )
		{
			unset($this->ini[$key]);
		}
		return true;
	}

	function ini_get($key, $attr=false)
	{
		if ( !$this->ini )
		{
			$this->ini_set();
		}
		if ( !$key || !$this->ini || !isset($this->ini[$key]) )
		{
			return false;
		}
		switch ( $attr )
		{
			// ressource: return the file path, without the extension (for js files)
			case 'resource':
				return isset($this->ini[$key]['file']) ? $this->get_layer_file($this->ini[$key]['file'], true) : false;

			// dir: return the directory name: doesn't check the existence. Used for lang files
			case 'dir':
				return isset($this->ini[$key]['dir']) ? $this->get_layer_file($this->ini[$key]['dir'], true) : false;

			// file: return the relative path of the filename (plus extension) if exists. Used for dbconfig.php and by ini_get(layer)
			case 'file':
				return isset($this->ini[$key]['file']) && ($name = $this->get_layer_file($this->ini[$key]['file'])) && ($filename = sys::realpath($name)) && file_exists($filename) ? $name : false;

			// layer: load the file
			case 'layer':
				// the key does not exists: end there
				if ( !isset($this->ini[$key]) || !$this->ini[$key] || (!isset($this->ini[$key]['class']) && !isset($this->ini[$key]['function'])) )
				{
					return false;
				}

				// already loaded: end there
				if ( (isset($this->ini[$key]['class']) && class_exists($this->ini[$key]['class'])) || (isset($this->ini[$key]['function']) && function_exists($this->ini[$key]['function'])) )
				{
					return true;
				}

				// we may use other layer: load them if necessary
				if ( ($extra = isset($this->ini[$key]['layer']) && $this->ini[$key]['layer']) )
				{
					if ( !$this->ini[$key]['layer'] )
					{
						return false;
					}
					if ( !is_array($this->ini[$key]['layer']) )
					{
						$this->ini[$key]['layer'] = array($this->ini[$key]['layer']);
					}
					foreach ( $this->ini[$key]['layer'] as $layer )
					{
						if ( !$layer || !$this->ini_get($layer, 'layer') )
						{
							return false;
						}
					}
				}

				// loaded during the layer load: end there
				if ( $extra && ((isset($this->ini[$key]['class']) && class_exists($this->ini[$key]['class'])) || (isset($this->ini[$key]['function']) && function_exists($this->ini[$key]['function']))) )
				{
					return true;
				}

				// the layer is not yet available: load it
				if ( ($found = ($name = $this->ini_get($key, 'file'))) )
				{
					include($name);
				}
				return $found;

			// class/function: return the class/function name, load the layer if necessary
			case 'class':
			case 'function':
				return isset($this->ini[$key]) && $this->ini[$key] && isset($this->ini[$key][$attr]) && (call_user_func($attr . '_exists', $this->ini[$key][$attr]) || ($this->ini_get($key, 'layer') && call_user_func($attr . '_exists', $this->ini[$key][$attr]))) ? $this->ini[$key][$attr] : false;

			default:
				return isset($this->ini[$key]) ? $this->ini[$key] : false;
		}
	}

	// turn a layer name to a layer file, with or without the extension
	function get_layer_file($name, $no_ext=false)
	{
		if ( is_array($name) )
		{
			return array_map(array(&$this, 'get_layer_file'), $name);
		}
		return $name ? str_replace(array('ROOT/', 'SYS/'), array($this->root, $this->path), $name) . ($no_ext ? '' : $this->ext) : false;
	}

	//
	// basic functions
	//

	// public: get server url
	function get_server_url($noscript=false)
	{
		$server_name = sys_string::strtolower(sys_string::htmlspecialchars($this->io->read('HTTP_HOST', '', '_SERVER')));
		$script_path = sys_string::strtolower(sys_string::htmlspecialchars($this->io->read('SCRIPT_NAME', '', '_SERVER')));
		if ( $noscript )
		{
			$script_path = preg_replace('#/+#is', '/', preg_replace('#' . preg_quote($this->requester . $this->ext, '#') . '$#is', '', $script_path));
		}
		$server_protocol = preg_match('#https#i', sys_string::htmlspecialchars($this->io->read('SERVER_PROTOCOL', '', '_SERVER'))) ? 'https://' : 'http://';
		if ( ($server_port = $this->io->read('SERVER_PORT', 0, '_SERVER')) )
		{
			$server_name = preg_replace('#' . preg_quote(':' . $server_port, '#') . '^#is', '', $server_name);
		}
		$server_port = $server_port && ($server_port != 80) ? ':' . $server_port : '';
		return $server_protocol . $server_name . $server_port . $script_path;
	}

	// static: check if we have an object and run the destroy() method if exists and is required (eg PHP < 5.0.0)
	function kill(&$obj)
	{
		if ( !version_compare(PHP_VERSION, '5.0.0', '>=') && is_object($obj) && method_exists($obj, '__destruct') )
		{
			$obj->__destruct();
		}
		return true;
	}

	// static: format microtime() result
	// - entry: none
	// - return: microtime() in seconds
	function microtime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return (float) $usec + (float) $sec;
	}

	// static: check if realpath() is available and return the file real path if possible
	function realpath($path)
	{
		static $done, $realpath_exists;
		if ( !$done && ($done = true) )
		{
			$checkpath = function_exists('realpath') ? @realpath('.') : false;
			$realpath_exists = $checkpath && ($checkpath != '.');
		}
		return $realpath_exists ? str_replace('\\', '/', @realpath($path)) : str_replace('\\', '/', $path);
	}

	// static: check if the directory exists
	function dir_exists($dir)
	{
		return ($filename = sys::realpath($dir . '.')) && file_exists($filename);
	}

	// static: used to write cache files ie
	function write($fullpath, &$content, $skip_error=false)
	{
		if ( ($fp = @fopen($fullpath, 'wb')) )
		{
			@flock($fp, LOCK_EX);
			@fwrite($fp, $content);
			@flock($fp, LOCK_UN);
			@fclose($fp);
			@umask(0000);
			@chmod($fullpath, 0644);
			return true;
		}
		trigger_error(sys_error::sprintf('err_sys_write', basename($fullpath)), $skip_error ? E_USER_NOTICE : E_USER_ERROR);
		return false;
	}

	// static: wrapper for file_get_contents()
	function file_get_contents($realpath)
	{
		$content = false;
		if ( function_exists('file_get_contents') )
		{
			$content = file_get_contents($realpath);
		}
		else if ( ($fp = @fopen($realpath, 'r')) )
		{
			$content = trim(@fread($fp, filesize($realpath)));
			@fclose($fp);
		}
		return $content;
	}

	// static: regular expressions
	function preg_expr($type)
	{
		switch ( $type )
		{
			case 'email':
				return '^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$';
		}
		return false;
	}
}

?>