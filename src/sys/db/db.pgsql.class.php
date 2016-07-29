<?php
//
//	file: sys/db/db.pgsql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 15/06/2007
//	version: 0.0.1.CH - 25/11/2007
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

//
// /!\ minimal requirement: php 4.1.0
//

class sys_db_pgsql extends sys_db
{
	var $id;
	var $in_transaction;
	var $last_inserted_table;
	var $affected_rows;
	var $version;
	var $can;

	function __construct()
	{
		parent::__construct();
		$this->id = false;
		$this->in_transaction = false;
		$this->last_inserted_table = false;
		$this->affected_rows = false;
		$this->version = false;
		$this->can = false;
	}

	function __destruct()
	{
		$this->close();

		unset($this->can);
		unset($this->version);
		unset($this->affected_rows);
		unset($this->last_inserted_table);
		unset($this->in_transaction);
		unset($this->id);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->layer = 'pgsql';
		$this->dbi_layer = 'pgsql';
	}

	// connect the database
	function open($table_prefix, $internal_prefix, $dbhost, $dbuser, $dbpasswd, $dbname=false, $dbpersistency=false)
	{
		if ( version_compare(PHP_VERSION, '4.1.0', '<') )
		{
			trigger_error('err_db_pgsql_php_too_low', E_USER_ERROR);
		}

		// build the connection string
		$connect = array();
		if ( $dbuser )
		{
			$connect[] = 'user=' . $dbuser;
		}
		if ( $dbpasswd )
		{
			$connect[] = 'password=' . $dbpasswd;
		}
		if ( $dbhost && ($dbhost != 'localhost') )
		{
			$matches = array();
			preg_match('#^(.*?)(?:\:([0-9]+))?$#i', $dbhost, $matches);
			unset($matches[0]);
			$matches = array_map('trim', $matches);
			$connect[] = 'host=' . $matches[1];
			if ( isset($matches[2]) && $matches[2] )
			{
				$connect[] = 'port=' . $matches[2];
			}
		}
		if ( $dbname )
		{
			$connect[] = 'dbname=' . $dbname;
		}
		$connect = empty($connect) ? '' : implode(' ', $connect);

		// attempt the connection
		$this->id = $dbpersistency ? @pg_pconnect($connect) : @pg_connect($connect);
		if ( !$this->id )
		{
			$this->id = false;
			return false;
		}

		// store basic def
		$this->name = $dbname;
		$this->prefix = $table_prefix;
		$this->internal_prefix = $internal_prefix;

		// reset status var
		$this->in_transaction = false;
		$this->last_inserted_table = false;
		$this->affected_rows = false;

		// database capabilities
		// linked to php version
		$this->can = array(
			// php 4.2
			'pg_query' => function_exists('pg_query'),
			'pg_affected_rows' => function_exists('pg_affected_rows'),
			'pg_free_result' => function_exists('pg_free_result'),
			'pg_last_error' => function_exists('pg_last_error'),

			// php 4.3
			'pg_fetch_assoc' => function_exists('pg_fetch_assoc'),
		);

		// linked to db server version
		$this->version = $this->_get_version();
		$this->can += array(
			// postgresql 8.2
			'mass_insert' => version_compare($this->version, '8.2.0', '>='),
		);
		return true;
	}

	function close()
	{
		$this->last_inserted_table = false;
		$this->affected_rows = false;
		if ( $this->id !== false )
		{
			if ( $this->in_transaction )
			{
				// rollback
				$this->end_transaction(true);
			}
			@pg_close($this->db);
			$this->id = false;
		}
	}

	function start_transaction()
	{
		$result = false;
		if ( !$this->in_transaction )
		{
			$sql = 'BEGIN TRANSACTION';
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
			if ( $rollback )
			{
				$this->affected_rows = false;
			}
		}
		return $result !== false;
	}

