<?php
//
//	file: sys/utf8/utf8.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/07/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_utf8 extends sys_stdclass
{
	var $locale;
	var $normalizer;

	function __construct()
	{
		parent::__construct();
		$this->locale = false;
		$this->normalizer = false;
	}

	function __destruct()
	{
		unset($this->normalizer);
		unset($this->locale);
		parent::__destruct();
	}

	// set locale is used for strtolower/upper
	// i18n context: this one should be used to qualify strtoupper/lower
	// it can be (according to unicode 5.0) 'az' (Azerbaijani), 'tr' (Turkish), 'lt' (Lithuanian)
	function set_locale($locale=false)
	{
		$this->locale = $locale && in_array($locale, array('az', 'tr', 'lt')) ? $locale : false;
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];

		parent::set($api_name);

		// force php to work with utf-8
		ini_set('default_charset', 'UTF-8');

		// limit strtolower/upper() to ascii chars only
		setlocale(LC_CTYPE, 'C');

		// pathes to tools
		$ini = array(
			'normalizer.native' => array('file' => 'utf8.normalizer.native.class', 'class' => 'sys_utf8_normalizer_native'),
			'normalizer.wrapper' => array('file' => 'utf8.normalizer.wrapper.class', 'class' => 'sys_utf8_normalizer_wrapper'),
		);

		// since PHP 5.3.0, normalizer class should be available
//		_dump(array('ext/intl' => extension_loaded('intl'), 'class Normalizer' => class_exists('Normalizer')));
		$normalizer_mode = 'normalizer.' . (class_exists('Normalizer') ? 'native' : 'wrapper');
		$class = $ini[$normalizer_mode]['class'];
		if ( !class_exists($class) )
		{
			include($sys->path . 'utf8/' . $ini[$normalizer_mode]['file'] . $sys->ext);
		}
		$this->normalizer = new $class();
		$this->normalizer->set($this->api_name);
	}

	// static: guess the appropriate class & load the layer
	function get_layer()
	{
		$sys = &$GLOBALS[SYS];
		$ini = array(
			'native' => array('file' => 'utf8.native.class', 'class' => 'sys_utf8_native'),
			'mbstring' => array('file' => 'utf8.mbstring.class', 'class' => 'sys_utf8_mbstring'),
			'wrapper' => array('file' => 'utf8.wrapper.class', 'class' => 'sys_utf8_wrapper'),
		);

		// with PHP 6, unicode is nativaly supported
		// nb.: due to lack of information, we disable it for now
		$mode = false && version_compare(PHP_VERSION, '6.0.0', '>=') ? 'native' : (extension_loaded('mbstring') ? 'mbstring' : 'wrapper');
		if ( !class_exists($ini[$mode]['class']) )
		{
			include($sys->path . 'utf8/' . $ini[$mode]['file'] . $sys->ext);
		}
		return $ini[$mode]['class'];
	}

	//
	// covered by the layers
	//

	// parms: $str
	function strlen() {}

	// parms: $str, $start, $length=null
	function substr() {}

	//
	// some wrappers
	//

	function strtoupper($str)
	{
		return $this->case_convert($str, 'uppers');
	}

	function strtolower($str)
	{
		return $this->case_convert($str, 'lowers');
	}

	//
	// more advanced methods
	//

	// sanatize a utf-8 string (strip \r, replace all not valid chars with the utf-8 replacement char (0xfffd)
	// based on http://www.w3.org/International/questions/qa-forms-utf-8.en.php
	// nb.: the native php6 or ICU will probably do the normalization nativaly, what will require to move this to the layers
	function normalize($str)
	{
		// strip \r, remove BOM
		if ( !empty($str) )
		{
			$str = str_replace(array("\r\n", "\r", "\xef\xbf\xbe"), array("\n", "\n", ''), $str);
		}
		// normalize the string NFC if not already
		if ( !$this->normalizer->is_nfc($str) )
		{
			$this->normalizer->nfc($str);
		}
		return $str;
	}

	// returned a string identifier: case folded, cleaned up from comfusable chars, NFKC
	// designed to be used as a varchar key with data coming from the user (group name, username, etc.)
	// confusable detection: see http://www.unicode.org/reports/tr39/#Confusable_Detection
	function get_identifier($str)
	{
		if ( !empty($str) )
		{
			// remove case distinction
			$this->case_folding($str);

			// remove confusable
			if ( $this->normalizer->load_chart('confusables') )
			{
				// normalize the string KD if not pure ASCII
				if ( preg_match('#[\x80-\xff]#', $str) )
				{
					$this->normalizer->nfkd($str);
				}
				$str = strtr($str, $this->normalizer->data['confusables']);
			}
			// remove double spaces
			$str = preg_replace('#\s+#', ' ', $str);

			// normalize the string KC if not pure ASCII
			if ( preg_match('#[\x80-\xff]#', $str) )
			{
				$this->normalizer->nfkc($str);
			}
		}
		return $str;
	}

	//
	// casing
	//

	// case folding according to http://www.unicode.org/versions/Unicode5.0.0/ch05.pdf#G21180
	// str: we expect it to be NFC
	// type: simple: simple case folding, else full case folding
	// locale: if 'tr' (Turkish), use the T type (the two locale alternate for I), what we will probably never do
	// return a no more normalized string, but caseless matching type
	function case_folding(&$str, $type=false)
	{
		if ( !empty($str) )
		{
			$chart = 'case.fold' . ($type == 'simple' ? '_s' : '_f');
			if ( !isset($this->normalizer->data[$chart]) && $this->normalizer->load_chart('case.fold_c') && $this->normalizer->load_chart($chart) )
			{
				$this->normalizer->data[$chart] += $this->normalizer->data['case.fold_c'];
				unset($this->normalizer->data['case.fold_c']);
				unset($this->normalizer->lowers['case.fold_c']);
			}
			if ( !empty($this->normalizer->data[$chart]) )
			{
				if ( ($this->locale == 'tr') && $this->normalizer->load_chart('case.fold_t') )
				{
					$str = strtr($str, $this->normalizer->data['case.fold_t']);
				}
				$str = strtr($str, $this->normalizer->data[$chart]);
			}
		}
	}

	// convert a string case in utf-8, make it NFC()
	// mode is 'uppers', 'lowers' or 'titles'
	// locale: according to unicode 5.0: 'az' (Azerbaijani), 'tr' (Turkish), 'lt' (Lithuanian) or false
	// nb.: titles does not take care of word boundaries: it will convert all chars of str if possible
	function case_convert(&$str, $mode)
	{
		if ( !empty($str) && in_array($mode, array('lowers', 'uppers', 'titles')) )
		{
			// we need to do a more advanced process: first normalize NFD (it is supposed to be NFC for now)
			$this->normalizer->nfd($str);

			// check special casing
			$this->special_casing($str, $mode);

			// do the conversion with the simple case
			if ( $this->normalizer->load_chart('case.' . $mode) )
			{
				$str = strtr($str, $this->normalizer->data['case.' . $mode]);
			}

			// let's re-normalize the string to NFC if not pure ASCII
			if ( preg_match('#[\x80-\xff]#', $str) )
			{
				$this->normalizer->nfc($str);
			}
		}
		return $str;
	}

	// special casing according to http://www.unicode.org/versions/Unicode5.0.0/ch03.pdf#G21130
	function special_casing(&$str, $mode)
	{
		$chart = 'case.' . $mode . '.s';
		if ( $this->normalizer->load_chart($chart) )
		{
			if ( !empty($this->normalizer->data[$chart]['*']) )
			{
				foreach ( $this->normalizer->data[$chart]['*'] as $check_type => $letters )
				{
					$checks[$check_type] = $letters;
				}
			}
			if ( $this->locale && isset($this->normalizer->data[$chart][$this->locale]) && !empty($this->normalizer->data[$chart][$this->locale]) )
			{
				foreach ( $this->normalizer->data[$chart][$this->locale] as $check_type => $letters )
				{
					$checks[$check_type] = $letters;
				}
			}
		}
		if ( !empty($checks) )
		{
			foreach ( $checks as $check_type => $letters )
			{
				if ( !empty($letters) )
				{
					if ( ($negate = substr($check_type, 0, 4) == 'Not_') )
					{
						$check_type = substr($check_type, 4);
					}
					if ( $check_type == '*' )
					{
						$str = strtr($str, $letters);
					}
					else if ( ($check_type = 'cc_' . strtolower($check_type)) && method_exists($this, $check_type) )
					{
						$this->$check_type($str, $letters, $negate);
					}
				}
			}
		}
	}

	// get the char at offset and increment offset
	function cc_next_char(&$str, &$offset)
	{
		return !($c0 = ord($str[$offset])) || ($c0 < 0xc0) ? $str[$offset++] : (
			$c0 < 0xe0 ? $str[$offset++] . $str[$offset++] : (
			$c0 < 0xf0 ? $str[$offset++] . $str[$offset++] . $str[$offset++] : (
			$str[$offset++] . $str[$offset++] . $str[$offset++] . $str[$offset++]
		)));
	}

	// get the char at offset and decrement offset
	function cc_previous_char(&$str, &$offset)
	{
		$c = '';
		$found = false;
		while ( !$found && isset($str[$offset]) )
		{
			$c = $str[$offset] . $c;
			$n = ord($str[$offset--]);
			$found = ($n < 0x80) || ($n > 0xbf);
		}
		return $found ? $c : false;
	}

	function cc_chrlen($str)
	{
		$sizes = array(0xc0 => 2, 0xd0 => 2, 0xe0 => 3, 0xf0 => 4);
		$i = 0;
		while ( isset($str[$i]) )
		{
			$c0 = ord($str[$i]) & 0xf0;
			$c_len = !isset($sizes[$c0]) ? 1 : $sizes[$c0];
			$i += $c_len;
		}
		return $i;
	}

	// conditional casing: final sigma case
	// \p{cased}(\p{case_ignorable})*([Char])!((\p{case-ignorable})*\p{cased})
	function cc_final_sigma(&$str, &$letters, $negate)
	{
		foreach ( $letters as $src => $dst )
		{
			// check if we have the src letter, else next letter
			if ( ($i = strpos($str, $src)) === false )
			{
				continue;
			}

			// check if we do the replacement, and where
			$offsets = array();
			$len_src = $this->cc_chrlen($src);
			while ( $i !== false )
			{
				// look forward: we must fail the test: (case ignorable)*cased
				$match = $this->cc_match_cased($str, $i + $len_src, false);
				if ( (!$match && !$negate) || ($match && $negate) )
				{
					// still candidate: look backward: we must pass the test: cased(case ignorable)*
					$match = $this->cc_match_cased($str, $i - 1, true);

					// catch the offset depending the match and negate status
					if ( ($match && !$negate) || (!$match && $negate) )
					{
						$offsets[] = $i;
					}
				}

				// search next candidate
				$i = strpos($str, $src, $i + $len_src);
			}

			// do the replacement
			$this->cc_chr_replace($str, $offsets, $len_src, $dst);
		}
	}

	// conditional casing: match cased(case ignorable)* sequences
	// forward: \p{case-ignorable}*\p{cased}
	// backward: \p{cased}\p{case_ignorable}*
	function cc_match_cased(&$str, $j, $backward)
	{
		if ( !isset($str[$j]) )
		{
			return false;
		}
		if ( !isset($this->normalizer->data['case.id_ignore']) )
		{
			$this->normalizer->load_chart('case.id_ignore');
		}

		// jump over case ignored sequences
		$c = false;
		while ( isset($str[$j]) )
		{
			$c = $backward ? $this->cc_previous_char($str, $j) : $this->cc_next_char($str, $j);
			if ( ($c === false) || !isset($this->normalizer->data['case.id_ignore'][$c]) )
			{
				break;
			}
		}
		if ( $c === false )
		{
			return false;
		}

		// check if the char is cased type
		if ( !isset($this->normalizer->data['case.id_cased']) )
		{
			$this->normalizer->load_chart('case.id_cased');
		}
		return isset($this->normalizer->data['case.id_cased'][$c]);
	}

	// conditional casing: more above
	// ([Char])[^\p{ccc=0}]*[\p{ccc=230}]
	function cc_more_above(&$str, &$letters, $negate)
	{
		foreach ( $letters as $src => $dst )
		{
			$this->cc_match_replace($str, $src, $dst, $negate, false, false, false, true);
		}
	}

	// conditional casing: before dot
	// ([Char])([^\p{ccc=230}\p{ccc=0}])*[\u0307] (cc.87)
	function cc_before_dot(&$str, &$letters, $negate)
	{
		foreach ( $letters as $src => $dst )
		{
			$this->cc_match_replace($str, $src, $dst, $negate, false, false, "\xcc\x87");
		}
	}

	// conditional casing: after soft dotted
	// [\p{Soft_Dotted}]([^\p{ccc=230}\p{ccc=0}])*([Char])
	function cc_after_soft_dotted(&$str, &$letters, $negate)
	{
		foreach ( $letters as $src => $dst )
		{
			$this->cc_match_replace($str, $src, $dst, $negate, true, 'case.id_soft_dotted');
		}
	}

	// conditional case: after I
	// [I]([^\p{ccc=230}\p{ccc=0}])*([Char])
	function cc_after_i(&$str, &$letters, $negate)
	{
		foreach ( $letters as $src => $dst )
		{
			$this->cc_match_replace($str, $src, $dst, $negate, false, false, 'I');
		}
	}

	// match a sequence against
	// - forward: [$chr|c in chart][^\p{ccc=230}\p{ccc=0}]*
	// - backward: [^\p{ccc=230}\p{ccc=0}]*[$chr|c in chart]
	// - match_230: forward: [$chr|c in chart][^\p{ccc=0}]*[\p{ccc=230}]
	// - match 230: backward: [^\p{ccc=0}]*[\p{ccc=230}][$chr|c in chart]
	function cc_match_replace(&$str, $src, $dst, $negate, $backward, $chart_name=false, $chr=false, $match_230=false)
	{
		// check if we have the src letter, else next letter
		if ( ($i = strpos($str, $src)) === false )
		{
			return false;
		}

		// check if we do the replacement, and where
		$offsets = array();
		$len_src = $this->cc_chrlen($src);
		while ( $i !== false )
		{
			$next_chr = $i + $len_src;
			$offset = $backward ? $i - 1 : $next_chr;
			$match = false;
			while ( isset($str[$offset]) )
			{
				// get the char
				if ( ($c = $backward ? $this->cc_previous_char($str, $offset) : $this->cc_next_char($str, $offset)) === false )
				{
					break;
				}

				// if chr granted, test first the char
				if ( ($chr !== false) && ($c == $chr ) )
				{
					$match = true;
					break;
				}

				// maybe it is ccc=0/230
				if ( isset($this->normalizer->data['normal.comb_class']) || $this->normalizer->load_chart('normal.comb_class') )
				{
					if ( !isset($this->normalizer->data['normal.comb_class'][$c]) )
					{
						$match = false;
						break;
					}
					else if ( $this->normalizer->data['normal.comb_class'][$c] == 230 )
					{
						$match = $match_230;
						break;
					}
				}

				// if not chr granted but chart, test chart
				if ( ($chart_name !== false) && (isset($this->normalizer->data[$chart_name]) || $this->normalizer->load_chart($chart_name)) && isset($this->normalizer->data[$chart_name][$c]) )
				{
					$match = true;
					break;
				}
			}

			// catch the offset depending the match and negate status
			if ( ($match && !$negate) || (!$match && $negate) )
			{
				$offsets[] = $i;
			}

			// next candidate
			$i = strpos($str, $src, $next_chr);
		}

		// do the replacement
		return $this->cc_chr_replace($str, $offsets, $len_src, $dst);
	}

	// replace in $str at $offsets chars having len = $len_src with $dst
	function cc_chr_replace(&$str, &$offsets, $len_src, $dst)
	{
		if ( ($count_offsets = count($offsets)) )
		{
			$len_dst = $this->cc_chrlen($dst);
			if ( $len_src == $len_dst )
			{
				for ( $i = 0; $i < $count_offsets; $i++ )
				{
					for ( $j = 0; $j < $len_dst; $j++ )
					{
						$str[ $offsets[$i] + $j ] = $dst[$j];
					}
				}
			}
			else
			{
				$res = '';
				$prev_pos = 0;
				for ( $i = 0; $i < $count_offsets; $i++ )
				{
					$res .= substr($str, $prev_pos, $offsets[$i]) . $dst;
					$prev_pos = $offsets[$i] + $len_src;
				}
				$res .= substr($str, $prev_pos);
				$str = $res;
			}
			return true;
		}
		return false;
	}
}

class sys_utf8_normalizer extends sys_stdclass
{
	var $data;
	var $lowers;

	function __construct()
	{
		parent::__construct();
		$this->data = array();
		$this->lowers = array();
	}

	function __destruct()
	{
		unset($this->lowers);
		unset($this->data);
		parent::__destruct();
	}

	// private: load an unicode 5.0 chart
	function load_chart($chart)
	{
		$sys = &$GLOBALS[SYS];
		if ( !isset($this->data[$chart]) )
		{
			$this->data[$chart] = array();
			if ( !($file = $sys->path . 'utf8/charts/' . $chart . $sys->ext) || !file_exists($file) )
			{
				trigger_error($chart . ' is missing.', E_USER_ERROR);
			}
			include($file);
			if ( !isset($this->lowers[$chart]) )
			{
				$this->lowers[$chart] = 0;
			}
		}
		return !empty($this->data[$chart]);
	}
}

?>