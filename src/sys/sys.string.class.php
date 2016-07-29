<?php
//
//	file: sys/sys.string.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/06/2007
//	version: 0.0.2 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

// SYS constants define the varname of our unique object which handles system status
if ( !defined('SYS') )
{
	die('Not allowed');
}

/**
 * @class: sys_string
 * @desc: static function to handle utf-8 aware string functions
**/
class sys_string extends sys_stdclass
{
	// static: htmlspecialchars
	function htmlspecialchars($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? htmlspecialchars($str, ENT_COMPAT, 'UTF-8') : htmlspecialchars($str, ENT_COMPAT);
	}

	// static: reverse html_specialchars
	// - entry: string
	// - return: htmlspecialchars_decode($str)
	function htmlspecialchars_decode($str)
	{
		static $done, $exists, $html_translation_table_fliped;
		if ( !$done && ($done = true) )
		{
			$html_translation_table_fliped = false;
			if ( !($exists = function_exists('htmlspecialchars_decode')) )
			{
				if ( function_exists('get_html_translation_table') )
				{
					$html_translation_table_fliped = array_flip(get_html_translation_table(HTML_ENTITIES));
				}
				if ( !$html_translation_table_fliped )
				{
					$html_translation_table_fliped = array('&amp;' => '&', '&#039;' => '\'', '&quot;' => '"', '&lt;' => '<', '&gt;' => '>');
				}
			}
		}
		return $exists ? htmlspecialchars_decode($str) : strtr($str, $html_translation_table_fliped);
	}

	// static: encode html entities
	function htmlentities($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? htmlentities($str, ENT_COMPAT, 'UTF-8') : htmlentities($str, ENT_COMPAT);
	}

	// static: decode html entities (exists since php 4.3.0)
	function html_entity_decode($str)
	{
		// from the php documentation
		if ( !version_compare(PHP_VERSION, '5.0.0', '>=') )
		{
			$str = preg_replace('#&\#x([0-9a-f]+);#ei', 'chr(hexdec("$1"))', $str);
			$str = preg_replace('#&\#([0-9]+);#e', 'chr("$1")', $str);
			if ( function_exists('get_html_translation_table') )
			{
				$html_translation_table_fliped = array_flip(get_html_translation_table(HTML_ENTITIES));
			}
			if ( !$html_translation_table_fliped )
			{
				$html_translation_table_fliped = array('&amp;' => '&', '&#039;' => '\'', '&quot;' => '"', '&lt;' => '<', '&gt;' => '>');
			}
			return strtr($str, $html_translation_table_fliped);
		}
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? html_entity_decode($str, ENT_COMPAT, 'UTF-8') : html_entity_decode($str, ENT_COMPAT);
	}

	// static: strtolower
	function strtolower($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? $GLOBALS[SYS]->utf8->strtolower($str) : strtolower($str);
	}

	// static: strtoupper
	function strtoupper($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? $GLOBALS[SYS]->utf8->strtoupper($str) : strtoupper($str);
	}

	// static: substr
	function substr($str, $start, $length=null)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? $GLOBALS[SYS]->utf8->substr($str, $start, $length) : substr($str, $start, $length);
	}

	// static: strlen
	function strlen($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? $GLOBALS[SYS]->utf8->strlen($str) : strlen($str);
	}

	// static: trim or normalize
	function normalize($str)
	{
		return isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) ? $GLOBALS[SYS]->utf8->normalize(trim($str)) : trim($str);
	}
}

?>