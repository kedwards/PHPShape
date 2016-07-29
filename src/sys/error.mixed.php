<?php
//
//	file: sys/error.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 01/08/2007
//	version: 0.0.2 - 25/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_error extends sys_stdclass
{
	var $warnings;
	var $notices;

	function __construct()
	{
		parent::__construct();
		$this->warnings = false;
		$this->notices = false;
	}

	function __destruct()
	{
		if ( !isset($this->api_name) )
		{
			return false;
		}
		restore_error_handler();
		unset($this->notices);
		unset($this->warnings);
		return parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);

		// php accepts method as callback, so let it point directly to the appropriate method
		if ( version_compare(PHP_VERSION, '4.3.0', '>=') )
		{
			// nb.: the error type filter has been introduced with php 5.0.0
			if ( version_compare(PHP_VERSION, '5.0.0', '>=') )
			{
				set_error_handler(array(&$this, 'handle'), E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
			}
			else
			{
				set_error_handler(array(&$this, 'handle'));
			}
		}
		// php < 4.3.0: we need to go through a function
		else
		{
			set_error_handler('sys_error_handler');
		}
	}

	function handle($errno, $errmsg, $file, $line)
	{
		switch ( $errno )
		{
			// errors in sys apis: SQL request failing ie
			case E_USER_ERROR:
				$this->send($errno, $this->str2array($errmsg), $file, $line);
				die();

			// warnings: cumulative errors
			case E_USER_WARNING:
				if ( !is_array($this->warnings) )
				{
					$this->warnings = array();
				}
				$this->warnings[] = array(
					'code' => $errno,
					'msg' => $this->str2array($errmsg),
					'file' => $file,
					'line' => $line,
				);
				break;

			case E_USER_NOTICE:
				if ( !is_array($this->notices) )
				{
					$this->notices = array();
				}
				$this->notices[] = array(
					'code' => $errno,
					'msg' => $this->str2array($errmsg),
					'file' => $file,
					'line' => $line,
				);
				break;

			// for php < 5.0.0 : fake the system messages
			default:
				// we don't want this error to pop if outside our scope
				if ( !(error_reporting() & $errno) )
				{
					return true;
				}

				// these constants may be missing depending the php version, so use vars in place to declare our array
				$e_strict = defined('E_STRICT') ? E_STRICT : 2048; // php 5.0.0
				$e_recoverable_error = defined('E_RECOVERABLE_ERROR') ? E_RECOVERABLE_ERROR : 4096; // php 5.2.0

				// define system errors
				$system_errors = array(
					E_ERROR => array('txt' => 'E_ERROR', 'die' => true),
					E_WARNING => array('txt' => 'E_WARNING', 'die' => false),
					E_PARSE => array('txt' => 'E_PARSE', 'die' => true),
					E_NOTICE => array('txt' => 'E_NOTICE', 'die' => false),
					E_CORE_ERROR => array('txt' => 'E_CORE_ERROR', 'die' => true),
					E_CORE_WARNING => array('txt' => 'E_CORE_WARNING', 'die' => false),
					E_COMPILE_ERROR => array('txt' => 'E_COMPILE_ERROR', 'die' => true),
					$e_strict => array('txt' => 'E_STRICT', 'die' => false),
					$e_recoverable_error => array('txt' => 'E_RECOVERABLE_ERROR', 'die' => true),
				);
				$errmsg = isset($system_errors[$errno]) ? array($system_errors[$errno]['txt'], $errmsg, $file ? basename($file) : '?', $line ? $line : '?') : array('err_error_unknown_sysmsg', $errno, $file ? basename($file) : '?', $line ? $line : '?', $errmsg);

				// die message
				if ( !isset($system_errors[$errno]) || $system_errors[$errno]['die'] )
				{
					$this->send($errno, $errmsg, $file, $line);
					die();
				}

				// warning
				$this->warnings[] = array(
					'code' => $errno,
					'msg' => $errmsg,
					'file' => $file,
					'line' => $line,
				);
				break;
		}
		return true;
	}

	// display errors
	function send($errno, $errmsg, $file=false, $line=false)
	{
		// check the environment
		$sys = false;

		// check if the environment is set. If not, instanciate one: this should only occurs if the $GLOBALS[SYS] var is destroyed manually.
		if ( ($sys_created = !isset($GLOBALS[SYS]) || !is_object($GLOBALS[SYS]) || !isset($GLOBALS[SYS]->api_name)) )
		{
			// get path: we assume $sys->path = $sys->root . 'sys/' or similar: this will be used to target the lang files
			$path = dirname(__FILE__);
			$sep = substr(__FILE__, strlen($path), 1);
			$root = preg_replace('#' . preg_quote($sep, '#') . '+#', $sep, implode($sep, array_slice(explode($sep, $path), 0, -1)) . $sep);
			$path = preg_replace('#' . preg_quote($sep, '#') . '+#', $sep, $path . $sep);

			// create a sys object, no requester, skiping the set of the server status and forcing the path
			sys::factory(false, $root, false, $path, true);
			$sys = &$GLOBALS[SYS];
			$sys->set_context();

			// force the message
			$errmsg = array('err_error_exception_msg', $errno, $file ? basename($file) : '', $line, $errmsg);
		}
		else
		{
			$sys = &$GLOBALS[SYS];
		}

		// we have now sys: use it
		$tpl = &$sys->tpl;

		// get the call list
		if ( function_exists('_backtrace') )
		{
			_backtrace();
		}

		// send the error
		$tpl->add('warnings', array(
			'WARNINGS' => 'error_error',
		));
		$tpl->add('warnings.warning', array(
			'NO' => $errno,
			'MSG' => $errmsg,
			'TRANSLATE' => is_array($errmsg),
			'LINE' => $line ? (int) $line : '',
			'FILE' => $file ? basename($file) : '',
		));

		// if sys was created by a standard script, use the creator display method
		if ( $sys->api_name && isset($GLOBALS[$sys->api_name]) && method_exists($GLOBALS[$sys->api_name], 'display') )
		{
			$api = &$GLOBALS[$sys->api_name];
			$api->display($sys->ini_get('tpl.file.errors' . ($tpl->xml ? '.xml' : '')));
		}
		else
		{
			$this->send_warnings(true);
			$tpl->send_headers();
			$tpl->parse($sys->ini_get('tpl.file.errors' . ($tpl->xml ? '.xml' : '')));
		}

		// destroy the system instances if created there
		if ( $sys_created )
		{
			sys::kill($GLOBALS[SYS]);
			unset($GLOBALS[SYS]);
		}
		exit;
	}

	function send_warnings($additional=false)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( $this->warnings && ($count_warnings = count($this->warnings)) )
		{
			$tpl->add('warnings', array(
				'WARNINGS' => $additional ? 'error_warnings_additional' : 'error_warnings',
			));
			for ( $i = 0; $i < $count_warnings; $i++ )
			{
				$tpl->add('warnings.warning', array(
					'MSG' => $this->warnings[$i]['msg'],
					'TRANSLATE' => is_array($this->warnings[$i]['msg']),
				));
			}
		}
	}

	// turn a list of parms into a string
	// Used to send a string when using trigger_error() for message with parms (sprintf() expected) as trigger_error() doesn't accept array
	// - entry: list of parms
	// - return: string
	function sprintf()
	{
		return ($args = func_get_args()) && !isset($args[1]) ? (is_array($args[0]) ? serialize($args[0]) : $args[0]) : serialize($args);
	}

	// this one reverse error::sprintf() result
	function str2array($str)
	{
		if ( is_string($str) && isset($str[1]) && ($str[1] == ':') )
		{
			$ary = unserialize($str);
			if ( $ary !== false )
			{
				$str = $ary;
			}
		}
		return is_array($str) ? $str : array($str);
	}
}

// if php < 4.3.0, we have to call the error handler method
function sys_error_handler($errno, $errmsg=false, $file=false, $line=false, $context=false)
{
	// we trigger an error, but the environment is missing
	if ( !isset($GLOBALS[SYS]) || !is_object($GLOBALS[SYS]) || !isset($GLOBALS[SYS]->api_name) || !isset($GLOBALS[SYS]->error) || !is_object($GLOBALS[SYS]->error) || !isset($GLOBALS[SYS]->error->api_name) )
	{
		$error = new sys_error();
		$error->send($errno, $errmsg, $file, $line);
		sys::kill($error);
		unset($error);
		die();
	}

	// other call: send the message
	$sys = &$GLOBALS[SYS];
	$error = &$sys->error;
	return $error->handle($errno, $errmsg, $file, $line, $context);
}

?>