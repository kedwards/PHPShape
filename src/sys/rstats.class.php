<?php
//
//	file: sys/rstats.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 02/06/2007
//	version: 0.0.3 - 18/08/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

// our run stats report object
class sys_rstats extends sys_stdclass
{
	var $debug;
	var $data;

	function __construct()
	{
		parent::__construct();
		$this->debug = false;
		$this->data = array();
	}

	function __destruct()
	{
		unset($this->data);
		unset($this->debug);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		if ( function_exists('_backtrace') )
		{
			$this->debug = true;
			$this->data['script.debug'] = _backtrace(false, true);
		}
	}

	function register($type, $data=false)
	{
		if ( !isset($this->data[$type]) )
		{
			$this->data[$type] = array();
		}
		if ( $data && !isset($data['tick']) )
		{
			$data['tick'] = sys::microtime();
		}
		$this->data[$type][] = $data;
	}

	function pop($type)
	{
		return !isset($this->data[$type]) || empty($this->data[$type]) ? false : array_pop($this->data[$type]);
	}

	// static: get the call list if possible
	function debug_backtrace()
	{
		static $exists, $done;
		if ( !$done && ($done = true) )
		{
			$exists = function_exists('debug_backtrace');
		}
		if ( !$exists )
		{
			return false;
		}
		$dbg = debug_backtrace();

		// remove this call
		array_shift($dbg);
		return $dbg;
	}

	// display the run stats reports
	function display($tpl_name)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		// scrit
		if ( isset($this->data['script']) && $this->data['script'] )
		{
			$tpl->add(array(
				'SCRIPT_TIME' => sys::microtime() - $this->data['script'][0]['tick'],
			));
		}
		// database
		if ( isset($this->data['db']) && $this->data['db'] )
		{
			$tpl->add(array(
				'DB_TIME' => $this->data['db'][0]['db.elapsed'],
				'DB_COUNT' => $this->data['db'][0]['db.count'],
			));
			// database caches
			if ( $this->data['db'][0]['cache.count'] )
			{
				$tpl->add(array(
					'DB_CACHE_TIME' => $this->data['db'][0]['cache.elapsed'],
					'DB_CACHE_COUNT' => $this->data['db'][0]['cache.count'],
				));
			}
		}

		// debug infos
		if ( $this->debug )
		{
			// backtraces
			if ( isset($this->data['script.debug']) && $this->data['script.debug'] && ($count_script = count($this->data['script.debug'])) && ($count_script > 1) )
			{
				$time = $this->data['script.debug'][0]['tick'];
				$base = $time;
				for ( $i = 1; $i < $count_script; $i++ )
				{
					$tpl->add('script_debug', array(
						'TITLE' => $this->data['script.debug'][$i]['title'] ? $this->data['script.debug'][$i]['title'] : '',
						'FILE' => $this->data['script.debug'][$i]['file'] ? basename($this->data['script.debug'][$i]['file']) : '',
						'LINE' => $this->data['script.debug'][$i]['line'] ? $this->data['script.debug'][$i]['line'] : '',
						'TICK' => $this->data['script.debug'][$i]['tick'] - $base,
						'ELAPSED' => $time !== false ? $this->data['script.debug'][$i]['tick'] - $time : 0,
					));
					if ( isset($this->data['script.debug'][$i]['calls']) && $this->data['script.debug'][$i]['calls'] && ($count_calls = count($this->data['script.debug'][$i]['calls'])) )
					{
						for ( $j = 0; $j < $count_calls; $j++ )
						{
							$tpl->add('script_debug.calls', array(
								'FILE' => $this->data['script.debug'][$i]['calls'][$j]['file'] ? basename($this->data['script.debug'][$i]['calls'][$j]['file']) : '??',
								'LINE' => $this->data['script.debug'][$i]['calls'][$j]['line'] ? $this->data['script.debug'][$i]['calls'][$j]['line'] : '??',
								'FUNCTION' => $this->data['script.debug'][$i]['calls'][$j]['function'],
							));
						}
					}
					$time = $this->data['script.debug'][$i]['tick'];
				}
			}

			// database
			if ( isset($this->data['db.debug']) && $this->data['db.debug'] )
			{
				foreach ( $this->data['db.debug'] as $row )
				{
					$tpl->add('db_debug', array(
						'SQL' => sys_string::htmlspecialchars(preg_replace('#[\n\r\s\t]+#', ' ', $row['sql'])),
						'LINE' => $row['line'],
						'FILE' => $row['file'] ? basename($row['file']) : '',
						'ELAPSED' => $row['elapsed'],
					));

					// we have explainations ?
					if ( isset($row['explain']) && $row['explain'] )
					{
						// get first all headers
						$headers = array();
						foreach ( $row['explain'] as $explains )
						{
							if ( $explains )
							{
								$headers = empty($headers) ? array_flip(array_keys($explains)) : array_merge($headers, array_flip(array_keys($explains)));
							}
						}
						if ( $headers )
						{
							// send headers
							foreach ( $headers as $title => $dummy )
							{
								$tpl->add('db_debug.explain', array(
									'TITLE' => $title,
								));
							}

							// send explain rows
							foreach ( $row['explain'] as $explain )
							{
								$tpl->add('db_debug.row');
								foreach ( $headers as $title => $dummy )
								{
									$tpl->add('db_debug.row.cell', array(
										'VALUE' => isset($explain[$title]) ? sys_string::htmlspecialchars($explain[$title]) : '',
									));
								}
							}
						}
					}
				}
			}
		}
		$tpl->parse($tpl_name);
	}
}

?>