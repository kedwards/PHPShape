<?php
//
//	file: sys/utf8/utf8.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/07/2007
//	version: 0.0.3 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_utf8_normalizer_native extends sys_utf8_normalizer
{
	// turn str into its nfc form
	function nfc(&$str)
	{
		if ( !empty($str) )
		{
			$str = Normalizer::normalize($str, Normalizer::FORM_C);
		}
	}

	// turn str into its nfd form
	function nfd(&$str)
	{
		if ( !empty($str) )
		{
			$str = Normalizer::normalize($str, Normalizer::FORM_D);
		}
	}

	// turn str into its nfkc form
	function nfkc(&$str)
	{
		if ( !empty($str) )
		{
			$str = Normalizer::normalize($str, Normalizer::FORM_KC);
		}
	}

	// turn str into its nfkd form
	function nfkd(&$str)
	{
		if ( !empty($str) )
		{
			$str = Normalizer::normalize($str, Normalizer::FORM_KD);
		}
	}

	// check if a string is nfc
	function is_nfc($str)
	{
		return empty($str) || Normalizer::isNormalized($str, Normalizer::FORM_C);
	}
}

?>