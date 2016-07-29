<?php
//
//	file: sys/db/dbi.mssql.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 16/01/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_dbi_mssql extends sys_dbi
{
	function supported_types()
	{
		$this->numerics = array('tinyint' => 'tinyint', 'smallint' => 'smallint', 'mediumint' => 'int', 'int' => 'int', 'bigint' => 'bigint', 'decimal' => 'decimal');
		$this->texts = array('char' => 'char', 'varchar' => 'varchar', 'text' => 'text', 'mediumtext' => 'text', 'longtext' => 'text');
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

		// then process the table structure
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			$db_table_name = $db->table($table_name);
			$lines = array();
			$dfts = array();
			if ( isset($table['FIELD']) && !empty($table['FIELD']) )
			{
				// fields
				$is_text = false;
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field = $this->field_def($field, $table_name);
					$lines[] = '[' . $field_name . '] [' . $field['TYPE'] . ']' . ($field['SIZE'] ? ' (' . $field['SIZE'] . ')' : '') . ($field['IDENTITY'] ? ' IDENTITY (1, 1) NOT NULL' : (isset($field['DEFAULT']) ? ' NOT NULL' : ' NULL'));

					// retain default value
					if ( !isset($field['IDENTITY']) && isset($field['DEFAULT']) )
					{
						$dfts[$field_name] = $field['DEFAULT'];
					}
					// check if a text field is present
					$is_text |= $field['TYPE'] == 'text';
				}
				$sqls[] = 'CREATE TABLE [' . $db_table_name . '] (
	' . implode(',
	', $lines) . '
) ON [PRIMARY]' . ($is_text ? ' TEXTIMAGE_ON [PRIMARY]' : '');

				// defaults
				if ( $dfts )
				{
					$lines = array();
					foreach ( $dfts as $field_name => $default )
					{
						$lines[] = 'CONSTRAINT [DF_' . $db_table_name . '_' . $field_name . '] DEFAULT(' . $default . ') FOR [' . $field_name . ']';
					}
					if ( $lines )
					{
						$sqls[] = 'ALTER TABLE [' . $db_table_name . '] WITH NOCHECK ADD
	' . implode(',
	', $lines);
					}
				}

				// indexes
				if ( isset($table['INDEX']) && !empty($table['INDEX']) )
				{
					$sqls = array_merge($sqls, $this->create_index_table($table, $table_name));
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
			$sqls[] = 'DROP TABLE [' . $db->table($table_name) . ']';
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
			$sqls[] = 'EXECUTE sp_rename N\'' . $db->table($table_name) . '\', N\'' . $db->table($new_name) . '\', \'OBJECT\'';
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
				$sqls = array_merge($sqls, $this->create_index_table($table, $table_name));
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
				$db_table_name = $db->table($table_name);
				foreach ( $table['INDEX'] as $index_name => $dummy )
				{
					if ( $index_name == 'PRIMARY' )
					{
						$sqls[] = 'ALTER TABLE [' . $db_table_name . '] DROP CONSTRAINT [PK_' . $db_table_name . ']';
					}
					else
					{
						$sqls[] = 'DROP INDEX [' . $db_table_name . '].[IX_' . $db_table_name . '_' . $index_name . ']';
					}
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
				$db_table_name = $db->table($table_name);

				// fields
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$field_def = $this->field_def($field, $table_name);

					// we can't add an identity field to an existing table, so set a default value to 0
					if ( isset($field_def['IDENTITY']) )
					{
						$field_def['DEFAULT'] = 0;
					}

					$sqls[] = 'ALTER TABLE [' . $db_table_name . '] ADD [' . $field_name . '] [' . $field_def['TYPE'] . ']' . ($field_def['SIZE'] ? ' (' . $field_def['SIZE'] . ')' : '') . (isset($field_def['DEFAULT']) ? ' NOT NULL' : ' NULL');
					if ( isset($field_def['DEFAULT']) )
					{
						$sqls[] = 'ALTER TABLE [' . $db_table_name . '] WITH NOCHECK ADD CONSTRAINT [DF_' . $db_table_name . '_' . $field_name . '] DEFAULT(' . $field_def['DEFAULT'] . ') FOR [' . $field_name . ']';
					}
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
				$db_table_name = $db->table($table_name);

				// search first for fields renamed
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					$new_name = $old_name = false;
					if ( $field['NEW_NAME'] )
					{
						$field_def = $this->field_def($field, $table_name);
						$new_name = $field['NEW_NAME'];
						$old_name = $field_name;
						$create_field = array('TABLE' => array($table_name => array('FIELD' => array($new_name => $field_def))));
						$sqls = array_merge($sqls, $this->create_field($create_field));
						$sqls[] = 'UPDATE ' . $db_table_name . ' SET ' . $new_name . ' = ' . $old_name . ' WHERE ' . $new_name . ' IS NOT NULL;';
						$sqls = array_merge($sqls, $this->drop_column($table_name, $field_name));
					}
				}

				// other fields
				$constraints = array();
				$constraints_done = false;
				foreach ( $table['FIELD'] as $field_name => $field )
				{
					if ( !isset($field['NEW_NAME']) )
					{
						$field_def = $this->field_def($field, $table_name);
						$change = isset($field['NEW_TYPE']) && ($field_def['TYPE'] != 'text');
						if ( isset($field['NEW_SIZE']) )
						{
							$change |= !$field_def['IS_NUMERIC'] || ($field_def['TYPE'] == 'decimal');
						}
						if ( isset($field['NEW_DEFAULT']) && isset($field_def['DEFAULT']) )
						{
							$change = true;

							// we need to update values to default when null prior adding NOT NULL
							$sqls[] = 'UPDATE ' . $db_table_name . ' SET ' . $field_name . ' = ' . $field_def['DEFAULT'] . ' WHERE ' . $field_name . ' IS NULL';
							if ( isset($field['NEW_TYPE']) || isset($field['NEW_DEFAULT']) )
							{
								if ( !isset($field_def['IDENTITY']) )
								{
									if ( !$constraints_done )
									{
										$constraints_done = true;
										$constraints = $this->get_constraints($table_name);
									}
									if ( isset($constraints[$field_name]) && isset($constraints[$field_name]['DF_' . $db_table_name . '_' . $field_name]) )
									{
										$sqls[] = 'ALTER TABLE [' . $db_table_name . '] DROP CONSTRAINT [DF_' . $db_table_name . '_' . $field_name . ']';
									}
								}
							}
						}
						if ( $change )
						{
							if ( isset($field_def['IDENTITY']) )
							{
								$field_def['DEFAULT'] = 0;
							}
							$sqls[] = 'ALTER TABLE [' . $db_table_name . '] ALTER COLUMN [' . $field_name . '] [' . $field_def['TYPE'] . ']' . ($field_def['SIZE'] ? ' (' . $field_def['SIZE'] . ')' : '') . (isset($field_def['DEFAULT']) ? ' NOT NULL' : ' NULL');
							if ( isset($field_def['DEFAULT']) )
							{
								$sqls[] = 'ALTER TABLE [' . $db_table_name . '] WITH NOCHECK ADD CONSTRAINT [DF_' . $db_table_name . '_' . $field_name . '] DEFAULT(' . $field_def['DEFAULT'] . ') FOR [' . $field_name . ']';
							}
						}
					}
				}
			}
		}
		return $sqls;
	}

	function drop_field($items)
	{
		if ( empty($items) || !isset($items['TABLE']) )
		{
			return false;
		}
		$sqls = array();
		foreach ( $items['TABLE'] as $table_name => $table )
		{
			foreach ( $table['FIELD'] as $field_name => $dummy )
			{
				$sqls = array_merge($sqls, $this->drop_column($table_name, $field_name));
			}
		}
		return $sqls;
	}

	function get_constraints($table_name, $field_name='')
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// read constraints
		$res = array();
		$sql = 'SELECT COL_NAME(id, colid) AS constraint_column, OBJECT_NAME(constid) AS constraint_name
					FROM [sysconstraints] WITH(NOLOCK)
					WHERE id = OBJECT_ID(\'' . $db->table($table_name) . '\')' . (empty($field_name) ? '
						AND colid <> 0 AND colid IS NOT NULL' : '
						AND COL_NAME(id, colid) = \'' . $field_name . '\'');
		$result = $db->query($sql, __LINE__, __FILE__, false);
		if ( $result !== false )
		{
			while ( ($row = $db->fetch($result)) )
			{
				$res[ $row['constraint_column'] ] = $row['constraint_name'];
			}
			$db->free($result);
		}
		return empty($res) ? false : $res;
	}

	function drop_column($table_name, $field_name)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$sqls = array();
		$db_table_name = $db->table($table_name);
		if ( ($constraints = $this->get_constraints($table_name, $field_name)) )
		{
			foreach ( $constraints as $constraint_name )
			{
				$sqls[] = 'ALTER TABLE [' . $db_table_name . '] DROP CONSTRAINT [' . $constraint_name . ']';
			}
		}
		$sqls[] = 'ALTER TABLE [' . $db_table_name . '] DROP COLUMN [' . $field_name . ']';
		return $sqls;
	}

	// private
	function create_index_table($table, $table_name)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$db_table_name = $db->table($table_name);
		$sqls = array();
		foreach ( $table['INDEX'] as $index_name => $index )
		{
			$type = isset($index['TYPE']) && (strtoupper($index['TYPE']) != 'INDEX') ? strtoupper($index['TYPE']) : '';
			if ( $type == 'PRIMARY' )
			{
				$sqls[] = 'ALTER TABLE [' . $db_table_name . '] WITH NOCHECK ADD CONSTRAINT [PK_' . $db_table_name . '] PRIMARY KEY CLUSTERED ([' . implode('], [', array_keys($index['FIELD'])) . ']) ON [PRIMARY]';
			}
			else
			{
				$sqls[] = 'CREATE ' . ($type == 'UNIQUE' ? 'UNIQUE ' : '') . 'INDEX [IX_' . $db_table_name . '_' . $index_name . '] ON [' . $db_table_name . ']([' . implode('], [', array_keys($index['FIELD'])) . ']) ' . ($type == 'UNIQUE' ? 'WITH IGNORE_DUP_KEY ' : '') . 'ON [PRIMARY]';
			}
		}
		return $sqls;
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
		$type = $field['NEW_TYPE'] ? sys_string::strtolower($field['NEW_TYPE']) : sys_string::strtolower($field['TYPE']);

		// numerics
		if ( isset($this->numerics[$type]) )
		{
			$res['TYPE'] = $this->numerics[$type];
			$res['IS_NUMERIC'] = true;
			if ( isset($field['SIZE']) && !empty($field['SIZE']) )
			{
				$size = preg_replace('#[\+\-\n\r\s\t]+#', '', $field['SIZE']);
				if ( !empty($size) && ($type == 'decimal') )
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
				if ( !isset($field['DEFAULT']) || empty($field['DEFAULT']) || ($field['DEFAULT'] === 0) || ($field['DEFAULT'] === '0') )
				{
					$field['DEFAULT'] = '0';
				}
				if ( strtoupper($field['DEFAULT']) != 'NULL' )
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
			$res['TYPE'] = isset($this->texts[$type]) ? $this->texts[$type] : $type;
			$res['IS_NUMERIC'] = false;
			if ( isset($field['SIZE']) && !empty($field['SIZE']) )
			{
				$res['SIZE'] = $field['SIZE'];
			}
			if ( !isset($field['DEFAULT']) || empty($field['DEFAULT']) )
			{
				$field['DEFAULT'] = '';
			}
			$matches = array();
			$field['DEFAULT'] = preg_match('#^["\'](.*)["\']$#is', $field['DEFAULT'], $matches) ? $matches[1] : $field['DEFAULT'];
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