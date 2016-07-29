<?php
//
//	file: sys/utf8/utf8.wrapper.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/07/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_utf8_wrapper extends sys_utf8
{
	var $xml_extension;
	var $regexp_u;
	var $regexp_char;

	function __construct()
	{
		parent::__construct();
		$this->xml_extension = false;
		$this->regexp_u = false;
		$this->regexp_char = false;
	}

	function __destruct()
	{
		unset($this->regexp_char);
		unset($this->regexp_u);
		unset($this->xml_extension);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);

		// verify utf8_encode/_decode exists
		$this->xml_extension = extension_loaded('xml') && function_exists('utf8_decode');

		// /u should be present since php 4.1.0 for *NIX, and 4.2.3 for w32/64 servers
		$this->regexp_u = @preg_match('#.#u', 'a') ? 'u' : '';
		$this->regexp_char = $this->regexp_u ? '.' : '(?:[^\x80-\xbf][\x80-\xbf]*)';
	}

	function strlen($str)
	{
		return strlen($this->xml_extension ? utf8_decode($str) : preg_replace('#' . $this->regexp_char . '#s' . $this->regexp_u, '_', $str));
	}

	function substr($str, $start, $length=null)
	{
		// adjust parms
		if ( ($len_str = empty($str) ? false : $this->strlen($str)) )
		{
			$start = $start < 0 ? $len_str + $start : $start;
			if ( ($start < 0) || ($start >= $len_str) || ($length === 0) || !(($length === null) || ($length < 0) ? max(0, $len_str + (int) $length - $start) : min($length, $len_str - $start)) )
			{
				return '';
			}

			$high = intval($start / 65535);
			$low = $start % 65535;
			$regexp_start = (!$high ? '' : '(?:' . $this->regexp_char . '{65535})' . ($high == 1 ? '' : '{' . $high . '}')) . (!$low ? '' : $this->regexp_char . ($low == 1 ? '' : '{' . $low . '}'));

			if ( ($length < 0) || ($length === null) || (($length > 0) && ($start + $length == $len_str)) )
			{
				$regexp_match = '.*'; // match all

				$regexp_end = '';
				if ( $length < 0 )
				{
					$high = intval(abs($length) / 65535);
					$low = abs($length) % 65535;
					$regexp_end = (!$high ? '' : '(?:' . $this->regexp_char . '{65535})' . ($high == 1 ? '' : '{' . $high . '}')) . (!$low ? '' : $this->regexp_char . ($low == 1 ? '' : '{' . $low . '}'));
				}
			}
			else
			{
				$regexp_end = $start + $length < $len_str ? '.*' : '';

				$high = intval($length / 65535);
				$low = $length % 65535;
				$regexp_match = (!$high ? '' : '(?:' . $this->regexp_char . '{65535})' . ($high == 1 ? '' : '{' . $high . '}')) . (!$low ? '' : $this->regexp_char . ($low == 1 ? '' : '{' . $low . '}'));
			}
			return preg_replace('#^' . ($regexp_start ? '(?:' . $regexp_start . ')' : '') . '(' . $regexp_match . ')' . $regexp_end . '$#s' . $this->regexp_u, '$1', $str);
		}
		return '';
	}
}

?>