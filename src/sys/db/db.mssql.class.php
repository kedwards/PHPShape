<?php
//
//	file: sys/db/db.mssql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 10/06/2007
//	version: 0.0.1.CH - 24/11/2007
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_db_mssql extends sys_db
{
	var $id;
	var $in_transaction;
	var $version;
	var $affected_rows;
	var $can;

	function __construct()
	{
		parent::__construct();
		$this->id = false;
		$this->in_transaction = false;
		$this->version = false;
		$this->affected_rows = false;
		$this->can = false;
	}

	function __destruct()
	{
		$this->close();

		unset($this->can);
		unset($this->affected_rows);
		unset($this->version);
		unset($this->in_transaction);
		unset($this->id);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);
		$this->layer = 'mssql';
		$this->dbi_layer = 'mssql';
	}

	// connect the database
	function open($table_prefix, $internal_prefix, $dbhost, $dbuser, $dbpasswd, $dbname=false, $dbpersistency=false)
	{
		$this->id = $dbpersistency ? @mssql_pconnect($dbhost, $dbuser, $dbpasswd) : @mssql_connect($dbhost, $dbuser, $dbpasswd);
		if ( !$this->id )
		{
			$this->id = false;
			return false;
		}
		$dbname = trim($dbname);
		if ( $dbname !== '' )
		{
			$dbselect = @mssql_select_db($dbname, $this->id);
			if ( !$dbselect )
			{
				@mssql_close($this->id);
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
		$this->affected_rows = false;

		// database capabilities
		$this->can = array(
			'mssql_fetch_assoc' => function_exists('mssql_fetch_assoc'),
		);
		$this->version = $this->_get_version();
		return true;
	}

	function close()
	{
		$this->affected_rows = false;
		if ( $this->id )
		{
			if ( $this->in_transaction )
			{
				// rollback
				$this->end_transaction(true);
			}
			@mssql_close($this->id);
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
			if ( !$result && !$rollback )
			{
				$rollback = true;
				$sql = 'ROLLBACK';
				$result = $this->_query($sql);
			}
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
		if ( ($this->id === false) || !($sql = trim($sql)) )
		{
			return false;
		}

		// deal with limit
		$matches = array();
		if ( preg_match('#LIMIT#i', $sql) && preg_match('#^SELECT(?:([\n\r\s\t]+DISTINCT)[\n\r\s\t]+)?(.*?)LIMIT[\n\r\s\t]+([0-9]+)[,\n\r\s\t]*([0-9]+)?#is', $sql, $matches) )
		{
			unset($matches[0]);
			$count = intval($matches[3]);
			$offset = isset($matches[4]) ? intval($matches[4]) : 0;
			$sql = 'SELECT' . $matches[1] . ' TOP ' . intval($count + $offset) . ' ' . trim($matches[2]);
			$result = $this->_query($sql);
			if ( ($result !== false) && $offset )
			{
				@mssql_data_seek($result, $offset);
			}
			return $result;
		}

		// deal with updates
		$matches = array();
		$update = preg_match('#^(INSERT|UPDATE|DELETE|TRUNCATE)[\n\r\s\t]+#i', $sql, $matches);
		unset($matches[0]);

		// insert ?
		if ( $update && (strtoupper($matches[1]) == 'INSERT') )
		{
			// check if we are inserting many rows
			$matches = array();
			if ( !empty($this->stack) && preg_match('#' . sprintf(preg_quote($this->mass_values_mask, '#'), '([0-9]+)') . '#', $sql, $matches) )
			{
				$stack_id = intval($matches[1]) - 1;
				unset($matches);
				$this->affected_rows = $this->_mass_insert($sql, $stack_id, $file, $line, $break_on_error);
				return $this->affected_rows !== false;
			}
		}
		unset($matches);

		// straight
		$result = $this->_query($sql);
		if ( $result === false )
		{
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
		return ($query_id === false) || ($query_id === true) ? false : ($this->can && $this->can['mssql_fetch_assoc'] ? @mssql_fetch_assoc($query_id) : @mssql_fetch_array($query_id, MSSQL_ASSOC));
	}

	function next_id()
	{
		$next_id = false;
		if ( $this->id !== false )
		{
			$sql = 'SELECT SCOPE_IDENTITY() AS next_id';
			if ( ($result = $this->_query($sql)) )
			{
				$next_id = ($row = $this->fetch($result)) && isset($row['next_id']) ? intval($row['next_id']) : false;
				$this->free($result);
			}
		}
		return $next_id;
	}

	function free($query_id)
	{
		return ($query_id === false) || ($query_id === true) ? false : @mssql_free_result($query_id);
	}

	function error()
	{
		$code = false;
		$message = @mssql_get_last_message();

		// retrieve error no
		$sql = 'SELECT @@ERROR AS code';
		if ( ($result = $this->_query($sql)) )
		{
			$code = ($row = $this->fetch($result)) && isset($row['code']) ? intval($row['code']) : false;
			$this->free($result);
		}
		if ( $code !== false )
		{
			// retrieve full message
			$sql = 'SELECT CAST(description AS varchar(255)) AS message
						FROM master.dbo.sysmessages
						WHERE error = ' . intval($code);
			if ( ($result = $this->_query($sql)) )
			{
				$message .= ($row = $this->fetch($result)) && isset($row['message']) && !empty($row['message']) ? '<br />' . $row['message'] : '';
				$this->free($result);
			}
		}
		return array(
			'code' => $code === false ? -1 : $code,
			'message' => $message,
		);
	}

	// private
	function _query(&$sql)
	{
		return @mssql_query($sql, $this->id);
	}

	function _get_version()
	{
		$version = false;
		$sql = 'SELECT SERVERPROPERTY(\'ProductVersion\') AS server_version';
		if ( ($result = $this->_query($sql)) )
		{
			$version = ($row = $this->fetch($result)) && isset($row['server_version']) && !empty($row['server_version']) ? $row['server_version'] : false;
			$this->free($result);
		}
		return $version;
	}

	function _affected_rows($query_id)
	{
		$affected_rows = false;
		if ( ($query_id !== false) && ($query_id !== true) )
		{
			$sql = 'SELECT ROWCOUNT_BIG() AS affected_rows';
			if ( ($result = $this->_query($sql)) )
			{
				$affected_rows = ($row = $this->fetch($result)) && isset($row['affected_rows']) ? intval($row['affected_rows']) : false;
				$this->free($result);
			}
		}
		return $affected_rows;
	}
}

?>