<?php
//
//	file: sys/db/dbi.pgsql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 16/01/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_dbi_pgsql extends sys_dbi
{
	var $recent;

	function __construct()
	{
		parent::__construct();
		$this->recent = false;
	}

	function __destruct()
	{
		unset($this->recent);
		parent::__destruct();
	}

	function supported_types()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$this->numerics = array('tinyint' => 'smallint', 'smallint' => 'smallint', 'mediumint' => 'integer', 'int' => 'integer', 'bigint' => 'bigint', 'decimal' => 'decimal');
		$this->texts = array('char' => 'char', 'varchar' => 'varchar', 'text' => 'text', 'mediumtext' => 'text', 'longtext' => 'text');
		$this->recent = version_compare($db->version, '8.0.0', '>=');
	}

	// tables
	function create_table($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}

		// first search for all sequences
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					if ( isset($field['EXTRA']) && ($field['EXTRA'] == 'auto_increment') )
					{
						$sqls[] = 'CREATE SEQUENCE ' . $db->table($table_name) . '_id_seq';
					}
				}
			}
		}

		// then process the table structure
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			$lines = array();
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				// fields
				foreach ( $table['field'] as $field_name => $field )
				{
					$lines[] = $this->field_line($table_name, $field_name, $this->field_def($field));
				}

				// search for the primary key
				if ( isset($table['INDEX']) && !empty($table['INDEX']) )
				{
					$res = '';
					foreach ( $table['INDEX'] as $index )
					{
						if ( isset($index['FIELD']) && isset($index['TYPE']) && (strtoupper($index['TYPE']) == 'PRIMARY') )
						{
							$res = 'CONSTRAINT ' . $db->table($table_name) . '_pkey PRIMARY KEY (' . implode(', ', array_keys($index['FIELD'])) . ')';
						}
					}
					if ( !empty($res) )
					{
						$lines[] = $res;
					}
				}

				// request
				$sqls[] = 'CREATE TABLE ' . $db->table($table_name) . ' (
	' . implode(',
	', $lines) . '
)';

				// other indexes
				if ( isset($table['INDEX']) && !empty($table['INDEX']) )
				{
					$sqls = array_merge($sqls, $this->create_index_table($table, $table_name, false));
				}
			}
		}
		return $sqls;
	}

	function drop_table($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		foreach ( $items['TABLE'] as $table_name => $dummy )
		{
			$sqls[] = 'DROP TABLE ' . $db->table($table_name);

			// do we have an existing sequence ?
			$sql = 'SELECT currval(' . $db->escape((string) $db->table($table_name) . '_id_seq') . ') AS currval_id_seq';
			$result = $db->query($sql, __LINE__, __FILE__, false);
			if ( $result !== false )
			{
				$db->free($result);
				$sqls[] = 'DROP SEQUENCE ' . $db->table($table_name) . '_id_seq';
			}
			unset($result);
		}
		return $sqls;
	}

	function rename_table($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		foreach ( $items['TABLE'] as $table_name => $new_name )
		{
			$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' RENAME TO ' . $db->table($new_name);
		}
		return $sqls;
	}

	// indexes
	function create_index($items)
	{
		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['INDEX']) && !empty($table['INDEX']) )
			{
				$sqls = array_merge($sqls, $this->create_index_table($table, $table_name, true));
			}
		}
		return $sqls;
	}

	function drop_index($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['INDEX']) && !empty($table['INDEX']) )
			{
				foreach ( $table['INDEX'] as $index_name => $dummy )
				{
					if ( $index_name != 'PRIMARY' )
					{
						$sqls[] = 'DROP INDEX ' . $db->table($table_name) . '_' . $index_name;
					}
					else
					{
						$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' DROP CONSTRAINT ' . $db->table($table_name) . '_pkey';
					}
				}
			}
		}
		return $sqls;
	}

	// fields
	function create_field($items)
	{
		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		return $this->recent ? $this->create_field_new($items) : $this->create_field_old($items);
	}
	function change_field($items)
	{
		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		return $this->recent ? $this->change_field_new($items) : $this->change_field_old($items);
	}

	function create_field_new($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field_def = $this->field_def($field);
					if ( !$field_def['IDENTITY'] )
					{
						$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD COLUMN ' . $this->field_line($table_name, $field_name, $field_def);
					}
				}
			}
		}
		return $sqls;
	}

	function create_field_old($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field_def = $this->field_def($field);
					if ( !$field_def['IDENTITY'] )
					{
						$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD COLUMN ' . $field_name . ' ' . $field_def['TYPE'] . ($field_def['SIZE'] ? '(' . $field_def['SIZE'] . ')' : '');
						if ( isset($field_def['DEFAULT']) )
						{
							$sqls[] = 'UPDATE ' . $db->table($table_name) . ' SET ' . $field_name . ' = ' . $field_def['DEFAULT'] . ' WHERE ' . $field_name . ' IS NULL';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET DEFAULT ' . ($field_def['IS_NUMERIC'] ? '\'' . $field_def['DEFAULT'] . '\'' : $field_def['DEFAULT']);
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET NOT NULL';
						}
					}
				}
			}
		}
		return $sqls;
	}

	function change_field_new($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field_def = $this->field_def($field);
					if ( !$field_def['IDENTITY'] )
					{
						if ( isset($field['NEW_TYPE']) )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' TYPE ' . $field_def['TYPE'] . ($field_def['SIZE'] ? '(' . $field_def['SIZE'] . ')' : '');
						}
						if ( isset($field_def['DEFAULT']) )
						{
							$sqls[] = 'UPDATE ' . $db->table($table_name) . ' SET ' . $field_name . ' = ' . $field_def['DEFAULT'] . ' WHERE ' . $field_name . ' IS NULL';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET DEFAULT ' . ($field_def['IS_NUMERIC'] ? '\'' . $field_def['DEFAULT'] . '\'' : $field_def['DEFAULT']);
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET NOT NULL';
						}
						else if ( isset($field_def['FORCE_NULL']) )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' DROP DEFAULT';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' DROP NOT NULL';
						}
						if ( isset($field['NEW_NAME']) )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' RENAME COLUMN ' . $field_name . ' TO ' . $field['NEW_NAME'];
						}
					}
				}
			}
		}
		return $sqls;
	}

	function change_field_old($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field_def = $this->field_def($field);
					if ( !$field_def['IDENTITY'] )
					{
						$new_field = $old_name = false;
						if ( isset($field['NEW_TYPE']) || (isset($field['NEW_SIZE']) && isset($field_def['SIZE'])) )
						{
							if ( isset($field['NEW_NAME']) )
							{
								$new_field = $field['NEW_NAME'];
								$old_name = $field_name;
							}
							else
							{
								$new_field = $field_name;
								$old_name = $field_name . '_old';
								$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' RENAME COLUMN ' . $new_field . ' TO ' . $old_name;
							}
						}
						if ( $new_field )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD COLUMN ' . $new_field . ' ' . $field_def['TYPE'] . (isset($field_def['SIZE']) ? '(' . $field_def['SIZE'] . ')' : '');
						}
						if ( isset($field_def['DEFAULT']) )
						{
							$sqls[] = 'UPDATE ' . $db->table($table_name) . ' SET ' . $field_name . ' = ' . $field_def['DEFAULT'] . ' WHERE ' . $field_name . ' IS NULL';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET DEFAULT ' . ($field_def['IS_NUMERIC'] ? '\'' . $field_def['DEFAULT'] . '\'' : $field_def['DEFAULT']);
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' SET NOT NULL';
						}
						else if ( isset($field_def['FORCE_NULL']) && !$new_field )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' DROP DEFAULT';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ALTER COLUMN ' . $field_name . ' DROP NOT NULL';
						}
						if ( $new_field )
						{
							$sqls[] = 'UPDATE ' . $db->table($table_name) . ' SET ' . $new_field . ' = ' . $old_name . ' WHERE ' . $old_name . ' IS NOT NULL';
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' DROP COLUMN ' . $old_name;
						}
						else if ( isset($field['NEW_NAME']) )
						{
							$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' RENAME COLUMN ' . $field_name . ' TO ' . $field['NEW_NAME'];
						}
					}
				}
			}
		}
		return $sqls;
	}

	function drop_field($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $dummy )
				{
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' DROP COLUMN ' . $field_name;
				}
			}
		}
		return $sqls;
	}

	// private
	function create_index_table($table, $table_name, $with_primary)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		foreach ( $table['INDEX'] as $index_name => $index )
		{
			if ( isset($index['FIELD']) )
			{
				$type = isset($index['TYPE']) && (strtoupper($index['TYPE']) != 'INDEX') ? strtoupper($index['TYPE']) : '';
				if ( $type != 'PRIMARY' )
				{
					$sqls[] = 'CREATE ' . ($type ? $type . ' ' : '') . 'INDEX ' . $db->table($table_name) . '_' . $index_name . ' ON ' . $db->table($table_name) . ' (' . implode(', ', array_keys($index['FIELD'])) . ')';
				}
				else if ( $with_primary )
				{
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD CONSTRAINT ' . $db->table($table_name) . '_pkey PRIMARY KEY (' . implode(', ', array_keys($index['FIELD'])) . ')';
				}
			}
		}
		return $sqls;
	}

	function field_line($table_name, $field_name, $field_def)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$res = array($field_name, $field_def['TYPE']);
		if ( $field_def['SIZE'] )
		{
			$res[] = '(' . $field_def['SIZE'] . ')';
		}
		if ( $field_def['IDENTITY'] )
		{
			$res[] = 'DEFAULT';
			$res[] = 'nextval(\'' . $db->table($table_name) . '_id_seq\'::text)';
			$res[] = 'NOT NULL';
		}
		else if ( isset($field_def['DEFAULT']) )
		{
			$res[] = 'DEFAULT';
			$res[] = $field_def['IS_NUMERIC'] ? '\'' . $field_def['DEFAULT'] . '\'' : $field_def['DEFAULT'];
			$res[] = ' NOT NULL';
		}
		return implode(' ', $res);
	}

	function field_def($field)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( isset($field['NEW_TYPE']) && !empty($field['NEW_TYPE']) )
		{
			$field['TYPE'] = $field['NEW_TYPE'];
		}
		if ( isset($field['NEW_SIZE']) && !empty($field['NEW_SIZE']) )
		{
			$field['SIZE'] = $field['NEW_SIZE'];
		}
		if ( isset($field['NEW_EXTRA']) && !empty($field['NEW_EXTRA']) )
		{
			$field['EXTRA'] = $field['NEW_EXTRA'];
		}
		if ( isset($field['NEW_DEFAULT']) && !empty($field['NEW_DEFAULT']) )
		{
			$field['DEFAULT'] = $field['NEW_DEFAULT'];
		}
		$res = array();
		$type = sys_string::strtolower($field['TYPE']);
		$res['IDENTITY'] = false;

		// numerics
		if ( isset($this->numerics[$type]) )
		{
			$res['IS_NUMERIC'] = true;
			$res['TYPE'] = $this->numerics[$type];
			if ( isset($field['SIZE']) && !empty($field['SIZE']) && ($type == 'decimal') )
			{
				$size = preg_replace('#[\+\-\n\r\s\t]+#', '', $field['SIZE']);
				if ( !empty($size) )
				{
					$res['SIZE'] = $size;
				}
			}
			if ( isset($field['EXTRA']) && ($field['EXTRA'] == 'auto_increment') )
			{
				$res['IDENTITY'] = true;
			}
			else
			{
				if ( !isset($field['DEFAULT']) )
				{
					$res['DEFAULT'] = $type == 'decimal' ? '0.0' : '0';
				}
				else if ( strtoupper($field['DEFAULT']) != 'NULL' )
				{
					$res['DEFAULT'] = $field['DEFAULT'];
				}
				else
				{
					$res['FORCE_NULL'] = true;
				}
			}
		}

		// alpha-numerics
		else
		{
			$res['IS_NUMERIC'] = false;
			$res['TYPE'] = isset($this->texts[$type]) ? $this->texts[$type] : $type;
			if ( isset($field['SIZE']) && !empty($field['SIZE']) )
			{
				$res['SIZE'] = $field['SIZE'];
			}
			if ( !isset($field['DEFAULT']) || empty($field['DEFAULT']) )
			{
				$field['DEFAULT'] = '';
			}
			else
			{
				$matches = array();
				$field['DEFAULT'] = preg_match('#^["\'](.*)["\']$#is', $field['DEFAULT'], $matches) ? $matches[1] : $field['DEFAULT'];
			}
			if ( strtoupper($field['DEFAULT']) != 'NULL' )
			{
				$res['DEFAULT'] = $db->escape((string) $field['DEFAULT']);
			}
			else
			{
				$res['FORCE_NULL'] = true;
			}
		}
		return $res;
	}
}

?>