<?php
//
//	file: sys/db/dbi.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 16/01/2007
//	version: 0.0.2 - 29/01/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

//
// ----------------------------------------------------------------
//
// Caution:
// -------
// o Droping an auto_increment field (with mySQL especially) or renaming a table with auto_increment (with postgreSQL especially)
// will lead to errors (run-time or immediate).
// In both cases the creation of a new table plus copy is required
//
// o renaming tables or columns is not a good idea: there will be issues with some db layer regarding the indexes, constraints and so
// in both case, the safier is to create a new table/field plus copy if required
//
// o Altering a field type or size for decimals and alpha with postgresql 7
// will result in loosing the indexes onto this field (a new field is created).
//
// o You can not alter field type for field being already text with mssql. However, the default value change will be applied.
//
// o When altering a column, the full definition is required (especially for postgreSQL 7), with {new_} for the attributes to change
//
// supported fields types:
// ----------------------
// o numerics: tinyint (bytes: 1), smallint (bytes: 2), mediumint (bytes: 3), int (bytes: 4), bigint (bytes: 8), decimal (bytes: 64)
// o alpha: char (size required), varchar (size required), text, mediumtext, longtext
//
// Size parameter:
// --------------
// o with numerics:
//		- with decimal type, the size is required. ie:
//			. decimal(5, 2) => type: decimal, size: 5,2
//
//		- with mySQL, the size is used for the front size, and - prevent the generation of UNSIGNED for the field. ie:
//			. smallint(5) UNSIGNED => type: smallint, size: 5
//			. mediumint(8) => type: mediumint, size: 8-
//
// o with alpha:
//		The size parameter is required for char and varchar (char varying). ie:
//			. char(5) => type: char, size: 5
//			. varchar(255) => type: varchar, size: 255
//
// xml structure:
/* --------------
<actions>
	(*)<action name="{action_qualifier}">

		(*)<create_table>
			(*)<table name="{table_name}">
				(*)<field name="{field_name}"><type>{type}</type><size>{front_size|-}</size><extra>{auto_increment}</extra><default>{NULL|value}</default></field>
				(*)<index name="{pkey|index_name}">
					<type>{PRIMARY|UNIQUE}</type>
					(*)<field name="{field_name}" />
				</index>
			</table>
		</create_table>

		(*)<drop_table>
			(*)<table name="{table_name}" />
		</drop_table>

		(*)<rename_table>
			(*)<table name="{old_table_name}">{new_table_name}</table>
		</rename_table>

		(*)<create_index>
			(*)<table name="{table_name}">
				(*)<index name="{pkey|index_name}">
					<type>{PRIMARY|UNIQUE}</type>
					(*)<field name="{field_name}" />
				</index>
			</table>
		</create_index>

		(*)<drop_index>
			(*)<table name="{table_name}">
				(*)<index name="{index_name}" />
			</table>
		</drop_index>

		(*)<create_field>
			(*)<table name="{table_name}">
				(*)<field name="{field_name}"><type>{type}</type><size>{front_size|-}</size><extra>{auto_increment}</extra><default>{NULL|value}</default></field>
			</table>
		</create_field>

		(*)<change_field>
			(*)<table name="{table_name}">
				(*)<field name="{field_name}"><(new_)type>{type}</(new_)type><(new_)size>{front_size|-}</(new_)size><(new_)extra>{auto_increment}</(new_)extra><(new_)default>{NULL|value}</(new_)default></field>
			</table>
		</change_field>

		(*)<drop_field>
			(*)<table name="{table_name}">
				(*)<field name="{field_name}" />
			</table>
		</drop_field>

		(*)<run>
			(*)<sql>{request: UPDATE {table_name} SET ...]}</sql>
		</run>

	</action>
</actions>
*/
//
// ----------------------------------------------------------------
//

class sys_dbi extends sys_stdclass
{
	var $numerics;
	var $texts;

	function __construct()
	{
		parent::__construct();
		$this->numerics = false;
		$this->texts = false;
		$this->supported_types();
	}

	function __destruct()
	{
		unset($this->texts);
		unset($this->numerics);
		parent::__destruct();
	}

