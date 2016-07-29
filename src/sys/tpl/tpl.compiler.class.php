<?php
//
//	file: sys/tpl.compiler.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 03/06/2007
//	version: 0.0.5.CH - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

// tpl compiler
class sys_tpl_compiler extends sys_stdclass
{
	function process($code)
	{
		$lf = '
';
		// php tags, based on php 5.2.3, Zend/zend_language_scanner.l & remove front tabulations/spaces
		$code = preg_replace(array('#<[\?%].*?[\?%]>|<\?php[\n\r\s\t].*?\?>|<script[\n\r\s\t]+language[\n\r\s\t]*=[\n\r\s\t]*["\']?php["\']?[^>]*>.*?</script[\n\r\s\t]*>#is', '#([\n\r]+)[\s\t]*#'), array('', $lf), $code);

		// get all php blocks
		$phps = array();
		$php_regexp = '#<!-- PHP -->[\n\r\s\t]*(.*?)[\n\r\s\t]*<!-- ENDPHP -->#s';
		preg_match_all($php_regexp, $code, $phps);
		$phps = $phps[1];
		$code = preg_replace($php_regexp, '<!-- PHP -->', $code);

		// turn vars into refs
		if ( !empty($phps) )
		{
			$marker = '[__php__::' . md5(mt_rand()) . ']';
			$phps = implode($marker, $phps);
			$this->_vars($phps);
			$phps = explode($marker, $phps);
			unset($marker);
		}

		// get all functions
		$functions = array();
		$functions_regexp = '#%([a-z0-9_]+)\((.*?)\)#is';
		preg_match_all($functions_regexp, $code, $functions);
		$code = preg_replace($functions_regexp, '<!-- FUNC -->', $code);

		// turn vars into refs
		if ( !empty($functions[0]) )
		{
			unset($functions[0]);
			$marker = '[__func__::' . md5(mt_rand()) . ']';
			$functions[2] = implode($marker, $functions[2]);
			$this->_vars($functions[2]);
			$functions[2] = explode($marker, $functions[2]);
			unset($marker);

			$functions = array_map(array(&$this, '_ref_func'), $functions[1], $functions[2]);
		}

		// get all text outside tags
		$regexp = '#<!--\s+([A-Z]+)\s+(.*?)?\s*-->#s';
		$text = preg_split($regexp, $code);

		// turn vars into printable refs
		if ( ($count_text = count($text)) )
		{
			$marker = '[__switch__::' . md5(mt_rand()) . ']';
			$text = implode($marker, $text);
			$this->_vars($text, true);
			$text = explode($marker, $text);
			unset($marker);
		}

		// get all tags
		$switches = array();
		preg_match_all($regexp, $code, $switches);
		unset($code);

		$res = '<?php if(!defined(\'SYS\')){die(\'Not Allowed\');} ?>';
		$nest_level = 0;
		for ( $i = 0; $i < $count_text; $i++ )
		{
			$res .= $text[$i];
			unset($text[$i]);
			$switch = isset($switches[1][$i]) ? $switches[1][$i] : false;
			switch ( $switch )
			{
				// conditions, loops
				case 'BEGIN':
					if ( isset($switches[2][$i]) && !empty($switches[2][$i]) )
					{
						// we need the parent switch
						$decomp = explode('.', $switches[2][$i]);
						$last = array_pop($decomp);
						$ref = $this->_ref($decomp) . '[\'' . $last;
						$res .= '<?php if((' . $ref . '__COUNT\']=isset(' . $ref . '\']) ? count(' . $ref . '\']) : 0)){for(' . $ref . '__IDX\']=0;' . $ref . '__IDX\']<' . $ref . '__COUNT\'];' . $ref . '__IDX\']++){ ?>';

						$nest_level++;
					}
					break;
				case 'BEGINELSE':
					$res .= '<?php }}else{{ ?>';
					break;
				case 'END':
					$res .= '<?php }} ?>';
					$nest_level = max(0, --$nest_level);
					break;
				case 'IF':
					if ( isset($switches[2][$i]) && !empty($switches[2][$i]) )
					{
						$this->_vars($switches[2][$i]);
						$res .= '<?php if (' . $switches[2][$i] . '){ ?>';
					}
					break;
				case 'ELSEIF':
					if ( isset($switches[2][$i]) && !empty($switches[2][$i]) )
					{
						$this->_vars($switches[2][$i]);
						$res .= '<?php }elseif(' . $switches[2][$i] . '){ ?>';
						break;
					}
				case 'ELSE':
					$res .= '<?php }else{ ?>';
					break;
				case 'ENDIF':
					$res .= '<?php } ?>';
					break;

				// code & file inclusion
				case 'INCLUDE':
					if ( isset($switches[2][$i]) && ($switches[2][$i] = trim($switches[2][$i])) )
					{
						$this->_vars($switches[2][$i], false);
						$parms = array_map('trim', explode(',', $switches[2][$i]));
						foreach ( $parms as $idx => $parm )
						{
							if ( !preg_match('#^\$#', $parm) )
							{
								$parms[$idx] = '\'' . $parm . '\'';
							}
						}
						$res .= '<?php $this->parse(' . $parms[0] . ($nest_level ? ', true' : '') . (isset($parms[2]) ? ', ' . $parms[1] . ', ' . $parms[2] : '') . '); ?>';
					}
					break;
				case 'PHP':
					if ( isset($switches[2][$i]) && ($switches[2][$i] = trim($switches[2][$i])) )
					{
						$this->_vars($switches[2][$i]);
						$res .= '<?php ' . $switches[2][$i] . ' ?>';
					}
					else if ( !empty($phps) && ($php = array_shift($phps)) )
					{
						$res .= '<?php ' . $php . ' ?>';
					}
					break;
				case 'FUNC':
					if ( count($functions) && ($func = trim(array_shift($functions))) )
					{
						$res .= $func;
					}
					break;

				// an unknown switch
				default:
					if ( !empty($switches[0][$i]) )
					{
						$this->_vars($switches[0][$i], true);
						$res .= $switches[0][$i];
					}
					break;
			}
			if ( $switch )
			{
				unset($switches[2][$i]);
				unset($switches[1][$i]);
				unset($switches[0][$i]);
			}
		}
		return preg_replace('#[\n\r\s\t]+\?\>[\n\r\s\t]*\<\?php[\n\r\s\t]+#is', $lf, $res);
	}

