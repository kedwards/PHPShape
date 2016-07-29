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

class sys_utf8_normalizer_wrapper extends sys_utf8_normalizer
{
	// turn str into its nfc form
	function nfc(&$str)
	{
		if ( !empty($str) )
		{
			$this->decompose_sort($str, 'normal.nfd'); // nfd()
			$this->recompose($str);
		}
	}

	// turn str into its nfd form
	function nfd(&$str)
	{
		if ( !empty($str) )
		{
			$this->decompose_sort($str, 'normal.nfd');
		}
	}

	// turn str into its nfkc form
	function nfkc(&$str)
	{
		if ( !empty($str) )
		{
			$this->decompose_sort($str, 'normal.nfkd'); // nfkd()
			$this->recompose($str);
		}
	}

	// turn str into its nfkd form
	function nfkd(&$str)
	{
		if ( !empty($str) )
		{
			$this->decompose_sort($str, 'normal.nfkd');
		}
	}

	// check if a char is nfc. "maybe" will result in false
	function is_nfc($str)
	{
		// if empty or pure ASCII, nothing to do
		if ( empty($str) || !preg_match('#[^\x09\x0a\x0d\x20-\x7f]#', $str) )
		{
			return true;
		}
		$done_comb = $done_nfc_qc = false;

		$last_class = 0;
		$i = 0;
		while ( isset($str[$i]) )
		{
			// get the char
			$c0 = ord($str[$i]);
			$k = $i + 1;
			if ( $c0 < 0xc0 )
			{
				if ( ($c0 > 0x7e) || (($c0 < 0x20) && ($c0 != 0x09) && ($c0 != 0x0a) && ($c0 != 0x0d)) )
				{
					return false;
				}
				$c = $str[$i++];
			}
			else if ( $c0 < 0xc2 )
			{
				return false;
			}
			else if ( $c0 < 0xe0 )
			{
				if ( !isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf) )
				{
					return false;
				}
				$c = $str[$i++] . $str[$i++];
			}
			else if ( $c0 < 0xf0 )
			{
				if ( !isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf) ||
					!isset($str[$k]) || !($c2 = ord($str[$k++])) || ($c2 < 0x80) || ($c2 > 0xbf) ||
					(($c0 == 0xe0) && ($c1 < 0xa0)) || (($c0 == 0xed) && ($c1 > 0x9f))
				)
				{
					return false;
				}
				$c = $str[$i++] . $str[$i++] . $str[$i++];
			}
			else if ( $c0 < 0xf4 )
			{
				if ( !isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf) ||
					!isset($str[$k]) || !($c2 = ord($str[$k++])) || ($c2 < 0x80) || ($c2 > 0xbf) ||
					!isset($str[$k]) || !($c3 = ord($str[$k++])) || ($c3 < 0x80) || ($c3 > 0xbf) ||
					(($c0 == 0xf0) && ($c1 < 0x90)) || (($c0 == 0xf4) && ($c1 > 0x8f))
				)
				{
					return false;
				}
				$c = $str[$i++] . $str[$i++] . $str[$i++] . $str[$i++];
			}
			else
			{
				return false;
			}

			// if in combiner char & not ordered, return false
			if ( !$done_comb && ($done_comb = true) )
			{
				$this->load_chart('normal.comb_class');
			}
			if ( ($class = ($c0 >= $this->lowers['normal.comb_class']) && isset($this->data['normal.comb_class'][$c]) ? $this->data['normal.comb_class'][$c] : 0) && ($last_class > $class) )
			{
				return false;
			}
			$last_class = $class;

			// if in nfc quick check chart (no or maybe nfc), return false
			if ( !$done_nfc_qc && ($done_nfc_qc = true) )
			{
				$this->load_chart('normal.nfc_qc');
			}
			if ( ($c0 >= $this->lowers['normal.nfc_qc']) && isset($this->data['normal.nfc_qc'][$c]) )
			{
				return false;
			}
		}
		return true;
	}

	// decompose and sort a string depending the chart: nfd/nfkd
	function decompose_sort(&$str, $chart)
	{
		$hangul_sbase_uni = 0xac00;
		$hangul_sbase = "\xea\xb0\x80"; // 0xac00
		$hangul_slast = "\xed\x9e\xa3"; // 0xd7a3: SBase + (SCount = LCount * NCount)
		$hangul_tcount = 28;
		$hangul_ncount = 588; // VCount * TCount

		$this->load_chart($chart);
		$this->load_chart('normal.comb_class');

		$combiners = array();
		$res = '';
		$i = 0;
		while ( isset($str[$i]) )
		{
			// get the char and verify the utf-8 conformance
			$c0 = ord($str[$i]);
			$error = false;
			$k = $i + 1;
			if ( $c0 < 0xc0 )
			{
				$c_len = 1;
				if ( !($error = ($c0 > 0x7e) || (($c0 < 0x20) && ($c0 != 0x09) && ($c0 != 0x0a) && ($c0 != 0x0d))) )
				{
					$c = $str[$i++];
				}
			}
			else if ( $c0 < 0xc2 )
			{
				$c_len = 2;
				$error = true;
			}
			else if ( $c0 < 0xe0 )
			{
				$c_len = 2;
				if ( !($error = !isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf)) )
				{
					$c = $str[$i++] . $str[$i++];
				}
			}
			else if ( $c0 < 0xf0 )
			{
				$c_len = 3;
				if ( !($error =
					!isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf) ||
					!isset($str[$k]) || !($c2 = ord($str[$k++])) || ($c2 < 0x80) || ($c2 > 0xbf) ||
					(($c0 == 0xe0) && ($c1 < 0xa0)) || (($c0 == 0xed) && ($c1 > 0x9f))
				) )
				{
					$c = $str[$i++] . $str[$i++] . $str[$i++];
				}
			}
			else if ( $c0 < 0xf4 )
			{
				$c_len = 4;
				if ( !($error =
					!isset($str[$k]) || !($c1 = ord($str[$k++])) || ($c1 < 0x80) || ($c1 > 0xbf) ||
					!isset($str[$k]) || !($c2 = ord($str[$k++])) || ($c2 < 0x80) || ($c2 > 0xbf) ||
					!isset($str[$k]) || !($c3 = ord($str[$k++])) || ($c3 < 0x80) || ($c3 > 0xbf) ||
					(($c0 == 0xf0) && ($c1 < 0x90)) || (($c0 == 0xf4) && ($c1 > 0x8f))
				) )
				{
					$c = $str[$i++] . $str[$i++] . $str[$i++] . $str[$i++];
				}
			}
			else
			{
				$error = true;
				$c_len = $c0 < 0xf8 ? 4 : ($c0 < 0xfc ? 5 : ($c0 < 0xfe ? 6 : 1));
			}
			if ( $error )
			{
				$i += $c_len;
				$c_len = 3;
				$c = "\xef\xbf\xbd"; // fffd
			}

			// from there c is a valid utf-8 char
			if ( $c_len > 1 )
			{
				// we have the decomposition
				if ( ($c0 >= $this->lowers[$chart]) && isset($this->data[$chart][$c]) )
				{
					$c = $this->data[$chart][$c];
				}
				// hangul decomposition: SBase starts with 0xea & SBase + SLast starts with 0xed
				else if ( ($c0 >= 0xea) && ($c0 <= 0xed) && ($c >= $hangul_sbase) && ($c <= $hangul_slast) )
				{
					// to codepoint then to index
					$index = ((($c0 & 0x0f) << 12) | (($c1 & 0x3f) << 6) | ($c2 & 0x3f)) - $hangul_sbase_uni;

					// LVT
					if ( ($t = $index % $hangul_tcount) )
					{
						if ( $t < 25 )
						{
							$c = "\xe1\x84\xff\xe1\x85\xff\xe1\x86\xff";
							$c[8] = chr(0xa7 + $t);
						}
						else
						{
							$c = "\xe1\x84\xff\xe1\x85\xff\xe1\x87\xff";
							$c[8] = chr(0x67 + $t); // 0x80 - 25
						}
					}
					// LV
					else
					{
						$c = "\xe1\x84\xff\xe1\x85\xff";
					}
					$c[5] = chr(0xa1 + intval(($index % $hangul_ncount) / $hangul_tcount));
					$c[2] = chr(0x80 + intval($index / $hangul_ncount));
				}
			}

			// sort the char
			if ( $c_len > 1 )
			{
				$j = 0;
				while ( isset($c[$j]) )
				{
					// get the char
					$cc0 = ord($c[$j]);
					if ( $cc0 < 0x80 )
					{
						$cc_len = 1;
						$cc = $c[$j++];
					}
					else if ( $cc0 < 0xe0 )
					{
						$cc_len = 2;
						$cc = $c[$j++] . $c[$j++];
					}
					else if ( $cc0 < 0xf0 )
					{
						$cc_len = 3;
						$cc = $c[$j++] . $c[$j++] . $c[$j++];
					}
					else
					{
						$cc_len = 4;
						$cc = $c[$j++] . $c[$j++] . $c[$j++] . $c[$j++];
					}

					// combining char
					if ( ($cc_len > 1) && ($cc0 >= $this->lowers['normal.comb_class']) && isset($this->data['normal.comb_class'][$cc]) )
					{
						$class = $this->data['normal.comb_class'][$cc];
						$combiners[$class] = isset($combiners[$class]) ? $combiners[$class] . $cc : $cc;
					}
					// not a combiner
					else
					{
						if ( !empty($combiners) )
						{
							ksort($combiners);
							$res .= implode('', $combiners);
							$combiners = array();
						}
						$res .= $cc;
					}
				}
			}

			// deal with one digit char
			if ( $c_len == 1 )
			{
				if ( !empty($combiners) )
				{
					ksort($combiners);
					$res .= implode('', $combiners);
					$combiners = array();
				}
				$res .= $c;
			}
		}
		if ( !empty($combiners) )
		{
			ksort($combiners);
			$res .= implode('', $combiners);
		}
		$str = $res;
	}

	function recompose(&$str)
	{
		$hangul_sbase_uni = 0xac00;
		$hangul_sbase = "\xea\xb0\x80"; // 0xac00
		$hangul_slast = "\xed\x9e\xa3"; // 0xd7a3: SBase + (SCount = LCount * NCount)
		$hangul_tcount = 28;
		$hangul_ncount = 588; // VCount * TCount
		$hangul_lbase = "\xe1\x84\x80"; // 0x1100
		$hangul_llast = "\xe1\x84\x92"; // 0x1112: LBase + LCount - 1 (19)
		$hangul_vbase = "\xe1\x85\xa1"; // 0x1161
		$hangul_vlast = "\xe1\x85\xb5"; // 0x1175: VBase + VCount - 1
		$hangul_tbase = "\xe1\x86\xa7"; // 0x11a7
		$hangul_tlast = "\xe1\x87\x82"; // 0x11c2: TBase + TCount - 1

		$this->load_chart('normal.comb_class');
		$this->load_chart('normal.nfc');

		$last_class = -1;
		$last_hangul = false;
		$start_char = '';
		$combining = '';

		$res = '';
		$i = 0;
		while ( isset($str[$i]) )
		{
			// get the char
			$c0 = ord($str[$i]);
			if ( $c0 < 0x80 )
			{
				$c_len = 1;
				$c = $str[$i++];
			}
			else if ( $c0 < 0xe0 )
			{
				$c_len = 2;
				$c = $str[$i++] . $str[$i++];
			}
			else if ( $c0 < 0xf0 )
			{
				$c_len = 3;
				$c = $str[$i++] . $str[$i++] . $str[$i++];
			}
			else
			{
				$c_len = 4;
				$c = $str[$i++] . $str[$i++] . $str[$i++] . $str[$i++];
			}

			// ascii
			if ( $c_len == 1 )
			{
				$res .= $start_char . $combining;
				$start_char = $c;
				$combining = '';
				$last_class = 0;
				continue;
			}

			// combining char
			$pair = $start_char . $c;
			if ( ($c0 >= $this->lowers['normal.comb_class']) && isset($this->data['normal.comb_class'][$c]) )
			{
				$class = $this->data['normal.comb_class'][$c];
				if ( ($start_char !== '') && ($class > 0) && ($last_class < $class) && (ord($start_char[0]) >= $this->lowers['normal.nfc']) && isset($this->data['normal.nfc'][$pair]) )
				{
					$start_char = $this->data['normal.nfc'][$pair];
					$class = 0;
				}
				else
				{
					$combining .= $c;
				}
				$last_class = $class;
				$last_hangul = false;
				continue;
			}

			// start char
			if ( $last_class == 0 )
			{
				// in normal form
				if ( (ord($start_char[0]) >= $this->lowers['normal.nfc']) && isset($this->data['normal.nfc'][$pair]) )
				{
					$start_char = $this->data['normal.nfc'][$pair];
					$last_hangul = false;
					continue;
				}

				// maybe an hangul ? if v | t, start with 0xe1
				if ( $c0 == 0xe1 )
				{
					// case 1: start=l, c=v
					if ( ($c >= $hangul_vbase) && ($c <= $hangul_vlast) && ($start_char >= $hangul_lbase) && ($start_char <= $hangul_llast) )
					{
						$l = ord($start_char[2]) - 0x80;
						$v = ord($c[2]) - 0xa1;
						$cp = $hangul_sbase_uni + $hangul_ncount * $l + $hangul_tcount * $v;

						// hardcode the limited-range UTF-8 conversion:
						$start_char = chr(0xe0 | (($cp >> 12) & 0x0f)) . chr(0x80 | (($cp >> 6) & 0x3f)) . chr(0x80 | ($cp & 0x3f));
						$last_hangul = false;
						continue;
					}
					// case 2: start=lv (so on its final range, SBase), c=t
					if ( !$last_hangul && ($c >= $hangul_tbase) && ($c <= $hangul_tlast) && ($start_char >= $hangul_sbase) && ($start_char <= $hangul_slast) )
					{
						$t = ord($c[2]);
						$t -= $t < 0xa7 ? 0x67 : 0xa7;
						$s2 = ord($start_char[2]) + $t;
						if ( $s2 > 0xbf )
						{
							$s2 -= 0x40;
							$s1 = ord($start_char[1]) + 1;
							if ( $s1 > 0xbf )
							{
								$s1 -= 0x40;
								$s0 = ord($start_char[0]) + 1;
								$start_char[0] = chr($s0);
							}
							$start_char[1] = chr($s1);
						}
						$start_char[2] = chr($s2);

						// if there's another jamo char after this, *don't* try to merge it.
						$last_hangul = true;
						continue;
					}
				}
			}
			// end of class
			$res .= $start_char . $combining;
			$start_char = $c;
			$combining = '';
			$last_class = 0;
			$last_hangul = false;
		}
		$res .= $start_char . $combining;
		$str = $res;
	}
}

?>