	// static: get the class & load the layer
	function get_layer($layer)
	{
		$sys = &$GLOBALS[SYS];
		$ini = array(
			'mssql' => array('file' => 'dbi.mssql.class', 'class' => 'sys_dbi_mssql'),
			'mysql' => array('file' => 'dbi.mysql.class', 'class' => 'sys_dbi_mysql'),
			'pgsql' => array('file' => 'dbi.pgsql.class', 'class' => 'sys_dbi_pgsql'),
		);
		if ( !isset($ini[$layer]) )
		{
			return false;
		}
		if ( !class_exists($ini[$layer]['class']) )
		{
			include($sys->path . 'db/' . $ini[$layer]['file'] . $sys->ext);
		}
		return $ini[$layer]['class'];
	}

	function supported_types()
	{
		$this->numerics = array('tinyint' => '', 'smallint' => '', 'mediumint' => '', 'int' => '', 'bigint' => '', 'decimal' => '');
		$this->texts = array('char' => '', 'varchar' => '', 'text' => '', 'mediumtext' => '', 'longtext' => '');
	}

	function process(&$actions)
	{
		// our process methods
		$methods = array(
			'CREATE_TABLE' => 'create_table',
			'DROP_TABLE' => 'drop_table',
			'RENAME_TABLE' => 'rename_table',
			'CREATE_INDEX' => 'create_index',
			'DROP_INDEX' => 'drop_index',
			'CREATE_FIELD' => 'create_field',
			'CHANGE_FIELD' => 'change_field',
			'DROP_FIELD' => 'drop_field',
			'RUN' => 'run_process',
		);

		// let's go
		$sqls = array();
		$count_actions = count($actions);
		for ( $i = 0; $i < $count_actions; $i++ )
		{
			if ( !isset($actions[$i]['name']) || ($actions[$i]['name'] != 'ACTIONS') || !isset($actions[$i]['childs']) || !($count_actions_childs = count($actions[$i]['childs'])) )
			{
				continue;
			}
			for ( $j = 0; $j < $count_actions_childs; $j++ )
			{
				if ( !isset($actions[$i]['childs'][$j]['name']) || ($actions[$i]['childs'][$j]['name'] != 'ACTION') || !isset($actions[$i]['childs'][$j]['childs']) || !($count_action_childs = count($actions[$i]['childs'][$j]['childs'])) )
				{
					continue;
				}
				for ( $k = 0; $k < $count_action_childs; $k++ )
				{
					if ( !isset($actions[$i]['childs'][$j]['childs'][$k]['name']) || !isset($methods[ $actions[$i]['childs'][$j]['childs'][$k]['name'] ]) || !($decoded = $this->decode($actions[$i]['childs'][$j]['childs'][$k]['childs'])) )
					{
						continue;
					}
					if ( ($res = call_user_func(array(&$this, $methods[ $actions[$i]['childs'][$j]['childs'][$k]['name'] ]), $decoded)) )
					{
						$sqls = array_merge($sqls, $res);
					}
				}
			}
		}
		return $sqls;
	}

	function decode($items)
	{
		$def = array();
		$count_items = count($items);
		for ( $i = 0; $i < $count_items; $i++ )
		{
			if ( !isset($items[$i]['name']) )
			{
				continue;
			}
			switch ( $items[$i]['name'] )
			{
				case 'TABLE':
					if ( ($table = $this->decode_table($items[$i])) )
					{
						if ( !isset($def[ $items[$i]['name'] ]) )
						{
							$def[ $items[$i]['name'] ] = array();
						}
						$def[ $items[$i]['name'] ] += $table;
					}
					break;

				case 'SQL':
					if ( isset($items[$i]['content']) && $items[$i]['content'] )
					{
						if ( !isset($def['RUN']) )
						{
							$def['RUN'] = array();
						}
						$def['RUN'][] = array($items[$i]['name'] => $items[$i]['content']);
					}
					break;
			}
		}
		return $def;
	}

