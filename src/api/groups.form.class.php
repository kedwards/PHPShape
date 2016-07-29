<?php
//
//	file: inc/groups.form.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.2 - 26/11/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class groups_form extends sys_stdclass
{
	var $item_id;
	var $data;
	var $form_fields;

	function __construct()
	{
		parent::__construct();
		$this->item_id = false;
		$this->data = false;
		$this->form_fields = false;
	}

	function __destruct()
	{
		unset($this->form_fields);
		unset($this->data);
		unset($this->item_id);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$class = $sys->ini_get('groups', 'class');
		$this->tree = new $class();
		$this->tree->set($this->api_name);
		$this->form_fields = array(
			'group_name' => array('field' => 'group_name', 'default' => ''),
			'group_desc' => array('field' => 'group_desc', 'default' => ''),
			'group_lang' => array('field' => 'group_lang', 'default' => ''),
		);
	}

	function process()
	{
		if ( $this->init() )
		{
			$this->check();
			$this->validate();
			return $this->display();
		}
		return false;
	}

	function init()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		$io = &$sys->io;

		if ( $io->button('cancel_form') )
		{
			return false;
		}
		// check the item: it must exists, be different from root, and the actor must be a manager of this item
		if ( !($this->item_id = $io->read(SYS_U_ITEM, 0)) || !$this->tree->read_item($this->item_id) || !$this->tree->allowed($this->item_id, 'manage') )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		// to be able to delete, we must be the manager of the whole content plus the parent
		if ( $api->mode_sub == 'delete' )
		{
			if ( !$this->tree->delete_allowed($this->item_id) )
			{
				trigger_error('err_not_authorized', E_USER_ERROR);
			}
		}

		// proceed
		$this->data = array();
		foreach ( $this->form_fields as $form_name => $field_def )
		{
			$this->data[ $field_def['field'] ] = $api->mode_sub == 'create' ? $field_def['default'] : ($field_def['default'] === 0 ? intval($this->tree->data[$this->item_id][$form_name]) : $this->tree->data[$this->item_id][$form_name]);
		}
		$sys->lang->get_available();
		return true;
	}

	function check()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;

		if ( !$io->button('submit_form') )
		{
			return false;
		}

		// get data back from the form & check them
		switch ( $api->mode_sub )
		{
			case 'create':
			case 'edit':
				foreach ( $this->form_fields as $form_name => $field_def )
				{
					$this->data[ $field_def['field'] ] = $io->read($form_name, $field_def['default'], '_POST');
				}
				if ( empty($this->data['group_name']) )
				{
					trigger_error('err_empty_group_name', E_USER_WARNING);
				}
				if ( $sys->lang->availables && (count($sys->lang->availables) > 1) )
				{
					if ( ($this->data['group_lang'] !== '') && !isset($sys->lang->availables[ $this->data['group_lang'] ]) )
					{
						trigger_error('err_group_lang_not_available', E_USER_WARNING);
					}
				}
				else
				{
					$this->data['group_lang'] = '';
				}
			break;
			case 'delete':
			break;
		}
		return true;
	}

	function validate()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$error = &$sys->error;
		if ( !$io->button('submit_form') || $error->warnings )
		{
			return false;
		}

		$message = '';
		$pid = 0;
		switch ( $api->mode_sub )
		{
			case 'create':
				$fields = $this->data + array(
					'group_pid' => (int) $this->item_id,
					'group_lid' => $this->item_id ? $this->tree->data[$this->item_id][$this->tree->field_lid] + 1 : 1,
					'group_rid' => $this->item_id ? $this->tree->data[$this->item_id][$this->tree->field_lid] + 2 : 2,
				);
				$pid = (int) $this->item_id;
				$this->tree->insert($fields);
				$message = 'group_created';
			break;
			case 'edit':
				$fields = $this->data;
				$pid = (int) $this->tree->data[$this->item_id][$this->tree->field_pid];
				$this->tree->update($this->item_id, $fields);
				$message = 'group_updated';
			break;
			case 'delete':
				$pid = (int) $this->tree->data[$this->item_id][$this->tree->field_pid];
				$this->tree->delete($this->item_id);
				$message = 'group_deleted';
			break;
		}
		$api->feedback($message, array('backto_groups' => $api->url(array(SYS_U_MODE => 'groups', SYS_U_ITEM => $pid))));
		return true;
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( intval($this->item_id) )
		{
			$tpl->hide(array(
				SYS_U_ITEM => (int) $this->item_id,
			));
		}
		$tpl->add(array(
			'NAME' => $this->data['group_name'],
			'DESC' => $this->data['group_desc'],
			'LANG' => $this->data['group_lang'],
		));
		if ( $sys->lang->availables && (count($sys->lang->availables) > 1) )
		{
			foreach ( $sys->lang->availables as $key => $desc )
			{
				$tpl->add('langs', array(
					'VALUE' => sys_string::htmlspecialchars($key),
					'DESC' => sys_string::htmlspecialchars($desc),
				));
			}
		}
		return 'groups.form';
	}
}

?>