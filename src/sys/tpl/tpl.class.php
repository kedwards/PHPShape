<?php
//
//	file: sys/tpl.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 03/06/2007
//	version: 0.0.4.CH - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

// tpl parser
class sys_tpl extends sys_stdclass
{
	var $data;
	var $tpls;
	var $funcs;
	var $check_caches;
	var $disable_caches;
	var $compiled;
	var $debug;
	var $xml;

	var $compiler;
	var $imgs;

	function __construct()
	{
		parent::__construct();
		$this->data = array();
		$this->tpls = array();
		$this->funcs = array();
		$this->check_caches = true; // false;
		$this->disable_caches = false;
		$this->compiled = false;
		$this->debug = false;
		$this->xml = false;
		$this->compiler = false;
		$this->imgs = false;
	}

	function __destruct()
	{
		if ( isset($this->imgs) )
		{
			sys::kill($this->imgs);
			unset($this->imgs);
		}
		if ( isset($this->compiler) )
		{
			sys::kill($this->compiler);
			unset($this->compiler);
		}
		unset($this->xml);
		unset($this->debug);
		unset($this->compiled);
		unset($this->disable_caches);
		unset($this->check_caches);
		unset($this->funcs);
		unset($this->tpls);
		unset($this->data);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->debug = false;
		$this->register_function('lang', array(&$GLOBALS[SYS]->lang, 'get'));
		$this->register_function('langhtml', array(&$GLOBALS[SYS]->lang, 'htmlspecialchars_get'));
		if ( isset($GLOBALS[SYS]->rstats) && is_object($GLOBALS[SYS]->rstats) )
		{
			$this->register_function('rstats', array(&$GLOBALS[SYS]->rstats, 'display'));
			$this->debug = $GLOBALS[SYS]->rstats->debug;
		}
	}

	function set_cache($enable=true, $check=false)
	{
		$this->disable_caches = !$enable;
		$this->check_caches = $check;
	}

	function set_debug($debug=true)
	{
		$this->debug = $debug;
	}

	function set_xml($xml)
	{
		$this->xml = $xml;
	}

	// define the style tpls path
	// entry:
	//		o $tpl_path: tpl's path from board root and ended with /
	//		o $tpl_ext: extension used for tpl's, with the front dot: .tpl per default
	//		o $tpl_cache_path: full path to the cache directory or false
	//		o $tpl_cache_prefix: prefix used for this tpls set
	// nb: if $tpl_cache_path or $tpl_cache_prefix are omitted or empty, there will be no cache for this tpls set
	function register_tpl($tpl_path, $tpl_ext=false, $tpl_cache_path=false, $tpl_cache_prefix=false)
	{
		$this->tpls[] = array(
			'path' => $tpl_path,
			'ext' => $tpl_ext ? $tpl_ext : '.tpl',
			'cache' => $tpl_cache_path && $tpl_cache_prefix ? array($tpl_cache_path, $tpl_cache_prefix) : false,
		);
	}

	// define the style images pack
	// entry:
	//		o $img_ini_file: full path to the images def file, including the extension
	function register_img($img_ini_file)
	{
		$sys = &$GLOBALS[SYS];
		if ( (!isset($this->imgs) || ($this->imgs === false)) && ($class = $sys->ini_get('tpl.img', 'class')) )
		{
			$this->imgs = new $class();
			$this->imgs->set($this->api_name);
		}
		if ( isset($this->imgs) && is_object($this->imgs) )
		{
			$this->imgs->register($img_ini_file);
			$this->register_function('img', array(&$this->imgs, 'get'));
		}
	}

