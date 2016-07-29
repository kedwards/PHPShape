<?php
//
//	file: sys/db/db.mysql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 10/06/2007
//	version: 0.0.1.CH - 24/11/2007
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}
//
// /!\ minimal requirement: mysql 3.23
//

class sys_db_mysql extends sys_db
{
	var $id;
	var $in_transaction;
	var $version;
	var $can;

	function __construct()
	{
		parent::__construct();
		$this->id = false;
		$this->in_transaction = false;
		$this->version = false;
		$this->can = false;
	}

	function __destruct()
	{
		$this->close();

		unset($this->can);
		unset($this->version);
		unset($this->in_transaction);
		unset($this->id);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->layer = 'mysql';
		$this->dbi_layer = 'mysql';
	}

	// connect the database
	function open($table_prefix, $internal_prefix, $dbhost, $dbuser, $dbpasswd, $dbname=false, $dbpersistency=false)
	{
		$this->id = $dbpersistency ? @mysql_pconnect($dbhost, $dbuser, $dbpasswd) : @mysql_connect($dbhost, $dbuser, $dbpasswd);
		if ( !$this->id )
		{
			$this->id = false;
			return false;
		}
		$dbname = trim($dbname);
		if ( $dbname !== '' )
		{
			$dbselect = @mysql_select_db($dbname);
			if ( !$dbselect )
			{
				@mysql_close($this->id);
				$this->id = false;
				return false;
			}
		}

		// store basic def
		$this->name = $dbname;
		$this->prefix = $table_prefix;
		$this->internal_prefix = $internal_prefix;

		// reset status var
		$this->in_transaction = false;

		// database capabilities
		$this->version = $this->_get_version();
		if ( $this->version && version_compare($this->version, '3.23.0', '<') )
		{
			trigger_error('err_db_mysql_too_low', E_USER_ERROR);
		}
		$this->can = array(
			'sub_queries' => $this->version && version_compare($this->version, '4.1.0', '>=') ? true : false,
			'start_transaction' => $this->version && version_compare($this->version, '4.0.11', '>=') ? true : false,
			'real_escape' => function_exists('mysql_real_escape_string'),
		);
		if ( isset($GLOBALS[SYS]) && isset($GLOBALS[SYS]->utf8) && is_object($GLOBALS[SYS]->utf8) )
		{
			$sql = 'SET NAMES \'utf8\'';
			$this->_query($sql);
		}
		return true;
	}

	function close()
	{
		if ( $this->id !== false )
		{
			if ( $this->in_transaction )
			{
				// rollback
				$this->end_transaction(true);
			}
			@mysql_close($this->id);
			$this->id = false;
		}
	}

	function start_transaction()
	{
		$result = false;
		if ( !$this->in_transaction )
		{
			$sql = $this->can && $this->can['start_transaction'] ? 'START TRANSACTION' : 'BEGIN';
			if ( ($result = $this->_query($sql)) )
			{
				$this->in_transaction = true;
			}
		}
		return $result !== false;
	}

	function end_transaction($rollback=false)
	{
		$result = false;
		if ( $this->in_transaction )
		{
			$this->in_transaction = false;
			$sql = $rollback ? 'ROLLBACK' : 'COMMIT';
			$result = $this->_query($sql);
		}
		return $result !== false;
	}

	function query($sql, $line=false, $file=false, $break_on_error=true)
	{
		if ( ($this->id === false) || !($sql = trim($sql)) )
		{
			return false;
		}
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

	function sub_query($sql, $line=false, $file=false, $break_on_error=true, $alpha=false)
	{
		if ( $this->can['sub_queries'] )
		{
			return $sql;
		}

		// this version does not support sub-queries
		$ids = array();
		$sql = trim($sql);
		$result = $this->query($sql, $line, $file, $break_on_error);
		$field = false;
		while ( ($row = $this->fetch($result)) )
		{
			if ( ($field === false) )
			{
				$field = key($row);
			}
			$ids[] = $alpha ? $this->escape($row[$field]) : intval($row[$field]);
		}
		$this->free($result);
		return empty($ids) ? 'NULL' : implode(', ', $ids);
	}

	function fetch($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : mysql_fetch_array($query_id, MYSQL_ASSOC);
	}

	function free($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : mysql_free_result($query_id);
	}

	function affected_rows()
	{
		return mysql_affected_rows($this->id);
	}

	function next_id()
	{
		return mysql_insert_id($this->id);
	}

	function error()
	{
		return array(
			'code' => mysql_errno($this->id),
			'message' => mysql_error($this->id),
		);
	}

	// private
	function _query(&$sql)
	{
		return @mysql_query($sql, $this->id);
	}

	// get server version to get the database capabilities
	function _get_version()
	{
		return function_exists('mysql_get_server_info') ? @mysql_get_server_info($this->id) : '3.23.17';
	}

	// map values for mass INSERT
	function _map_values_stack(&$fields)
	{
		return implode('),
					(', array_map(array(&$this, '_map_values'), $fields));
	}

	// request to run to get the explainations
	function _get_explain_request(&$sql)
	{
		$matches = array();
		if ( preg_match('#^SELECT[\n\r\s\t]+#i', $sql) )
		{
			return 'EXPLAIN ' . $sql;
		}
		else if ( preg_match('#^UPDATE[\n\r\s\t]+([a-z0-9_\-]+).*?WHERE(.*)#is', $sql, $matches) )
		{
			return 'EXPLAIN SELECT * FROM ' . $matches[1] . ' WHERE ' . $matches[2];
		}
		else if ( preg_match('#^DELETE[\n\r\s\t]+FROM[\n\r\s\t]+([a-z0-9_\-]+).*?WHERE(.*)#is', $sql, $matches) )
		{
			return 'EXPLAIN SELECT * FROM ' . $matches[1] . ' WHERE ' . $matches[2];
		}
		return false;
	}

	// escape a string for sql usage
	function escape_string($value)
	{
		if ( empty($value) || !$this->id || !$this->can['real_escape'] )
		{
			return parent::escape_string($value);
		}
		return mysql_real_escape_string((string) $value, $this->id);
	}
}

?>