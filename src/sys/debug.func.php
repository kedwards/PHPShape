<?php
//
//	file: sys/debug.func.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 13/11/2007
//	version: 0.0.2 - 25/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

//
// debug functions
//

// dump a var or a set of vars
// - entry: list(var 1, var 2, ...)
function _dump()
{
	$args = func_get_args();
	$message = count($args) == 1 ? $args[0] : $args;
	unset($args);

	$lang = array(
		'dbg_title' => '_dump() in <strong>%s</strong> on line <strong>%s</strong>',
		'dbg_empty' => 'Empty %s',
	);
	$title = class_exists('sys_rstats') && ($dbg = sys_rstats::debug_backtrace()) ? sprintf($lang['dbg_title'], $dbg[0]['file'], $dbg[0]['line']) : false;
	unset($dbg);

	echo '<pre style="background-color: #ffffff; color: #000000; border: 1px; border-style: outset; padding: 5px; text-align: left; overflow: auto; font: Arial; font-size: 12px;">' . ($title ? $title . '<br />' : '');
	if ( is_null($message) )
	{
		echo 'NULL';
	}
	else if ( is_bool($message) )
	{
		echo $message ? 'TRUE' : 'FALSE';
	}
	else if ( is_numeric($message) )
	{
		echo $message;
	}
	else if ( empty($message) )
	{
		echo sprintf($lang['dbg_empty'], gettype($message));
	}
	else if ( is_array($message) || is_object($message) )
	{
		ob_start();
		print_r($message);
		$content = ob_get_contents();
		ob_end_clean();
		echo htmlspecialchars($content);
	}
	else
	{
		echo str_replace("\t", '&nbsp; &nbsp;', htmlspecialchars($message));
	}
	echo '</pre>';
}

// report a trace point
function _backtrace($title=false, $retrieve=false)
{
	static $done, $traces;

	$trace = array(
		'tick' => sys::microtime(),
		'title' => $title ? $title : ($done ? '' : 'Start'),
		'file' => '',
		'line' => '',
		'calls' => array(),
	);
	if ( !$done && ($done = true) )
	{
		$traces = array($trace);
	}

	// we are initializing the sys object and want to retrieve the _backtrace() already done
	if ( $retrieve )
	{
		$res = $traces;
		$traces = array();
		return $res;
	}

	// get the calls line
	if ( ($backtrace = class_exists('sys_rstats') ? sys_rstats::debug_backtrace() : (function_exists('debug_backtrace') ? debug_backtrace() : false)) )
	{
		$call = array_shift($backtrace);
		$trace['file'] = $call['file'];
		$trace['line'] = $call['line'];

		$count_backtrace = count($backtrace);
		for ( $i = 0; $i < $count_backtrace; $i++ )
		{
			// let's try to avoid to store full objects
			if ( isset($backtrace[$i]['args']) && $backtrace[$i]['args'] )
			{
				$count_args = count($backtrace[$i]['args']);
				for ( $j = 0; $j < $count_args; $j++ )
				{
					if ( is_object($backtrace[$i]['args'][$j]) )
					{
						$backtrace[$i]['args'][$j] = get_class($backtrace[$i]['args'][$j]);
					}
					else if ( is_array($backtrace[$i]['args'][$j]) && isset($backtrace[$i]['args'][$j][0]) && is_object($backtrace[$i]['args'][$j][0]) )
					{
						$backtrace[$i]['args'][$j][0] = get_class($backtrace[$i]['args'][$j][0]);
					}
				}
			}
			$call = array(
				'file' => $backtrace[$i]['file'],
				'line' => $backtrace[$i]['line'],
				'function' => (isset($backtrace[$i]['class']) ? $backtrace[$i]['class'] . '::' : '') . $backtrace[$i]['function'],
				'args' => isset($backtrace[$i]['args']) && $backtrace[$i]['args'] ? $backtrace[$i]['args'] : null,
			);
			$trace['calls'][] = $call;
		}
	}

	// store the result
	if ( isset($GLOBALS[SYS]) && is_object($GLOBALS[SYS]) && isset($GLOBALS[SYS]->rstats) && is_object($GLOBALS[SYS]->rstats) )
	{
		$GLOBALS[SYS]->rstats->register('script.debug', $trace);
		$done = true;
	}
	else
	{
		$traces[] = $trace;
	}
	return true;
}

?>