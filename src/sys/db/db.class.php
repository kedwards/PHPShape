<?php
//
//	file: sys/db/db.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 14/11/2007
//	version: 0.0.2 - 25/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_db extends sys_stdclass
{
	var $layer;
	var $dbi_layer;
	var $name;
	var $prefix;
	var $internal_prefix;
	var $stack;
	var $mass_values_mask;
	var $per_turn;

	function __construct()
	{
		parent::__construct();
		$this->layer = false;
		$this->dbi_layer = false;
		$this->name = false;
		$this->prefix = false;
		$this->internal_prefix = false;
		$this->stack = array();

		// constants
		$this->mass_values_mask = '{__[%s]__}';
		$this->per_turn = 150;
	}

	function __destruct()
	{
		unset($this->per_turn);
		unset($this->mass_values_mask);
		unset($this->stack);
		unset($this->internal_prefix);
		unset($this->prefix);
		unset($this->name);
		unset($this->dbi_layer);
		unset($this->layer);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->layer = false;
		$this->dbi_layer = false;
	}

	// static: return the class matching dbms & load the layer
	function get_layer($dbms=false)
	{
		$sys = &$GLOBALS[SYS];
		$ini = array(
			'mssql' => array('file' => 'db.mssql.class', 'class' => 'sys_db_mssql'),
			'mssql-odbc' => array('file' => 'db.mssql_odbc.class', 'class' => 'sys_db_mssql_odbc'),
			'mysql' => array('file' => 'db.mysql.class', 'class' => 'sys_db_mysql'),
			'mysql4' => array('file' => 'db.mysql.class', 'class' => 'sys_db_mysql'),
			'postgres' => array('file' => 'db.pgsql.class', 'class' => 'sys_db_pgsql'),
		);
		if ( !$dbms )
		{
			return array_keys($ini);
		}
		if ( !isset($ini[$dbms]) )
		{
			return false;
		}
		if ( !class_exists($ini[$dbms]['class']) )
		{
			include($sys->path . 'db/' . $ini[$dbms]['file'] . $sys->ext);
		}
		return $ini[$dbms]['class'];
	}

	function table($name, $prefix=false)
	{
		if ( is_array($name) )
		{
			$prefix = isset($name[1]) ? $name[1] : false;
			$name = $name[0];
		}
		return (($prefix === false) || ($prefix === $this->internal_prefix) ? $this->prefix : $this->prefix . $prefix) . $name;
	}

	// private: format and trigger the error
	function trigger_error($line, $file, $code, $message, $break_on_error=false, $sql=false)
	{
		trigger_error($sql ?
				sys_error::sprintf('err_db_error_sql', $file ? basename($file) : '', $line, $code, $message, preg_replace('#[\n\r\s\t]+#', ' ', $sql)) :
				sys_error::sprintf('err_db_error', $file ? basename($file) : '', $line, $code, array($message)),
			$break_on_error ? E_USER_ERROR : E_USER_NOTICE);
		return false;
	}

	// private: fill run stats reports
	function _rstats($elapsed, $from_cache, $explain=false)
	{
		$sys = &$GLOBALS[SYS];
		if ( isset($sys->rstats) && is_object($sys->rstats) )
		{
			// global result
			if ( !($rstats = $sys->rstats->pop('db')) )
			{
				$rstats = array('db.count' => 0, 'db.elapsed' => 0, 'cache.count' => 0, 'cache.elapsed' => 0);
			}
			$channel = $from_cache ? 'cache' : 'db';
			$rstats[$channel . '.count'] += 1;
			$rstats[$channel . '.elapsed'] += $elapsed;
			$sys->rstats->register('db', $rstats);

			// details
			if ( $sys->rstats->debug )
			{
				$explain['elapsed'] = $elapsed;
				$sys->rstats->register('db.debug', $explain);
			}
		}
	}

	function query($sql, $line=false, $file=false, $break_on_error=true)
	{
		if ( !$this->id || !($sql = trim($sql)) )
		{
			return false;
		}

		// mass insert ?
		$matches = array();
		if ( !empty($this->stack) && preg_match('#^INSERT[\n\r\s\t]#i', $sql) && preg_match('#' . sprintf(preg_quote($this->mass_values_mask, '#'), '([0-9]+)') . '#', $sql, $matches) )
		{
			$stack_id = intval($matches[1]) - 1;
			unset($matches);
			$affected_rows = $this->_mass_insert($sql, $stack_id, $line, $file, $break_on_error);
			return $affected_rows === false ? false : true;
		}
		else
		{
			unset($matches);
			$explain = $this->_explain($sql, $line, $file);
			$start = sys::microtime();
			$result = $this->_query($sql);
			$this->_rstats(sys::microtime() - $start, false, $explain);
			if ( $result === false )
			{
				$error = $this->error();
				$this->trigger_error($line, $file, $error['code'], $error['message'], $break_on_error, $sql);
			}
			return $result;
		}
	}

	// mass insert for database without multi row insertion capability
	function _mass_insert($sql, $stack_id, $line, $file, $break_on_error)
	{
		$affected_rows = 0;
		$mask = sprintf($this->mass_values_mask, $stack_id + 1);

		// check if we have some values to process
		if ( !isset($this->stack[$stack_id]) || !count($this->stack[$stack_id]) )
		{
			$affected_rows = false;
			if ( isset($this->stack[$stack_id]) )
			{
				unset($this->stack[$stack_id]);
			}
			$sql = str_replace($mask, '', $sql);
			$this->trigger_error($line, $file, -1, 'err_db_no_values', $break_on_error, $sql);
			return $affected_rows;
		}

		// launch one query per fields row
		$error = false;
		if ( !($in_transaction = $this->in_transaction) )
		{
			$this->start_transaction();
		}
		$start = sys::microtime();
		$done = $explain = false;
		foreach ( $this->stack[$stack_id] as $idx => $fields )
		{
			unset($this->stack[$stack_id][$idx]);

			$sql_atom = str_replace($mask, $this->_map_values($fields), $sql);
			if ( !$done && ($done = true) )
			{
				$explain = $this->_explain($sql_atom, $line, $file);
				$start = sys::microtime();
			}
			$result = $this->_query($sql_atom);
			if ( $result === false )
			{
				$affected_rows = false;
				$error = $this->error();
				$this->trigger_error($line, $file, $error['code'], $error['message'], $break_on_error, $sql_atom);
				break;
			}
			else if ( $result !== true )
			{
				$affected_rows += $this->_affected_rows($result);
			}
		}
		$this->_rstats(sys::microtime() - $start, false, $explain);
		if ( !$in_transaction )
		{
			// rollback on error
			$this->end_transaction($error !== false);
		}
		unset($this->stack[$stack_id]);
		return $affected_rows;
	}

	// sub-query: parms: $sql, $field, $line=false, $file=false, $break_on_error=true, $alpha=false
	function sub_query($sql)
	{
		return $sql;
	}

	// cast a value for sql usage
	function escape($value, $quotes=true, $null='')
	{
		$quotes = $quotes ? '\'' : '';
		return
			is_string($value) ? $quotes . $this->escape_string($value) . $quotes : (
			is_float($value) ? doubleval($value) : (
			is_integer($value) || is_bool($value) ? intval($value) : (
			is_null($value) ? ($null ? $null : 'NULL') : (
			'\'\''
		))));
	}

	// escape a string for sql usage: close to what does mysql_escape_string()
	function escape_string($value)
	{
		return empty($value) ? '' : strtr(addslashes((string) $value), array('\\\'' => '\'\'', '\\"' => '"', "\x1a" => "\\\x1a"));
	}

	// map fields & values
	function fields($mode, $fields)
	{
		if ( !empty($fields) )
		{
			switch ( $mode )
			{
				case 'fields':
					return implode(', ', isset($fields[0]) ? (is_array($fields[0]) ? array_keys($fields[0]) : $fields) : array_keys($fields));

				case 'values':
					return isset($fields[0]) && is_array($fields[0]) ? $this->_map_values_stack($fields) : $this->_map_values($fields);

				case 'update':
					return implode(', ', array_map(array(&$this, '_map_update'), array_keys($fields), $fields));
			}
		}
		return '';
	}

	// private: map values for INSERT
	function _map_values($fields)
	{
		return implode(', ', array_map(array(&$this, 'escape'), $fields));
	}

	// private: map values for mass INSERT (for databases without the mass insert support)
	function _map_values_stack($fields)
	{
		$this->stack[] = $fields;
		return sprintf($this->mass_values_mask, count($this->stack));
	}

	function _map_update($field, $value)
	{
		return $field . ' = ' . $this->escape($value);
	}

	// private: explain a request
	function _explain($sql, $line, $file)
	{
		$res = array(
			'sql' => $sql,
			'line' => $line,
			'file' => $file,
		);
		if ( ($sql = $this->_get_explain_request($sql)) )
		{
			$start = sys::microtime();
			$res['explain'] = array();
			$result = $this->_query($sql);
			while ( ($row = $this->fetch($result)) )
			{
				$res['explain'][] = $row;
			}
			$this->free($result);
			$res['explain.elapsed'] = sys::microtime() - $start;
		}
		return $res;
	}

	// parms: $sql
	function _get_explain_request()
	{
		return false;
	}
}

?>