	function decode_table($item)
	{
		if ( !isset($item['attrs']) || !isset($item['attrs']['NAME']) || !$item['attrs']['NAME'] )
		{
			return false;
		}
		$name = $item['attrs']['NAME'];
		unset($item['attrs']['NAME']);
		$def = $item['attrs'];

		if ( !isset($item['childs']) )
		{
			return array($name => isset($item['content']) ? $item['content'] : '');
		}

		// fields & indexes
		$count_childs = count($item['childs']);
		for ( $i = 0; $i < $count_childs; $i++ )
		{
			if ( !isset($item['childs'][$i]['name']) )
			{
				continue;
			}
			switch ( $item['childs'][$i]['name'] )
			{
				case 'FIELD':
					if ( ($field = $this->decode_field($item['childs'][$i])) )
					{
						if ( !isset($def[ $item['childs'][$i]['name'] ]) )
						{
							$def[ $item['childs'][$i]['name'] ] = array();
						}
						$def[ $item['childs'][$i]['name'] ] += $field;
					}
					break;

				case 'INDEX':
					if ( ($index = $this->decode_index($item['childs'][$i])) )
					{
						if ( !isset($def[ $item['childs'][$i]['name'] ]) )
						{
							$def[ $item['childs'][$i]['name'] ] = array();
						}
						$def[ $item['childs'][$i]['name'] ] += $index;
					}
					break;

				default:
					$def[ $item['childs'][$i]['name'] ] = isset($item['childs'][$i]['content']) ? $item['childs'][$i]['content'] : '';
					break;
			}
		}
		return array($name => $def ? $def : '');
	}

	function decode_index($item)
	{
		if ( !isset($item['attrs']) || !isset($item['attrs']['NAME']) || !$item['attrs']['NAME'] )
		{
			return false;
		}
		$name = $item['attrs']['NAME'];
		unset($item['attrs']['NAME']);
		$def = $item['attrs'];

		if ( !isset($item['childs']) )
		{
			return array($name => isset($item['content']) ? $item['content'] : '');
		}

		$count_childs = count($item['childs']);
		for ( $i = 0; $i < $count_childs; $i++ )
		{
			if ( !isset($item['childs'][$i]['name']) )
			{
				continue;
			}
			switch ( $item['childs'][$i]['name'] )
			{
				case 'FIELD':
					if ( ($field = $this->decode_field($item['childs'][$i])) )
					{
						if ( !isset($def[ $item['childs'][$i]['name'] ]) )
						{
							$def[ $item['childs'][$i]['name'] ] = array();
						}
						$def[ $item['childs'][$i]['name'] ] += $field;
					}
					break;

				default:
					$def[ $item['childs'][$i]['name'] ] = isset($item['childs'][$i]['content']) ? $item['childs'][$i]['content'] : '';
					break;
			}
		}
		return array($name => $def ? $def : '');
	}

	function decode_field($item)
	{
		if ( !isset($item['attrs']) || !isset($item['attrs']['NAME']) || !$item['attrs']['NAME'] )
		{
			return false;
		}
		$def = $item['attrs'];
		$name = $def['NAME'];
		unset($def['NAME']);
		if ( isset($item['childs']) && $item['childs'] )
		{
			$count_childs = count($item['childs']);
			for ( $i = 0; $i < $count_childs; $i++ )
			{
				if ( !isset($item['childs'][$i]['name']) )
				{
					continue;
				}
				$def[ $item['childs'][$i]['name'] ] = isset($item['childs'][$i]['content']) ? $item['childs'][$i]['content'] : '';
			}
		}
		return array($name => $def ? $def : '');
	}

	function run_process($items)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		if ( !$items || !isset($items['RUN']) )
		{
			return false;
		}

		$res = array();
		$count_items = count($items['RUN']);
		for ( $i = 0; $i < $count_items; $i++ )
		{
			if ( !empty($items['RUN'][$i]) )
			{
				foreach ( $items['RUN'][$i] as $type => $command )
				{
					switch ( $type )
					{
						case 'SQL':
							$matches = array();
							preg_match_all('#\{([^\}]+)\}#is', $command, $matches);
							$res[] = $matches[1] ? str_replace($matches[0], array_map(array(&$db, 'table'), $matches[1]), $command) : $command;
							break;

						default:
							break;
					}
				}
			}
		}
		return $res;
	}
}

?>