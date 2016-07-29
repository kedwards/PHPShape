<?php
//
//	file: sys/lang.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 18/01/2008
//	version: 0.0.4 - 14/08/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_lang extends sys_stdclass
{
	var $files;
	var $loaded;
	var $availables;
	var $active;
	var $data;
	var $times;

	var $default;
	var $default_desc;

	function __construct()
	{
		parent::__construct();
		$this->files = array();
		$this->loaded = false;
		$this->availables = false;
		$this->active = false;
		$this->data = array();

		// fallback: english
		$this->default = 'en';
		$this->default_desc = 'English';
	}

	function __destruct()
	{
		unset($this->default_desc);
		unset($this->default);
		unset($this->data);
		unset($this->active);
		unset($this->availables);
		unset($this->loaded);
		unset($this->files);
		parent::__destruct();
	}

	// public: set the language used
	// - entry:
	//		o $lang: language to use: fallback to "en" ($this->default) if it doesn't match an existing languages sub-directory
	// - return:
	//		o true if language is equal to default or exists
	function set_lang($lang=false)
	{
		$sys = &$GLOBALS[SYS];

		if ( !$lang )
		{
			$lang = $this->guess_lang();
		}

		if ( $this->active && ($this->active !== $lang) )
		{
			$this->loaded = false;
			if ( $this->files )
			{
				foreach ( $this->files as $file => $dummy )
				{
					$this->files[$file] = false;
				}
			}
		}

		// check the path
		$exists = !$lang || ($lang == $this->default) || (preg_match('#^[a-z0-9\-]+$#', $lang) && sys::dir_exists($sys->ini_get('lang', 'dir') . '/' . $lang . '/'));
		$this->active = $exists ? $lang : $this->default;
		return $exists;
	}

	// public: get available lang packs
	function get_available()
	{
		$sys = &$GLOBALS[SYS];

		if ( !($lang_root = $sys->ini_get('lang', 'dir')) )
		{
			$lang_root = $sys->root . 'languages';
		}

		// get languages available (sub-directories of languages directory)
		$this->availables = array();
		$handler = opendir($lang_root);
		while ( ($filename = readdir($handler)) )
		{
			if ( !in_array($filename, array('.', '..')) && is_dir($lang_root . '/' . $filename) )
			{
				$key = sys_string::strtolower($filename);
				$this->availables[$key] = sys_string::strtolower($filename);
				if ( ($desc_name = $lang_root . '/' . $filename . '/_.lang' . $sys->ext) && file_exists($desc_name) )
				{
					$lang = array();
					include($desc_name);
					if ( isset($lang['DESCRIPTION']) && $lang['DESCRIPTION'] )
					{
						$this->availables[$key] = $lang['DESCRIPTION'];
					}
				}
				else
				{
					unset($this->availables[$key]);
				}
			}
		}
		if ( !$this->availables )
		{
			$this->availables[$this->default] = $this->default_desc;
		}
		return true;
	}

	// public: guess the user's browser lang
	// - return:
	//		o "en" or a lang directory name matching the Accept-Language HTTP header
	function guess_lang()
	{
		$sys = &$GLOBALS[SYS];

		if ( !$this->availables )
		{
			$this->get_available();
		}

		// only one available language: use it
		if ( $this->availables && (count($this->availables) == 1) )
		{
			return key($this->availables);
		}

		// we have various languages possible, so get the Accept-Language HTTP header & languages directory
		if ( $this->availables && (count($this->availables) > 1) && isset($sys->io) && is_object($sys->io) && ($accept_languages = $sys->io->read('HTTP_ACCEPT_LANGUAGE', '', '_SERVER')) && ($lang_root = $sys->ini_get('lang', 'dir')) )
		{
			$accept_languages = explode(',', str_replace(array('_', ';'), array('-', ','), sys_string::strtolower(implode(',', array_map('trim', explode(',', $accept_languages))))));
			for ( $i = count($accept_languages)-1; $i >= 0; $i-- )
			{
				if ( preg_match('#=#', $accept_languages[$i]) )
				{
					unset($accept_languages[$i]);
				}
			}

			// ok, there is something on the user's browser
			if ( $accept_languages )
			{
				$accept_languages[] = $this->default;
				$accept_languages = array_keys(array_flip($accept_languages));

				// get languages available (sub-directories of languages directory)
				$lang_dirs = ',' . implode(',', array_keys($this->availables));

				// check the HTTP header against the existing lang directories
				foreach ( $accept_languages as $lang )
				{
					if ( preg_match('#,' . preg_quote($lang, '#') . '#is', $lang_dirs) )
					{
						return $lang;
					}

					// search on generic (first part of the lang code)
					if ( ($lang = explode('-', $lang)) && isset($lang[1]) )
					{
						if ( preg_match('#,' . preg_quote($lang[0], '#') . '#is', $lang_dirs) )
						{
							return $lang[0];
						}
					}
				}
			}
		}
		return $this->default;
	}

	// register a lang def file to be loaded further
	// - entry:
	//		o file, list of files, or array(list of file): files to register
	function register()
	{
		if ( !($args = func_get_args()) )
		{
			return false;
		}
		foreach ( $args as $file )
		{
			$dir = false;
			if ( is_array($file) )
			{
				$dir = $file[1];
				$file = $file[0];
			}

			$file = str_replace('/', '_', preg_replace('#^[\./]+#', '', $file));
			if ( $dir )
			{
				$file = $file . ';' . $dir;
			}
			if ( !isset($this->files[$file]) )
			{
				$this->loaded = false;
				$this->files[$file] = false;
			}
		}
		return true;
	}

	// public: convert a key into the $lang[] value, and apply a sprintf() if required
	// note: should be used only by the tpl parser
	// - entry: key or list(key, parm 1, parm 2, ...) or array($key, parm 1, parm 2, ...)
	// - return:
	//		o if no parms: value of $data[key] if exists, else key
	//		o if parms: sprintf($data[key] else key, parm 1, parm 2, ...)
	function get()
	{
		// do we have parms ?
		if ( !($args = func_get_args()) || (is_array($args[0]) && !($args = $args[0])) )
		{
			return '';
		}
		// nested array (it shouldn't occur) or many parms
		if ( is_array($args[0]) || (count($args) > 1) )
		{
			if ( !is_array($args[0]) )
			{
				$args[0] = array($args[0]);
			}
			return call_user_func_array('sprintf', array_map(array(&$this, '_map_get_args'), $args));
		}

		// load the language files if not done
		if ( !$args[0] )
		{
			return '';
		}
		if ( !$this->loaded && $this->files )
		{
			$this->_load();
		}
		return !empty($this->data) && isset($this->data[ $args[0] ]) ? $this->data[ $args[0] ] : $args[0];
	}

	function htmlspecialchars_get()
	{
		$args = func_get_args();
		return sys_string::htmlspecialchars(call_user_func_array(array(&$this, 'get'), $args));
	}

	// private: recursive mapping for sprintf
	function _map_get_args($arg)
	{
		return is_array($arg) ? $this->get($arg) : $arg;
	}

	// private: load the not yet loaded files
	function _load()
	{
		$sys = &$GLOBALS[SYS];
		if ( $this->loaded || !$this->files )
		{
			return false;
		}
		foreach ( $this->files as $full_file => $loaded )
		{
			if ( !$loaded )
			{
				$file = explode(';', $full_file);
				$dir = isset($file[1]) ? $file[1] : '';
				$file = $file[0];

				if ( !$this->active )
				{
					$this->set_lang();
				}
				$lang_root = $dir ? $dir : $sys->ini_get('lang', 'dir');
				$lang_file = $this->get_filename($file);
				$fnames = array(
					$this->active ? $lang_root . '/' . $this->active . '/' . $lang_file : false,
					$lang_root . '/' . $this->default . '/' . $lang_file,
				);
				foreach ( $fnames as $fname )
				{
					if ( $fname && ($filename = sys::realpath($fname)) && file_exists($filename) )
					{
						// load the file to $this->data
						$this->_load_to_data($fname, $this->data);

						// sys.datetime files receive a supplementary treatment, filling $this->times required for date translations
						if ( $file == 'sys.datetime' )
						{
							$this->_load_to_times($fname);
						}
						break;
					}
				}
			}
			$this->files[$full_file] = true;
		}
		$this->loaded = true;

		// force encoding
		if ( isset($this->data['ENCODING']) && isset($sys->utf8) && is_object($sys->utf8) )
		{
			$this->data['ENCODING'] = 'UTF-8';
		}
		return true;
	}

	// build the lang filename from the name
	function get_filename($file)
	{
		$sys = &$GLOBALS[SYS];
		return $file . '.lang' . $sys->ext;
	}

	// private: load $lang into $this->data using $lang as an alias
	function _load_to_data($fname, &$lang)
	{
		include($fname);
	}

	// private: process $lang into $this->times: specific treatment for sys.datetime.lang files
	function _load_to_times($fname)
	{
		$lang = array();
		include($fname);
		if ( $lang )
		{
			foreach ( $lang as $key => $value )
			{
				if ( ($key = explode('.', $key)) && ($key[0] == 'time') )
				{
					$this->times[ $key[1] ] = $value;
				}
			}
		}
	}
}

?>