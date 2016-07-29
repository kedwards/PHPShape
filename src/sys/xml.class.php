<?php
//
//	file: sys/xml.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/01/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_xml_parser extends sys_stdclass
{
	var $data;
	var $resource;
	var $encoding;

	function __construct()
	{
		parent::__construct();
		$this->data = array();
		$this->resource = '';
		$this->encoding = false;
	}

	function __destruct()
	{
		unset($this->encoding);
		unset($this->resource);
		unset($this->data);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->encoding = 'UTF-8';
	}

	function set_encoding($encoding)
	{
		$this->encoding = $encoding;
	}

	function set_resource($resource)
	{
		$this->resource = $resource;
	}

	// we can use xml_*() functions
	function parse($xml)
	{
		if ( empty($xml) )
		{
			trigger_error('err_xml_empty', E_USER_ERROR);
		}
		$this->data = array();

		// we can use the xml_*() functions
		$errno = $errmsg = false;
		if ( function_exists('xml_parser_create') )
		{
			$parser = xml_parser_create($this->encoding ? $this->encoding : 'UTF-8');
			xml_set_object($parser, $this);
			xml_set_element_handler($parser, 'tag_open', 'tag_close');
			xml_set_character_data_handler($parser, 'content');
			$result = xml_parse($parser, $xml);
			if ( !$result )
			{
				$errno = xml_get_error_code($parser);
				$errmsg = xml_error_string($errno);
			}
			xml_parser_free($parser);
			if ( !$result )
			{
				trigger_error(sys_error::sprintf('err_xml_error', $errmsg, $errno, $this->resource), E_USER_ERROR);
			}
		}
		// we don't have the support of xml_*() functions
		else
		{
			$tags_split = '#<([^\?!>]+)>#is';
			$tags_match = '#<([\/])?([^\n\r\s\t\?!>]+)[\n\r\s\t]*([^\?!>\/]*)([\/])?>#is';
			$attrs_match = '#([^=]+)=\"([^\"]*)\"[\n\r\s\t]*#is';
			$tags = array();
			if ( !($count_tags = preg_match_all($tags_match, $xml, $tags)) || !($cdata = preg_split($tags_split, $xml)) )
			{
				trigger_error('err_xml_no_tags', E_USER_ERROR);
			}

			for ( $i = 0; $i < $count_tags; $i++ )
			{
				$tag_close = $tags[1][$i] == '/';
				$tag_name = strtoupper(trim($tags[2][$i]));
				$tag_attrs = array();
				$matches = array();
				if ( $tags[3][$i] && preg_match_all($attrs_match, $tags[3][$i], $matches) && ($count_matches = count($matches[0])) )
				{
					for ( $j = 0; $j < $count_matches; $j++ )
					{
						$tag_attrs[ strtoupper($matches[1][$j]) ] = $matches[2][$j];
					}
				}
				$tag_autoclose = $tags[4][$i] == '/';
				$tag_cdata = trim($cdata[ ($i + 1) ]);

				if ( !$tag_close )
				{
					$this->tag_open(false, $tag_name, $tag_attrs);
					if ( !$tag_autoclose && $tag_cdata )
					{
						$this->content(false, $tag_cdata);
					}
				}
				if ( $tag_close || $tag_autoclose )
				{
					$this->tag_close(false, $tag_name);
				}
			}
		}
		return $this->data;
	}

	function tag_open($parser, $name, $attrs)
	{
		$res = array('name' => $name);
		if ( !empty($attrs) )
		{
			$res['attrs'] = $attrs;
		}
		$this->data[] = $res;
	}

	function content($parser, $cdata)
	{
		if ( trim($cdata) !== '' )
		{
			$count_data = count($this->data);
			if ( !isset($this->data[ ($count_data - 1) ]['content']) )
			{
				$this->data[ ($count_data - 1) ]['content'] = '';
			}
			$this->data[ ($count_data - 1) ]['content'] .= $cdata;
		}
	}

	function tag_close($parser, $name)
	{
		$count_data = count($this->data);
		if ( $count_data > 1 )
		{
			$child = array_pop($this->data);
			$count_data--;
			if ( !isset($this->data[ ($count_data - 1) ]['childs']) )
			{
				$this->data[ ($count_data - 1) ]['childs'] = array();
			}
			$this->data[ ($count_data - 1) ]['childs'][] = $child;
		}
	}
}

?>