<?php
//
//	file: sys/io.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 07/07/2007
//	version: 0.0.3 - 24/08/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_io extends sys_stdclass
{
	var $channels;
	var $magic_quotes_gpc;
	var $string_type;
	var $array_type;

	function __construct()
	{
		parent::__construct();
		$this->channels = false;
		$this->magic_quotes_gpc = false;
		$this->string_type = false;
		$this->array_type = false;
	}

	function __destruct()
	{
		unset($this->array_type);
		unset($this->string_type);
		unset($this->magic_quotes_gpc);
		unset($this->channels);
		parent::__destruct();
	}

	// public: init the instance
	function set($api_name)
	{
		parent::set($api_name);

		$this->magic_quotes_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
		$this->string_type = gettype(' ');
		$this->array_type = gettype(array(0));

		// choose between old fashion & new fashion: we expect all the vars to exists except _SESSION, whatever the method
		// nb.: _REQUEST channel is considered being gp and not gpc
		$new = isset($_GET) || isset($_POST);
		$this->channels = array(
			'_GET' => $new ? '_GET' : 'HTTP_GET_VARS',
			'_POST' => $new ? '_POST' : 'HTTP_POST_VARS',
			'_COOKIE' => $new ? '_COOKIE' : 'HTTP_COOKIE_VARS',
			'_SERVER' => $new ? '_SERVER' : 'HTTP_SERVER_VARS',
			'_ENV' => $new ? '_ENV' : 'HTTP_ENV_VARS',
			'_FILES' => $new ? '_FILES' : 'HTTP_POST_FILES',
		);
		$this->channels += (!$new || !isset($_SESSION)) && ($new || !isset($GLOBALS['HTTP_SESSION_VARS'])) ? array() : array(
			'_SESSION' => $new ? '_SESSION' : 'HTTP_SESSION_VARS',
		);

		// we ensure GLOBALS is not used within a channel to overwrite the real $GLOBALS
		// this is the only case we are going to work with $_REQUEST
		if ( (isset($_REQUEST) && is_array($_REQUEST) && isset($_REQUEST['GLOBALS'])) || $this->exists('GLOBALS', array_keys($this->channels)) )
		{
			die('Not allowed');
		}
	}

	// public: check if a button has been pressed (only $_POST is checked)
	// - return:
	//		o true if button activated
	function button($var)
	{
		return $this->exists($var, '_POST') || ($this->exists($var . '_x', '_POST') && $this->exists($var . '_y', '_POST'));
	}

	// public: check the channels list and detect where the var stands
	//		if channel not provided, _REQUEST will be used (GET, POST)
	//		if channel is equal to *ALL, all channels will be scaned
	// - return:
	//		o channel the var is present
	function exists($var, $channels=null)
	{
		if ( ($channels = $this->_scan($channels)) )
		{
			foreach ( $channels as $channel )
			{
				if ( $this->_exists($var, $channel) )
				{
					return $channel;
				}
			}
		}
		return false;
	}

	// public: read() & get(): read a var value within the channels choosen, and return the value or a list(value, channel) for get
	//		we don't care the $dft value itself, only its type
	//		if channel not provided, _REQUEST will be used (GET, POST), if channel is equal to *ALL, all channels will be scaned
	// - entry:
	//		o $var: string: var name,
	//		o $dft: 0, '', array(''), array(0), array(), null: default value type
	//		o $channels: string or array of strings: channel list
	//		o $skip_norm: boolean: if true, skip the utf-8 normalization for strings
	// - return:
	//		read():
	//			o value of the var within the channel(s), else "empty" type casted on dft type if the var is not found
	//		get():
	//			o list of
	//				- value of the var within the channel(s), else "empty" type casted on dft type if the var is not found
	//				- channel where stands the value, else false
	//
	function read($var, $dft=null, $channels=false, $skip_norm=false)
	{
		list($value, ) = $this->get($var, $dft, $channels, $skip_norm);
		return $value;
	}

	function get($var, $dft=null, $channels=false, $skip_norm=false)
	{
		// get the value of the var
		$res = false;
		if ( ($channels = $this->_scan($channels)) )
		{
			foreach ( $channels as $channel )
			{
				if ( ($res = $this->_exists($var, $channel)) )
				{
					if ( $res[1] == '_GET' )
					{
						// we don't accept arrays from url
						$res[0] = is_array($res[0]) ? array() : urldecode($res[0]);
					}
					break;
				}
			}
		}

		// var does not exists, so return "empty" type casted like $dft
		if ( !$res )
		{
			return array(is_null($dft) ? null : (is_array($dft) ? array() : $this->_settype('', $this->_gettype($dft), true, true)), false);
		}

		// type cast the value, from itself if no default set, skiping magic_quotes_gpc when not from GPC channels
		return array($this->_settype($res[0], $this->_gettype(is_null($dft) ? (is_array($res[0]) ? array() : $res[0]) : $dft), $skip_norm, !$this->magic_quotes_gpc || !in_array($res[1], array('_GET', '_POST', '_COOKIE'))), $res[1]);
	}

	// private: return a full channels list validated
	function _scan($channels)
	{
		$all_channels = array_flip(array('_GET', '_POST', '_COOKIE', '_SERVER', '_ENV', 'getenv', '_FILES', '_SESSION'));
		$channels = !$channels ? array('_REQUEST') : (!is_array($channels) ? array($channels) : $channels);
		$res = array();
		foreach ( $channels as $channel )
		{
			if ( $channel == '*ALL' )
			{
				$res += $all_channels;
			}
			else if ( $channel == '_REQUEST' )
			{
				$res += array_flip(array('_GET', '_POST'));
			}
			else if ( $channel == '_SERVER' )
			{
				$res += array_flip(array('_SERVER', '_ENV', 'getenv'));
			}
			else if ( $channel == '_ENV' )
			{
				$res += array_flip(array('_ENV', 'getenv'));
			}
			else if ( isset($all_channels[$channel]) )
			{
				$res[$channel] = true;
			}
		}
		return empty($res) ? false : array_keys($res);
	}

	// private: return array [$value, $channel] if the channel exists and if the var exists in the channel, else false
	function _exists($var, $channel)
	{
		if ( $channel == 'getenv' )
		{
			$value = getenv($var);
			if ( ($value === false) && function_exists('apache_getenv') )
			{
				$value = apache_getenv($var);
			}
			return ($value === false) ? false : array($value, $channel);
		}
		return isset($this->channels[$channel]) && isset($GLOBALS[ $this->channels[$channel] ]) && is_array($GLOBALS[ $this->channels[$channel] ]) && isset($GLOBALS[ $this->channels[$channel] ][$var]) ? array($GLOBALS[ $this->channels[$channel] ][$var], $channel) : false;
	}

	// private: retrieve a var type
	function _gettype($var)
	{
		if ( is_array($var) && count($var) )
		{
			$value = reset($var);
			$key = key($var);
			return array($this->_gettype($key), $this->_gettype($value));
		}
		return gettype($var);
	}

	// private: force the type of a var.
	// - entry:
	//		o $var: value to cast
	//		o $type: value type from _gettype()
	//		o $skip_norm: boolean: skip the utf-8 normalization/trim (will be used only for passwords, as they will be processed with md5() or similar)
	//		o $skip_gpc: boolean: skip stripslashes() when value does not come from _GET/_POST/_COOKIE channels or magic_quotes_gpc is disabled
	// - return:
	//		o $var casted according to $type, or from its value for uncasted arrays
	function _settype($var, $type, $skip_norm=false, $skip_gpc=false)
	{
		// type is a casted array
		if ( is_array($type) )
		{
			$res = array();
			if ( is_array($var) && count($var) )
			{
				foreach ( $var as $key => $value )
				{
					$res[ $this->_settype($key, $type[0], $skip_norm, $skip_gpc) ] = $this->_settype($value, $type[1], $skip_norm, $skip_gpc);
				}
			}
			return $res;
		}

		// type is an uncasted array
		if ( $type == $this->array_type )
		{
			$res = array();
			if ( is_array($var) && count($var) )
			{
				foreach ( $var as $key => $value )
				{
					$res[ $this->_settype($key, $this->_gettype($key), $skip_norm, $skip_gpc) ] = $this->_settype($value, $this->_gettype($value), $skip_norm, $skip_gpc);
				}
			}
			return $res;
		}

		// type is a scalar
		if ( is_array($var) )
		{
			$var = '';
		}
		settype($var, $type);

		// strings
		if ( $type == $this->string_type )
		{
			if ( !$skip_gpc )
			{
				$var = stripslashes($var);
			}
			if ( !$skip_norm )
			{
				$var = sys_string::normalize($var);
			}
		}
		return $var;
	}
}

?>