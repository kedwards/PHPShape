<?php
//
//	file: sys/db/dbi.mysql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 16/01/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_dbi_mysql extends sys_dbi
{
	function supported_types()
	{
		$this->numerics = array('tinyint' => 'tinyint', 'smallint' => 'smallint', 'mediumint' => 'mediumint', 'int' => 'int', 'bigint' => 'bigint', 'decimal' => 'decimal');
		$this->texts = array('char' => 'char', 'varchar' => 'varchar', 'text' => 'text', 'mediumtext' => 'mediumtext', 'longtext' => 'longtext');
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
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			$lines = array();

			// fields
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$lines[] = $field_name . ' ' . $this->field_def($field);
				}
			}

			// indexes
			if ( isset($table['INDEX']) && !empty($table['INDEX']) )
			{
				foreach ( $table['INDEX'] as $index_name => $index )
				{
					if ( isset($index['FIELD']) )
					{
						$type = isset($index['TYPE']) ? strtoupper($index['TYPE']) : false;
						$lines[] = ($type && ($type != 'INDEX') ? $type . ' ' : '') . 'KEY ' . ($type != 'PRIMARY' ? $index_name . ' ' : '') . '(' . implode(', ', array_keys($index['FIELD'])) . ')';
					}
				}
			}

			// build
			$sqls[] = 'CREATE TABLE ' . $db->table($table_name) . '(
	' . implode(',
	', $lines) . '
)';
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
		$sql = 'DROP TABLE ' . implode(', ', array_map(array(&$db, 'table'), array_keys($items['TABLE'])));
		return array($sql);
	}

	function rename_table($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		$lines = array();
		foreach ( $items['TABLE'] as $table_name => $new_name )
		{
			$lines[] = $db->table($table_name) . ' TO ' . $db->table($new_name);
		}
		$sql = 'RENAME TABLE
	' . implode(',
	', $lines);
		return array($sql);
	}

	// indexes
	function create_index($items)
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
			if ( isset($table['INDEX']) || empty($table['INDEX']) )
			{
				foreach ( $table['INDEX'] as $index_name => $index )
				{
					if ( isset($index['FIELD']) )
					{
						$type = isset($index['TYPE']) && !empty($index['TYPE']) ? strtoupper($index['TYPE']) : 'INDEX';
						$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD ' . $type . ' ' . ($type == 'PRIMARY' ? 'KEY ' : $index_name . ' ') . '(' . implode(', ', array_keys($index['FIELD'])) . ')';
					}
				}
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
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' DROP ' . ($index_name == 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX ' . $index_name);
				}
			}
		}
		return $sqls;
	}

	// fields
	function create_field($items)
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
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' ADD COLUMN ' . $field_name . ' ' . $this->field_def($field);
				}
			}
		}
		return $sqls;
	}

	function change_field($items)
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
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' CHANGE COLUMN ' . $field_name . ' ' . (isset($field['NEW_NAME']) ? $field['NEW_NAME'] : $field_name) . ' ' . $this->field_def($field);
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
				foreach ( $table['FIELD'] as $field_name )
				{
					$sqls[] = 'ALTER TABLE ' . $db->table($table_name) . ' DROP COLUMN ' . $field_name;
				}
			}
		}
		return $sqls;
	}

	// private
	function field_def($field)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( isset($field['NEW_TYPE']) && !empty($field['NEW_TYPE']) )
		{
			$field['TYPE'] = $field['NEW_TYPE'];
		}
		if ( isset($field['NEW_NAME']) && !empty($field['NEW_NAME']) )
		{
			$res['NEW_NAME'] = $field['NEW_NAME'];
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

		$res = '';
		$type = sys_string::strtolower($field['TYPE']);

		// numerics
		if ( isset($this->numerics[$type]) )
		{
			$res .= $this->numerics[$type];
			$auto_increment = isset($field['EXTRA']) && ($field['EXTRA'] == 'auto_increment');
			$unsigned = $auto_increment;
			if ( isset($field['SIZE']) && !empty($field['SIZE']) )
			{
				$unsigned |= strpos($field['SIZE'], '-') === false;
				$size = preg_replace('#[\+\-\n\r\s\t]+#', '', $field['SIZE']);
				if ( !empty($size) )
				{
					$res .= '(' . $size . ')';
				}
			}
			if ( $unsigned )
			{
				$res .= ' UNSIGNED';
			}
			if ( $auto_increment )
			{
				$res .= ' auto_increment';
			}
			else
			{
				if ( !isset($field['DEFAULT']) || empty($field['DEFAULT']) || ($field['DEFAULT'] === '0') || ($field['DEFAULT'] === 0) )
				{
					$field['DEFAULT'] = $type == 'decimal' ? '0.0' : '0';
				}
				$res .= strtoupper($field['DEFAULT']) == 'NULL' ? ' DEFAULT NULL' : ' NOT NULL DEFAULT \'' . $field['DEFAULT'] . '\'';
			}
		}

		// alpha-numerics
		else
		{
			$res .= isset($this->texts[$type]) ? $this->texts[$type] : $type;
			if ( isset($field['SIZE']) && !empty($field['SIZE']) )
			{
				$res .= '(' . $field['SIZE'] . ')';
			}
			if ( ($field['TYPE'] == 'char') && ($field['SIZE'] == 1) )
			{
				$res .= ' BINARY';
			}
			if ( !in_array($type, array('text', 'mediumtext', 'longtext')) )
			{
				if ( !isset($field['DEFAULT']) || empty($field['DEFAULT']) )
				{
					$field['DEFAULT'] = '';
				}
				$matches = array();
				$default = preg_match('#^["\'](.*)["\']$#is', $field['DEFAULT'], $matches) ? $matches[1] : $field['DEFAULT'];
				$res .= strtoupper($field['DEFAULT']) == 'NULL' ? ' DEFAULT NULL' : ' NOT NULL DEFAULT ' . $db->escape((string) $default);
			}
		}
		return $res;
	}
}

?>