	// run the style ini files
	// entry:
	//		o $ini_file: full path to the style ini file, including the extension
	function register_ini($ini_file)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		if ( ($filename = sys::realpath($ini_file)) && file_exists($filename) )
		{
			include($ini_file);
		}
	}

	// register a tpl function
	// entry:
	//		o $name: name used in tpls: %name(parm)
	//		o $callback: real function: 'foo': function foo(), array('foo') = $this->foo(), array($oject, 'foo') = $object->foo()
	function register_function($name, $callback)
	{
		$this->funcs[$name] = is_array($callback) && (count($callback) == 1) ? array(&$this, $callback[0]) : $callback;
		return true;
	}

	//
	// data manipulation
	//

	// create a new block (or add at root if only one parm is sent)
	// entry:
	//		o vars (array) only: root assignation
	//		o switch & vars: nested block
	//		o switch (string) only: switch setting
	function add($switch, $vars=false)
	{
		// we add the vars to root: $switch is actually filled with the vars
		if ( ($vars === false) && is_array($switch) )
		{
			$this->_process('add', false, $switch);
		}
		// we are creating a new entry
		else
		{
			$this->_process('add', $switch, $vars);
		}
	}

	function hide($vars, $value=false, $force=false)
	{
		if ( is_array($vars) )
		{
			$force = $value;
			unset($value);
		}
		else
		{
			$vars = array($vars => $value);
		}
		foreach ( $vars as $name => $value )
		{
			unset($vars[$name]);
			if ( $force || $value )
			{
				$this->_process('add', 'hidden_field', array(
					'NAME' => $name,
					'VALUE' => $value,
				));
			}
		}
	}

	// merge vars to the last iteration of a block
	function merge($switch, $vars)
	{
		$this->_process('merge', $switch, $vars);
	}

	// delete last iteration of a block
	function delete_last($switch)
	{
		$this->_process('delete', $switch);
	}

	// private: add to/remove from the data
	function _process($action, $switch, $vars=false)
	{
		// retrieve a pointer
		$pointer = &$this->data;
		if ( $switch )
		{
			$parts = explode('.', $switch);
			$count_parts = count($parts) - ($action == 'merge' ? 0 : 1);
			for ( $i = 0; $i < $count_parts; $i++ )
			{
				if ( !isset($pointer[ $parts[$i] ]) )
				{
					$pointer[ $parts[$i] ] = array(0 => array());
				}
				$pointer = &$pointer[ $parts[$i] ][ count($pointer[ $parts[$i] ]) - 1 ];
			}
		}

		switch ( $action )
		{
			// add the switch
			case 'add':
				if ( $switch )
				{
					if ( !isset($pointer[ $parts[$count_parts] ]) )
					{
						$pointer[ $parts[$count_parts] ] = array();
					}
					$pointer[ $parts[$count_parts] ][] = $vars;
				}
				else
				{
					$pointer = empty($pointer) ? $vars : array_merge($pointer, $vars);
				}
				break;

			// merge to the last iteration
			case 'merge':
				$pointer = empty($pointer) ? $vars : array_merge($pointer, $vars);
				break;

			// delete the last iteration
			case 'delete':
				if ( isset($pointer[ $parts[$count_parts] ]) )
				{
					$last_iter = count($pointer[ $parts[$count_parts] ]) - 1;
					unset($pointer[ $parts[$count_parts] ][$last_iter]);
					if ( empty($pointer[ $parts[$count_parts] ]) )
					{
						unset($pointer[ $parts[$count_parts] ]);
					}
				}
				break;

			case 'retrieve':
				if ( isset($pointer[ $parts[$count_parts] ]) )
				{
					$last_iter = count($pointer[ $parts[$count_parts] ]) - 1;
					return $pointer[ $parts[$count_parts] ][$last_iter];
				}
				break;
		}
	}

	//
	// parsing processes
	//

	// compile the tpl, then use the result to build the display
	function parse($tpl_name, $keep=false, $from=false, $to=false)
	{
		$sys = &$GLOBALS[SYS];

		$d = &$this->data;
		$f = &$this->funcs;

		// include with another block of data
		if ( ($from !== false) && ($to !== false) )
		{
			$this->add($to, $this->_process('retrieve', $from));
		}

		for ( $tpl_idx = count($this->tpls) - 1; $tpl_idx >= 0; $tpl_idx-- )
		{
			// we may have the content already in memory cache
			if ( isset($this->compiled[$tpl_name]) )
			{
				if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
				{
					echo '<!--// Start of mem cached ' . $tpl_name . ' //-->';
				}
				eval(' ?>' . $this->compiled[$tpl_name] . '<?php ');
				if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
				{
					echo '<!--// End of mem cached ' . $tpl_name . ' //-->' . "\n";
				}

				// delete merged blocks
				if ( ($from !== false) && ($to !== false) )
				{
					$this->delete_last($to);
				}
				return;
			}

			// we may have a cache for this tpl
			$tpl_file = $tpl_relative_name = false;
			if ( ($cache_full_name = $this->_from_cache($tpl_name, $tpl_idx, $tpl_file, $tpl_relative_name)) )
			{
				if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
				{
					echo '<!--// Start of cached ' . $tpl_relative_name . ' //-->';
				}

				// display
				if ( $keep )
				{
					$this->compiled[$tpl_name] = sys::file_get_contents($cache_full_name);
					eval(' ?>' . $this->compiled[$tpl_name] . '<?php ');
				}
				else
				{
					include($cache_full_name);
				}

				// debug info
				if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
				{
					echo '<!--// End of cached ' . $tpl_relative_name . ' //-->' . "\n";
				}

				// delete merged blocks
				if ( ($from !== false) && ($to !== false) )
				{
					$this->delete_last($to);
				}
				return;
			}

			// no cache and no source: next tpls set
			if ( !$tpl_file )
			{
				continue;
			}

			// we have no cache or we need to regenerate it: first get the source
			if ( !($fsize = filesize($tpl_file)) || !($fp = @fopen($tpl_file, 'r')) )
			{
				trigger_error(sys_error::sprintf('err_tpl_empty', $tpl_relative_name), E_USER_ERROR);
			}
			$code = trim(@fread($fp, $fsize));
			@fclose($fp);

			// compile
			if ( (!isset($this->compiler) || ($this->compiler === false)) && ($class = $sys->ini_get('tpl.compiler', 'class')) )
			{
				$this->compiler = new $class();
				$this->compiler->set($this->api_name);
			}
			$code = $this->compiler->process($code, $this->disable_caches || !$this->tpls[$tpl_idx]['cache'] ? $tpl_file: false);

			// generate the cache if required
			$this->_to_cache($tpl_name, $tpl_idx, $code);

			// debug info
			if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
			{
				echo '<!--// Start of ' . $tpl_relative_name . ' //-->';
			}

			// display
			eval(' ?>' . $code . '<?php ');
			if ( $keep )
			{
				$this->compiled[$tpl_name] = $code;
			}
			unset($code);

			// debug info
			if ( isset($sys->rstats) && is_object($sys->rstats) && $this->debug )
			{
				echo '<!--// End of ' . $tpl_relative_name . ' //-->' . "\n";
			}

			// delete merged blocks
			if ( ($from !== false) && ($to !== false) )
			{
				$this->delete_last($to);
			}
			return;
		}

		// if we reach this point, we haven't found the tpl
		trigger_error(sys_error::sprintf('err_tpl_not_exists', $tpl_name), E_USER_ERROR);
	}

	function _get_cache_full_name($tpl_name, $tpl_idx)
	{
		$sys = &$GLOBALS[SYS];
		return $this->tpls[$tpl_idx]['cache'][0] . 'tpl_' . $this->tpls[$tpl_idx]['cache'][1] . '_' . str_replace(array('.', '/'), '_', $tpl_name) . $sys->ext;
	}

	// remove $sys->root from the filename
	function _clear_root($filename)
	{
		$sys = &$GLOBALS[SYS];
		return preg_replace('#^' . preg_quote($sys->root, '#') . '#', '', $filename);
	}

	// private: get a file from the cache
	function _from_cache($tpl_name, $tpl_idx, &$tpl_file, &$tpl_relative_name)
	{
		// full tpl name
		$tpl_file = false;
		$tpl_full_name = $this->tpls[$tpl_idx]['path'] . $tpl_name . $this->tpls[$tpl_idx]['ext'];
		$tpl_relative_name = $this->_clear_root($tpl_full_name);

		// we have no cache for this tpls set, so we simply get the real path for the tpl
		if ( $this->disable_caches || !$this->tpls[$tpl_idx]['cache'] )
		{
			$tpl_file = ($file = sys::realpath($tpl_full_name)) && file_exists($file) ? $file : false;
			return false;
		}

		// cache full name
		$cache_full_name = $this->_get_cache_full_name($tpl_name, $tpl_idx);

		// do we have the cache file ?
		$cache_exists = ($cache_file = sys::realpath($cache_full_name)) && file_exists($cache_file);

		// if we have the cache, we may have to check it
		$tpl_exists = (!$cache_exists || $this->check_caches) && ($tpl_file = sys::realpath($tpl_full_name)) && file_exists($tpl_file);

		// do the check if required
		$cache_exists = $cache_exists && (!$this->check_caches || ($tpl_exists && (filemtime($cache_file) > filemtime($tpl_file))));
		return $cache_exists ? $cache_full_name : false;
	}

	function _to_cache($tpl_name, $tpl_idx, $code)
	{
		if ( $this->disable_caches || !$this->tpls[$tpl_idx]['cache'] )
		{
			return false;
		}

		// cache full name
		$cache_full_name = $this->_get_cache_full_name($tpl_name, $tpl_idx);

		// write the file, ignore errors
		return sys::write($cache_full_name, $code, true);
	}

	//
	// output
	//
	function send_headers()
	{
		$sys = &$GLOBALS[SYS];

		// content header
		$content = array(
			$this->xml ? 'Content-Type: text/xml' : 'Content-Type: text/html',
			isset($sys->utf8) && is_object($sys->utf8) ? 'charset=utf-8' : '',
		);
		header(implode('; ', $content));

		// Cache-control: private=don't allow shared caches, no-cache="set-cookie": don't cache in any case the cookies, must-revalidate: force reload
		// http://archive.apache.org/gnats/4793
		header('Cache-control: private, no-cache="set-cookie", must-revalidate');
		header('Expires: 0');
		header('Pragma: no-cache');
		header('X-UA-Compatible: IE=contours');

		// xml output: add the xml mark
		if ( $this->xml )
		{
			echo '<' . '?xml version="1.0"' . (isset($sys->utf8) && is_object($sys->utf8) ? ' encoding="UTF-8"' : '') . '?' . '>';
		}

		// we can now free some memory
		if ( isset($this->compiler) )
		{
			sys::kill($this->compiler);
			unset($this->compiler);
		}
		$this->compiler = false;
	}
}

?>