	function query($sql, $line=false, $file=false, $break_on_error=true)
	{
		$this->affected_rows = false;
		$this->last_inserted_table = false;
		if ( ($this->id === false) || !($sql = trim($sql)) )
		{
			return false;
		}

		// deal with limit
		if ( preg_match('#LIMIT#i', $sql) )
		{
			$sql = preg_replace('#([\n\r\s\t])LIMIT[\n\r\s\t]+([0-9]+)[,\n\r\s\t]+([0-9]+)#is', '$1LIMIT $3 OFFSET $2', $sql);
		}
		$update = preg_match('#(INSERT|UPDATE|DELETE|TRUNCATE)#i', $sql);

		// insert ?
		$matches = array();
		if ( $update && preg_match('#^INSERT[\n\r\s\t]+INTO[\n\r\s\t]+(.*?)[\n\r\s\t]#i', $sql, $matches) )
		{
			$this->last_inserted_table = trim($matches[1]);

			// check if we are inserting many rows
			$matches = array();
			if ( !empty($this->stack) && preg_match('#' . sprintf(preg_quote($this->mass_values_mask, '#'), '([0-9]+)') . '#', $sql, $matches) )
			{
				$stack_id = intval($matches[1]) - 1;
				unset($matches);
				$this->affected_rows = $this->_mass_insert($sql, $stack_id, $file, $line, $break_on_error);
				if ( $this->affected_rows === false )
				{
					$this->last_inserted_table = false;
				}
				return $this->affected_rows !== false;
			}
		}
		unset($matches);

		// straight
		$result = $this->_query($sql);
		if ( $result === false )
		{
			$this->last_inserted_table = false;
			$this->affected_rows = false;
			$error = $this->error();
			$this->trigger_error($line, $file, $error['code'], $error['message'], $break_on_error, $sql);
		}
		if ( $update )
		{
			$this->affected_rows = $this->_affected_rows($result);
		}
		return $result;
	}

	// parms: $sql, $field, $line=false, $file=false, $break_on_error=true, $alpha=false
	function sub_query($sql)
	{
		return $sql;
	}

	function affected_rows()
	{
		return $this->affected_rows;
	}

	function fetch($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : ($this->can && $this->can['pg_fetch_assoc'] ? @pg_fetch_assoc($query_id) : @pg_fetch_array($query_id, null, PGSQL_ASSOC));
	}

	function next_id()
	{
		$next_id = false;
		if ( ($this->id !== false) && ($this->last_inserted_table !== false) )
		{
			$sql = 'SELECT currval(' . $this->escape((string) $this->last_inserted_table . '_id_seq') . ') AS currval_id_seq';
			if ( ($result = $this->_query($sql)) )
			{
				$next_id = ($row = $this->fetch($result)) ? intval($row['currval_id_seq']) : false;
				$this->free($result);
			}
		}
		return $next_id;
	}

	function free($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : ($this->can && $this->can['pg_free_result'] ? @pg_free_result($query_id) : @pg_freeresult($query_id));
	}

	function error()
	{
		return array(
			'code' => -1,
			'message' => @pg_errormessage($this->id),
		);
	}

	// private
	function _query(&$sql)
	{
		return $this->can && $this->can['pg_query'] ? @pg_query($this->id, $sql) : @pg_exec($this->id, $sql);
	}

	function _affected_rows($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : ($this->can && $this->can['pg_affected_rows'] ? @pg_affected_rows($query_id) : @pg_cmdtuples($query_id));
	}

	function _get_version()
	{
		$version = false;
		if ( !$version && function_exists('pg_parameter_status') )
		{
			$version = ($version = @pg_parameter_status($this->id, 'server_version')) ? $version : false;
		}
		if ( !$version && function_exists('pg_version') )
		{
			// we assume server version and client version are enough close regarding the major version (7/8)
			if ( ($version = ($version = @pg_version($this->id)) && is_array($version) ? $version : false) )
			{
				$version = isset($version['server_version']) && !empty($version['server_version']) ? $version['server_version'] : (isset($version['client']) && !empty($version['client']) ? $version['client'] : false);
			}
		}
		if ( !$version )
		{
			$sql = 'SELECT version() AS pgsql_version';
			if ( ($result = $this->_query($sql)) )
			{
				$matches = array();
				$version = ($row = $this->fetch($result)) && isset($row['pgsql_version']) && !empty($row['pgsql_version']) && preg_match('#[\.0-9]+#', $row['pgsql_version'], $matches) ? $matches[0] : false;
				$this->free($result);
			}
		}
		return $version ? $version : '7.0.0';
	}

	// map values for mass INSERT
	function _map_values_stack(&$fields)
	{
		if ( $this->can && $this->can['mass_insert'] )
		{
			return implode('),
						(', array_map(array(&$this, '_map_values'), $fields));
		}
		return parent::_map_values_stack($fields);
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
}

?>