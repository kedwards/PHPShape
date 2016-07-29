<?php
//
//	filename: cron.processus.class.php
//	start date: 30/03/2010
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	version: 0.0.1 - 30/03/2010
//	license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class cron_processus extends sys_stdclass
{
	var $data;
	var $name;
	var $step_last;

	function __construct()
	{
		parent::__construct();
		$this->data = false;
		$this->name = false;
		$this->step_last = false;
	}

	function __destruct()
	{
		unset($this->step_last);
		unset($this->name);
		unset($this->data);
		parent::__destruct();
	}

	function define($name, $step_last)
	{
		$this->name = $name;
		$this->step_last = $step_last;
	}

	function get()
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		// get any unfinished process
		$sql = 'SELECT *
					FROM ' . $db->table('cron_processus') . '
					WHERE process_name = ' . $db->escape($this->name) . '
						AND process_step_current <= process_step_last';
		$result = $db->query($sql, __LINE__, __FILE__);
		$this->data = ($row = $db->fetch($result)) ? $row : false;
		$db->free($result);

		// no pending processes ? generate one
		if ( !$this->data )
		{
			$now = time();
			$fields = array(
				'process_name' => (string) $this->name,
				'process_step_last' => (int) $this->step_last,
				'process_step_current' => (int) 0,
				'process_time_start' => (int) $now,
				'process_time_current' => (int) $now,
			);
			$this->data = $fields;
			$sql = 'INSERT INTO ' . $db->table('cron_processus') . '
						(' . $db->fields('fields', $fields) . ') VALUES(' . $db->fields('values', $fields) . ')';
			$db->query($sql, __LINE__, __FILE__);
			$id = $db->next_id();
			$this->data = array('process_id' => intval($id)) + $this->data;
		}
	}

	function id()
	{
		return intval($this->data['process_id']);
	}

	function step()
	{
		return intval($this->data['process_step_current']);
	}

	function time_start()
	{
		return intval($this->data['process_time_start']);
	}

	function next_step()
	{
		$this->set_step($this->step() + 1);
	}

	function set_step($step)
	{
		$api = &$GLOBALS[$this->api_name];
		$db = &$api->db;

		$now = time();
		$this->data['process_step_current'] = intval($step);
		$this->data['process_time_current'] = $now;
		$fields = array(
			'process_step_current' => (int) $this->data['process_step_current'],
			'process_time_current' => (int) $this->data['process_time_current'],
		);
		$sql = 'UPDATE ' . $db->table('cron_processus') . '
					SET ' . $db->fields('update', $fields) . '
					WHERE process_id = ' . intval($this->id());
		$db->query($sql, __LINE__, __FILE__);
	}
}

?>