	// private: replace all vars with their ref, with or without the code for printing
	function _vars(&$text, $print=false)
	{
		if ( strpos($text, '{') !== false )
		{
			$text = preg_replace('#\{([a-z0-9_\.]+?)\}#ie', $print ? '$this->_ref_print(\'$1\')' : '$this->_ref(\'$1\')', $text);
		}
	}

	// private: turn vars into refs with the code for printing
	function _ref_print($var)
	{
		$ref = $this->_ref($var);
		return '<?php if(isset(' . $ref . ')){echo ' . $ref . ';} ?>';
	}

	// private: turn a var into its ref
	function _ref($var)
	{
		$ref = '$d';
		$last = false;
		if ( !empty($var) )
		{
			if ( !is_array($var) )
			{
				$var = explode('.', $var);
				$last = array_pop($var);
			}
			if ( ($count_var = count($var)) )
			{
				for ( $i = 0; $i < $count_var; $i++ )
				{
					$ref .= '[\'' . $var[$i] . '\'][' . $ref . '[\'' . $var[$i] . '__IDX\']]';
				}
			}
		}
		return $last === false ? $ref : $ref . '[\'' . $last . '\']';
	}

	// private: turn functions into their call
	function _ref_func($func_name, $func_parms='')
	{
		return '<?php if(isset($f[\'' . $func_name . '\'])){echo call_user_func($f[\'' . $func_name . '\']' . (trim($func_parms) == '' ? '' : ', ' . $func_parms) . ');} ?>';
	}
}

?>