<?php
//
//	file: sys/tpl.img.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 19/01/2008
//	version: 0.0.2.CH - 21/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_tpl_img extends sys_stdclass
{
	var $files;
	var $loaded;
	var $data;
	var $attrs;

	function __construct()
	{
		parent::__construct();
		$this->files = array();
		$this->loaded = false;
		$this->data = array();
		$this->attrs = array();
	}

	function __destruct()
	{
		unset($this->attrs);
		unset($this->data);
		unset($this->loaded);
		unset($this->files);
		parent::__destruct();
	}

	// register an images def file to be loaded further
	// entry:
	//		o file, list of files, or array(list of file): files to register
	function register()
	{
		if ( !($args = func_get_args()) || (is_array($args[0]) && !($args = $args[0])) )
		{
			return false;
		}
		foreach ( $args as $file )
		{
			if ( !isset($this->files[$file]) )
			{
				$this->loaded = false;
				$this->files[$file] = false;
			}
		}
		return true;
	}

	// convert a key into $this->data[key]
	// - entry: key
	// - return: $this->data[key] if exists, else key
	function get($key)
	{
		return $this->exists($key) ? $this->data[$key] : $key;
	}

	// retrieve an attribute from the key
	// - entry:
	//		o $attr: attribute name,
	//		o $key: image key
	// - return: '' if not exists, value if exists
	function get_attr($attr, $key)
	{
		return $this->exists($key, $attr) ? $this->attrs[$key][$attr] : '';
	}

	// check the existence of a key or an attribute
	// - entry:
	//		o $key: image key
	//		o attr: if omitted, we only check the existence of the key
	// - return: true or false
	function exists($key, $attr=false)
	{
		if ( !$this->loaded && $this->files )
		{
			$this->_load();
		}
		return $this->data && isset($this->data[$key]) && (!$attr || ($this->attrs && isset($this->attrs[$key]) && $this->attrs[$key] && isset($this->attrs[$key][$attr])));
	}

	// private: load the not yet loaded files
	function _load()
	{
		if ( $this->loaded || !$this->files )
		{
			return false;
		}
		$sys = &$GLOBALS[SYS];
		$io = &$sys->io;
		$lang = isset($sys->lang) && is_object($sys->lang) && isset($sys->lang->active) && $sys->lang->active ? $sys->lang->active : 'en';
		$gif_only = ($user_agent = $io->read('HTTP_USER_AGENT', '', '_SERVER')) && preg_match('#MSIE\s(5\.|6\.)#is', $user_agent);
		foreach ( $this->files as $file => $loaded )
		{
			if ( !$loaded )
			{
				$images = &$this->data;
				$images_attrs = &$this->attrs;
				if ( @file_exists($file) )
				{
					include($file);
				}
			}
			$this->files[$file] = true;
		}
		$this->loaded = true;
		return true;
	}
}

?>