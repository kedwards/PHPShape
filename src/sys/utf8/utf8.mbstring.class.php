<?php
//
//	file: sys/utf8/utf8.mbstring.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/07/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_utf8_mbstring extends sys_utf8
{
	var $overload;

	function __construct()
	{
		parent::__construct();
		$this->overload = false;
	}

	function set($api_name)
	{
		parent::set($api_name);
		mb_internal_encoding('UTF-8');
		$this->overload = (ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING) == MB_OVERLOAD_STRING;
	}

	function __destruct()
	{
		unset($this->overload);
		parent::__destruct();
	}

	function strlen($str)
	{
		return mb_strlen($str, 'UTF-8');
	}

	function substr($str, $start, $length=false)
	{
		if ( !empty($str) && ($len_str = $this->strlen($str)) )
		{
			$start = $start < 0 ? $len_str + $start : $start;
			if ( ($start >= 0) && ($start < $len_str) && ($length !== 0) && ($len_match = ($length === false) || ($length < 0) ? max(0, $len_str + ($length === false ? 0 : $length) - $start) : min($length, $len_str - $start)) )
			{
				return mb_substr($str, $start, $len_match, 'UTF-8');
			}
		}
		return '';
	}
}

?>