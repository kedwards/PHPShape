<?php
//
//	file: inc/users.form.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 05/02/2008
//	version: 0.0.4 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class actor_login extends sys_stdclass
{
	var $data;
	var $user;
	var $activation_required;
	var $activation_done;
	var $disable_password_renew;
	var $reset_password_renew;
	var $password_renewed;

	function __construct()
	{
		parent::__construct();
		$this->data = false;
		$this->user = false;
		$this->activation_required = false;
		$this->activation_done = false;
		$this->disable_password_renew = false;
		$this->reset_password_renew = false;
		$this->password_renewed = false;
	}

	function __destruct()
	{
		unset($this->password_renewed);
		unset($this->reset_password_renew);
		unset($this->disable_password_renew);
		unset($this->activation_done);
		unset($this->activation_required);
		if ( isset($this->user) )
		{
			sys::kill($this->user);
			unset($this->user);
		}
		unset($this->data);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
		$this->activation_required = $sys->ini_get('login.activation');
		$this->disable_password_renew = $sys->ini_get('login.disable.password.renew');
		$this->reset_password_renew = false;
		$this->password_renewed = false;
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
		$user = &$api->user;

		if ( ($io->button('cancel_form') && !$api->ignore_cancel) || ($user->data && $user->data['user_id']) )
		{
			$api->mode = '';
			return false;
		}

		// create a new user instance
		$class = $sys->ini_get('user', 'class');
		$this->user = new $class();
		$this->user->set($this->api_name);

		// get data from form
		$this->data = array(
			'user_ident' => ($ident = $io->read('user_ident', '', '_POST')) ? $sys->utf8->get_identifier($ident) : '',
			'user_password' => ($passwd = $io->read('user_password', '', '_POST')) ? $this->user->encode_password($passwd) : '',
			'remember' => $io->button('remember'),
		);
		if ( $this->activation_required )
		{
			$this->data += array(
				'actkey' => $io->read(SYS_U_ACTKEY, '', $io->button('submit_form') ? '_POST' : '_GET'),
			);
		}
		return true;
	}

	function check()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$db = &$api->db;

		if ( !$io->button('submit_form') )
		{
			return false;
		}
		$this->activation_done = false;

		// both pass and ident are required
		if ( !$this->data['user_ident'] || !$this->data['user_password'] )
		{
			trigger_error('err_user_login_required', E_USER_WARNING);
			return false;
		}

		// check if we can get something with the ident
		$sql = 'SELECT *
					FROM ' . $db->table('users') . '
					WHERE user_ident = ' . $db->escape($this->data['user_ident']);
		$result = $db->query($sql, __LINE__, __FILE__);
		$this->user->data = ($row = $db->fetch($result)) ? $row : false;
		$db->free($result);

		// verify login attempts
		$login_max_attempts = $sys->ini_get('login.max_attempts');
		$login_reset_time = $sys->ini_get('login.reset_time');
		if ( $this->user->data && ($login_max_attempts && ($this->user->data['user_login_tries'] > $login_max_attempts)) && ($login_reset_time && ($this->user->data['user_login_tries_last'] > (time() - ($login_reset_time * 60)))) )
		{
			$this->user->data = false;
			trigger_error('err_user_max_login', E_USER_ERROR);
		}

		// check password against our own database
		$password_match = $this->user->data && ($this->user->data['user_password'] == $this->data['user_password']);

		// maybe a renewed password ?
		if ( $this->user->data && !$password_match )
		{
			if ( !$this->disable_password_renew && isset($this->user->data['user_password_renew']) && !empty($this->user->data['user_password_renew']) && ($this->user->data['user_password_renew'] == $this->data['user_password']) )
			{
				$password_match = true;
				$this->password_renewed = true;
			}
		}

		// check password with other methods (hooks)
		if ( $this->user->data && !$password_match )
		{
			$api->hooks->set_data('users.check.password', array(
				'user.data' => $this->user->data,
				'user.password' => $io->read('user_password', ''),
				'user.match' => $password_match,
			));
			$api->hooks->process('users.check.password');
			$data = $api->hooks->get_data('users.check.password');
			$password_match = isset($data['user.match']) && $data['user.match'];
			$api->hooks->unset_data('users.check.password');
		}

		// the user exists && the password is correct ?
		if ( !$password_match )
		{
			if ( $this->user->data )
			{
				$sql = 'UPDATE ' . $db->table('users') . '
							SET user_login_tries = user_login_tries + 1, user_login_tries_last = ' . intval(time()) . '
							WHERE user_id = ' . intval($this->user->data['user_id']);
				$db->query($sql, __LINE__, __FILE__);
			}
			$this->user->data = false;
			trigger_error('err_user_login_not_found', E_USER_WARNING);
		}

		// no match
		if ( !$password_match )
		{
			return false;
		}

		// the password match
		$this->reset_password_renew = isset($this->user->data['user_password_renew']) && !empty($this->user->data['user_password_renew']);

		// the user exists and the password is correct. Is it activated, and if no, is the activation key present and ok ?
		if ( $this->activation_required && $this->user->data['user_disabled'] )
		{
			if ( !$this->data['actkey'] )
			{
				// we start the session to be sure the "Houston, we have a problem" link is followed by the actor for his own profile
				$this->start_session();
				trigger_error(sys_error::sprintf('err_user_not_activated', $api->url(array(SYS_U_MODE => 'login.problem', SYS_U_USER => intval($this->user->data['user_id'])))), E_USER_ERROR);
			}
			if ( $this->data['actkey'] != $this->user->data['user_actkey'] )
			{
				trigger_error('err_user_actkey_no_match', E_USER_ERROR);
			}
			$this->activation_done = true;
		}
		return true;
	}

	function validate()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$error = &$sys->error;
		$session = &$api->session;

		if ( !$io->button('submit_form') || $error->warnings )
		{
			return false;
		}

		// update the user to reset logins attempts
		$this->user->data['user_login_tries'] = 0;
		$this->user->data['user_login_tries_last'] = 0;
		if ( $this->activation_done )
		{
			$this->user->data['user_actkey'] = '';
			$this->user->data['user_disabled'] = 0;
		}
		if ( $this->password_renewed )
		{
			$this->user->data['user_password'] = $this->user->data['user_password_renew'];
		}
		if ( $this->reset_password_renew )
		{
			$this->user->data['user_password_renew'] = '';
		}
		$this->user->update($this->user->data);
		if ( !$this->user->data['user_disabled'] )
		{
			$this->user->auto_group();
		}

		$this->start_session();
		$api->feedback($this->activation_done ? 'user_activation_message' : 'user_login_message', ($backto = $sys->ini_get('login.backto')) ? array(key($backto) => $api->url(@reset($backto))) : false, ($delay = $sys->ini_get('login.delay')) || ($delay !== false) ? $delay : false);
		return true;
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$tpl->add(array('PASSWORD_RENEW' => !$this->disable_password_renew));
		if ( $this->activation_required && $this->data && $this->data['actkey'] )
		{
			$tpl->hide(array(SYS_U_ACTKEY => sys_string::htmlspecialchars($this->data['actkey'])));
		}
		return 'users.login.form';
	}

	function start_session()
	{
		$api = &$GLOBALS[$this->api_name];
		$session = &$api->session;

		$api->user->data = $this->user->data;
		$actor = &$api->user;
		$session->close();
		$session->create(array('user_id' => (int) $actor->data['user_id']));
		$actor->generate_autologin($this->activation_required && $actor->data['user_disabled'] ? false : $this->data['remember']);
		$actor->login_history();
	}
}

class actor_logout extends sys_stdclass
{
	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
	}

	function process()
	{
		$api = &$GLOBALS[$this->api_name];
		$actor = &$api->user;

		if ( $actor->data )
		{
			$this->kill_session();
			$api->feedback('user_logout_message');
		}
		return false;
	}

	function kill_session()
	{
		$api = &$GLOBALS[$this->api_name];
		$session = &$api->session;
		$actor = &$api->user;

		$actor->generate_autologin(false);
		$session->close();
		$actor->reset();
	}
}

class actor_login_report extends sys_stdclass
{
	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
	}

	function process()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$actor = &$api->user;

		if ( !$actor->data || !$actor->data['user_id'] || !$io->read(SYS_U_USER, 0) )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		// prepare the mail to the administrator
		$report = $sys->ini_get('login.report');

		$class = $sys->ini_get('emailer', 'class');
		$mailengine = emailer::get_config('mailengine');
		$class = $sys->ini_get('emailer.' . $mailengine, 'class');
		$emailer = new $class();
		$emailer->set($this->api_name);
		$emailer->add(array(
			'URL' => preg_replace('#/$#', '', $sys->get_server_url(true)),
			'URI' => '/' . preg_replace('#^[\./]+#is', '', $api->url(array(SYS_U_MODE => 'users.edit', SYS_U_USER => intval($actor->data['user_id'])), true)),
			'NAME' => sys_string::htmlspecialchars($actor->data['user_name']),
			'EMAIL' => sys_string::htmlspecialchars($actor->data['user_email']),
			'REALNAME' => sys_string::htmlspecialchars($actor->data['user_realname']),
		));
		$emailer->reset_recipients();
		$emailer->set_lang($report['lang']);
		$emailer->set_recipient($report['email']);
		$result = $emailer->send('login.report');
		sys::kill($emailer);
		unset($emailer);

		$api->feedback('user_login_report_done');
	}
}

class actor_login_lost extends sys_stdclass
{
	var $email_uniq;
	var $submit;
	var $name;
	var $ident;
	var $email;
	var $password;
	var $user;

	function __construct()
	{
		parent::__construct();
		$this->email_uniq = false;
		$this->submit = false;
		$this->name = false;
		$this->ident = false;
		$this->email = false;
		$this->user = false;
	}

	function __destruct()
	{
		if ( $this->user )
		{
			sys::kill($this->user);
			unset($this->user);
		}
		unset($this->email);
		unset($this->ident);
		unset($this->name);
		unset($this->submit);
		unset($this->email_uniq);
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
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
		$actor = &$api->user;

		if ( $actor->data || $sys->ini_get('login.disable.password.renew') )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		if ( $io->button('cancel_form') )
		{
			$api->redirect($api->url(array(SYS_U_MODE => 'login')));
		}
		$this->email_uniq = $sys->ini_get('login.email.unique');
		$this->submit = $io->button('submit_form');
		$this->name = $this->email_uniq ? false : $io->read('name', '', '_POST');
		$this->ident = $this->email_uniq || empty($this->name) ? false : $sys->utf8->get_identifier($this->name);
		$this->email = $io->read('email', '', '_POST');
		return true;
	}

	function check()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$error = &$sys->error;
		$db = &$api->db;

		if ( !$this->submit )
		{
			return false;
		}
		if ( !$this->email_uniq && empty($this->ident) )
		{
			trigger_error('err_user_name_empty', E_USER_WARNING);
		}
		if ( empty($this->email) )
		{
			trigger_error('err_user_email_empty', E_USER_WARNING);
		}
		else if ( !preg_match('#' . sys::preg_expr('email') . '#', $this->email) )
		{
			trigger_error('err_user_email_not_valid', E_USER_WARNING);
		}
		if ( $error->warnings )
		{
			return false;
		}

		// now get the user
		$this->user = false;
		$sql = 'SELECT *
					FROM ' . $db->table('users') . '
					WHERE user_email = ' . $db->escape($this->email) . ($this->email_uniq ? '' : '
						AND user_ident = ' . $db->escape($this->ident));
		$result = $db->query($sql, __LINE__, __FILE__);
		$data = ($row = $db->fetch($result)) ? $row : false;
		$db->free($result);
		if ( $data )
		{
			$class = $sys->ini_get('user', 'class');
			$this->user = new $class();
			$this->user->set($this->api_name);
			$this->user->data = $data;
		}

		if ( !$this->user )
		{
			trigger_error('err_user_unknown', E_USER_WARNING);
		}
	}

	function validate()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$error = &$sys->error;
		$session = &$api->session;

		if ( !$this->submit || $error->warnings )
		{
			return false;
		}

		// create a new temp password
		$password = $this->_get_new_password();

		// update the user
		$data = array(
			'user_password_renew' => $this->user->encode_password($password),
		);
		$this->user->update($data);

		// send the mail
		$this->notify($password);

		// and conclude with the message
		$api->feedback('user_password_renewed');
	}

	function _get_new_password()
	{
		$length = 8;
		return substr(md5(uniqid(mt_rand())), rand(0, 31 - $length), $length);
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$tpl->add(array(
			'EMAIL_UNIQ' => $this->email_uniq,
			'EMAIL' => $this->email ? sys_string::htmlspecialchars($this->email) : '',
			'NAME' => $this->email_uniq || !$this->name ? false : sys_string::htmlspecialchars($this->name),
		));
		return 'users.login.renew.form';
	}

	function notify($password)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		$class = $sys->ini_get('emailer', 'class');
		$mailengine = emailer::get_config('mailengine');
		$class = $sys->ini_get('emailer.' . $mailengine, 'class');
		$emailer = new $class();
		$emailer->set($this->api_name);
		$emailer->add(array(
			'URL' => preg_replace('#/$#', '', $sys->get_server_url(true)) . '/' . preg_replace('#^[\./]+#is', '', $api->url(array(SYS_U_MODE => 'login'), true, true)),
			'NAME' => sys_string::htmlspecialchars($this->user->data['user_name']),
			'PASSWORD' => sys_string::htmlspecialchars($password),
			'REALNAME' => sys_string::htmlspecialchars($this->user->data['user_realname']),
		));

		$emailer->reset_recipients();
		$emailer->set_lang($this->user->data['user_lang'] ? $this->user->data['user_lang'] : $sys->lang->active);
		$emailer->set_recipient($this->user->data['user_email']);
		$result = $emailer->send('password.renew');
		sys::kill($emailer);
		unset($emailer);
	}
}

class user_edit extends sys_stdclass
{
	var $user;
	var $form_fields;
	var $form_values;
	var $group_id;
	var $timeshifts;
	var $activation_required;
	var $activation_done;
	var $notify_activation_done;
	var $notify_activation_required;
	var $notify_new_registration;
	var $notify_password_changed;

	function __construct()
	{
		parent::__construct();
		$this->user = false;
		$this->form_fields = false;
		$this->form_values = false;
		$this->group_id = false;
		$this->timeshifts = false;
		$this->activation_required = false;
		$this->activation_done = false;
		$this->notify_activation_done = false;
		$this->notify_activation_required = false;
		$this->notify_new_registration = false;
		$this->notify_password_changed = false;
	}

	function __destruct()
	{
		unset($this->notify_password_changed);
		unset($this->notify_new_registration);
		unset($this->notify_activation_required);
		unset($this->notify_activation_done);
		unset($this->activation_done);
		unset($this->activation_required);
		unset($this->timeshifts);
		unset($this->group_id);
		unset($this->form_values);
		unset($this->form_fields);
		if ( isset($this->user) )
		{
			sys::kill($this->user);
			unset($this->user);
		}
		parent::__destruct();
	}

	function set($api_name)
	{
		$sys = &$GLOBALS[SYS];
		parent::set($api_name);

		$sys->lang->register('api.users');
		$this->timeshifts = $sys->ini_get('timezones');
		$this->activation_required = $sys->ini_get('login.activation');
	}

	function process()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;

		// cancel
		if ( $io->button('cancel_form') )
		{
			if ( $api->mode_base == 'users' )
			{
				$api->mode = 'groups.membership';
				$api->slice_mode();
			}
			else
			{
				$api->mode = $sys->ini_get('default.mode');
				$api->slice_mode();
			}
			return false;
		}

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
		$user = &$api->user;

		// get the user
		$user_id = false;
		if ( ($api->mode !== 'register') && (!$user->data || !$user->data['user_id']) )
		{
			trigger_error('err_user_unknown', E_USER_ERROR);
		}
		if ( $api->mode == 'profile' )
		{
			if ( $user->data && $user->data['user_id'] )
			{
				$user_id = $user->data['user_id'];
			}
		}
		if ( $api->mode != 'users.create' )
		{
			$user_id = $io->read(SYS_U_USER, 0);
			if ( !$user_id || ($user->data && ($user->data['user_id'] == $user_id)) )
			{
				$user_id = $user->data['user_id'];
			}
		}
		if ( $user_id && ($user_id == $user->data['user_id']) )
		{
			$this->user = &$api->user;
		}
		else
		{
			$class = $sys->ini_get('user', 'class');
			$this->user = new $class();
			$this->user->set($this->api_name);
			if ( $user_id )
			{
				$this->user->read($user_id);
				if ( !$this->user->data )
				{
					trigger_error('err_user_unknown', E_USER_ERROR);
				}
				$this->user->read_groups();
			}
		}
		if ( $api->mode == 'users.delete' )
		{
			if ( $this->user->data['user_id'] == $user->data['user_id'] )
			{
				trigger_error('err_user_self_delete', E_USER_ERROR);
			}
		}

		// a group arised ?
		if ( ($group_id = $io->read(SYS_U_GROUP, 0)) )
		{
			$class = $sys->ini_get('groups', 'class');
			$groups = new $class();
			$groups->set($this->api_name);
			if ( $groups->read_item($group_id) && $groups->allowed($group_id, 'manage') )
			{
				$this->group_id = $group_id;
			}
			sys::kill($groups);
			unset($groups);
		}
		if ( $group_id && !$this->group_id )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}
		if ( !$user->is_admin && in_array($api->mode, array('users.edit', 'users.delete', 'users.create')) && !$this->group_id && (!$user->auth_types_manager || !isset($user->auth_types_manager[SYS_U_GROUP])) )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		// choose the appropriate fields set
		$this->form_fields = array();
		$input = $io->button(SYS_U_MODE);
		switch ( $api->mode )
		{
			case 'users.create':
			case 'users.edit':
				if ( $this->activation_required && !$this->user->is_admin )
				{
					$this->form_fields += array_flip(array('enable'));
				}
			case 'register':
				$this->form_fields += array_flip(array('name', 'password_new', 'password_confirm'));
			break;
			case 'profile':
				$this->form_fields += array_flip(array('name', 'password_current', 'password_new', 'password_confirm'));
			break;
			case 'users.delete':
				$this->form_fields += array_flip(array('name', 'realname'));
				$input = false;
			break;
		}
		$this->form_fields += array_flip(array('first_name', 'last_name', 'email', 'phone', 'location'));
		if ( $sys->lang->get_available() && $sys->lang->availables && (count($sys->lang->availables) > 1) )
		{
			$this->form_fields += array('lang' => '');
		}
		if ( $this->timeshifts )
		{
			$this->form_fields += array('timeshift' => '', 'timeshift_disable' => 0);
		}

		// get the values
		$fields_def = array(
			'name' => '',
			'password_current' => '',
			'password_new' => '',
			'password_confirm' => '',
			'first_name' => '',
			'last_name' => '',
			'realname' => '',
			'email' => '',
			'phone' => '',
			'location' => '',
			'timeshift' => '',
			'timeshift_disable' => 0,
		);
		if ( isset($this->form_fields['lang']) )
		{
			$fields_def += array(
				'lang' => '',
			);
		}
		if ( $user_id && $this->user->data['user_realname'] )
		{
			$xname = explode(', ', $this->user->data['user_realname']);
			$fields_def['first_name'] = isset($xname[1]) ? $xname[1] : '';
			$fields_def['last_name'] = $xname[0];
		}

		$fields_def += $this->_get_additional_fields_def();

		// get generic values
		$this->form_values = array();
		foreach ( $this->form_fields as $name => $dummy )
		{
			if ( isset($fields_def[$name]) )
			{
				$this->form_values[$name] = $user_id && isset($this->user->data['user_' . $name]) ? $this->user->data['user_' . $name] : $fields_def[$name];
				$this->form_fields[$name] = is_string($fields_def[$name]) ? '' : 0;
				if ( $input )
				{
					$this->form_values[$name] = $io->read($name, $this->form_fields[$name], '_POST');
				}
			}
		}

		// get special values
		if ( isset($this->form_fields['enable']) )
		{
			$this->form_values['enable'] = ($api->mode == 'users.create') || ($this->user->data && !$this->user->data['user_disabled']) ? 1 : 0;
			if ( $input )
			{
				$this->form_values['enable'] = intval($io->button('enable'));
			}
		}

		// process some values
		if ( isset($this->form_fields['email']) )
		{
			$this->form_values['email'] = sys_string::strtolower($this->form_values['email']);
		}
		return true;
	}

	function _get_additional_fields_def()
	{
		return array();
	}

	function check()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		$io = &$sys->io;
		$db = &$api->db;
		$user = &$api->user;

		if ( !$io->button('submit_form') )
		{
			return false;
		}

		if ( $api->mode == 'users.delete' )
		{
			return true;
		}

		// check fields
		if ( !$this->form_values['name'] )
		{
			trigger_error('err_user_name_empty', E_USER_WARNING);
		}
		else if ( !($ident = $sys->utf8->get_identifier($this->form_values['name'])) )
		{
			trigger_error('err_user_name_not_valid', E_USER_WARNING);
		}
		// check the unicity of the ident
		else
		{
			$sql = 'SELECT user_id
						FROM ' . $db->table('users') . '
						WHERE user_ident = ' . $db->escape($ident) . (!$this->user->data ? '' : '
							AND user_id <> ' . intval($this->user->data['user_id']));
			$result = $db->query($sql, __LINE__, __FILE__);
			$exists = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $exists )
			{
				trigger_error('err_user_name_exists', E_USER_WARNING);
			}
		}

		// check password
		if ( isset($this->form_values['password_current']) )
		{
			if ( empty($this->form_values['password_current']) )
			{
				trigger_error('err_user_password_empty', E_USER_WARNING);
			}
			else if ( $this->user->encode_password($this->form_values['password_current']) != $this->user->data['user_password'] )
			{
				trigger_error('err_user_password_mismatch', E_USER_WARNING);
			}
		}
		if ( isset($this->form_values['password_new']) )
		{
			if ( !$user->is_admin || ($user->data && ($user->data['user_id'] == $this->user->data['user_id'])) )
			{
				if ( !isset($this->form_values['password_current']) && empty($this->form_values['password_new']) )
				{
					trigger_error('err_user_password_new_empty', E_USER_WARNING);
				}
				if ( !empty($this->form_values['password_new']) && ($this->form_values['password_new'] != $this->form_values['password_confirm']) )
				{
					trigger_error('err_user_password_new_mismatch', E_USER_WARNING);
				}
			}
			if ( ($api->mode == 'users.create') && empty($this->form_values['password_new']) )
			{
				trigger_error('err_user_password_new_empty', E_USER_WARNING);
			}
			$this->form_values['flat_password'] = $this->form_values['password_new'];
			$this->form_values['password_new'] = empty($this->form_values['password_new']) ? '' : $this->user->encode_password($this->form_values['password_new']);
		}
		if ( isset($this->form_values['password_current']) )
		{
			unset($this->form_values['password_current']);
		}
		if ( isset($this->form_values['password_confirm']) )
		{
			unset($this->form_values['password_confirm']);
		}
		if ( isset($this->form_values['password_new']) && empty($this->form_values['password_new']) )
		{
			unset($this->form_values['password_new']);
		}

		// realname
		if ( isset($this->form_values['last_name']) )
		{
			$first_name = $this->form_values['first_name'] ? trim(str_replace(',', ' ', $this->form_values['first_name'])) : '';
			$last_name = $this->form_values['last_name'] ? trim(str_replace(',', ' ', $this->form_values['last_name'])) : '';
			$xname = array();
			if ( $last_name )
			{
				$xname[] = $last_name;
			}
			if ( $first_name )
			{
				$xname[] = $first_name;
			}
			$this->form_values['realname'] = $xname ? implode(', ', $xname) : '';
			$this->form_fields['realname'] = '';
		}
		if ( empty($this->form_values['realname']) )
		{
			trigger_error('err_user_realname_empty', E_USER_WARNING);
		}
		// check unicity
		else if ( !($ident = $sys->utf8->get_identifier($this->form_values['realname'])) )
		{
			trigger_error('err_user_realname_not_valid', E_USER_WARNING);
		}
		else
		{
			$sql = 'SELECT user_id
						FROM ' . $db->table('users') . '
						WHERE user_realname_ident = ' . $db->escape($ident) . (!$this->user->data ? '' : '
							AND user_id <> ' . intval($this->user->data['user_id']));
			$result = $db->query($sql, __LINE__, __FILE__);
			$exists = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $exists )
			{
				trigger_error('err_user_realname_exists', E_USER_WARNING);
			}
		}

		// email
		if ( empty($this->form_values['email']) )
		{
			trigger_error('err_user_email_empty', E_USER_WARNING);
		}
		else if ( !preg_match('#' . sys::preg_expr('email') . '#', $this->form_values['email']) )
		{
			trigger_error('err_user_email_not_valid', E_USER_WARNING);
		}
		else if ( $sys->ini_get('login.email.unique') )
		{
			$sql = 'SELECT user_id
						FROM ' . $db->table('users') . '
						WHERE user_email = ' . $db->escape($this->form_values['email']) . (!$this->user->data ? '' : '
							AND user_id <> ' . intval($this->user->data['user_id']));
			$result = $db->query($sql, __LINE__, __FILE__);
			$exists = $db->fetch($result) ? true : false;
			$db->free($result);
			if ( $exists )
			{
				trigger_error('err_user_email_exists', E_USER_WARNING);
			}
		}

		// langs
		if ( isset($this->form_values['lang']) && ($this->form_values['lang'] !== '') && (count($sys->lang->availables) > 1) )
		{
			if ( !isset($sys->lang->availables[ $this->form_values['lang'] ]) )
			{
				trigger_error('err_user_lang_not_available', E_USER_WARNING);
			}
		}
		else
		{
			$this->form_values['lang'] = '';
		}

		// timeshifts
		if ( $this->timeshifts && isset($this->form_values['timeshift']) )
		{
			if ( !isset($this->timeshifts[ $this->form_values['timeshift'] ]) )
			{
				trigger_error('err_user_timeshift_not_available', E_USER_WARNING);
			}
		}
		return true;
	}

	function validate()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$io = &$sys->io;
		$error = &$sys->error;
		$actor = &$api->user;
		$session = &$api->session;
		$db = &$api->db;

		if ( !$io->button('submit_form') || $error->warnings )
		{
			return false;
		}

		$this->activation_done = false;
		$this->notify_activation_done = false;
		$this->notify_activation_required = false;
		$this->notify_new_registration = false;
		$this->notify_password_changed = false;

		$message = '';
		$linkback = '';
		$fields = array();
		if ( ($api->mode != 'users.delete') && $this->form_values )
		{
			unset($this->form_values['first_name']);
			unset($this->form_values['last_name']);
			foreach ( $this->form_values as $name => $value )
			{
				$field_name = 'user_' . $name;
				if ( in_array($name, array('flat_password', 'enable')) )
				{
					continue;
				}
				if ( $name == 'password_new' )
				{
					$field_name = 'user_password';
				}
				$fields += array($field_name => $this->form_fields[$name] === '' ? (string) $value : intval($value));
			}

			// no activation: reset
			if ( !$this->activation_required )
			{
				$this->activation_done = true;
				$fields += array(
					'user_disabled' => 0,
					'user_actkey' => '',
				);
			}
			// only admin can have the field
			else if ( isset($this->form_values['enable']) )
			{
				if ( $this->form_values['enable'] )
				{
					$fields += array(
						'user_disabled' => 0,
						'user_actkey' => '',
					);
					$this->activation_done = true;
					if ( ($api->mode == 'users.edit') && $this->user->data && $this->user->data['user_disabled'] )
					{
						$this->notify_activation_done = true;
					}
				}
				else if ( !$this->user->data || ($this->user->data['user_id'] != $actor->data['user_id']) )
				{
					$at_guests = true;
					if ( $this->group_id && ($this->group_id != SYS_GROUP_GUESTS) )
					{
						$sql = 'SELECT p.group_id
									FROM ' . $db->table('groups') . ' n, ' . $db->table('groups') . ' p
									WHERE n.group_lid BETWEEN p.group_lid AND p.group_rid
										AND n.group_id = ' . intval($this->group_id) . '
										AND p.group_pid = 0';
						$result = $db->query($sql, __LINE__, __FILE__);
						$main_group = ($row = $db->fetch($result)) ? intval($row['group_id']) : false;
						$db->free($result);
						if ( $main_group != SYS_GROUP_GUESTS )
						{
							$at_guests = false;
						}
					}
					if ( !$at_guests && $this->group_id )
					{
						$this->group_id = SYS_GROUP_GUESTS;
					}
					$fields += array(
						'user_disabled' => 1,
					);
					if ( $api->mode == 'users.create' )
					{
						$fields += array(
							'user_actkey' => $this->_get_actkey(),
						);
						$this->notify_activation_required = true;
					}
				}
			}
			// in case of registration, create an activation key
			else if ( $api->mode == 'register' )
			{
				$fields += array(
					'user_disabled' => 1,
					'user_actkey' => $this->_get_actkey(),
				);
				$this->notify_activation_required = true;
			}
		}
		switch ( $api->mode )
		{
			case 'profile':
			case 'users.edit':
				$this->user->update($fields);
				if ( !$this->user->data['user_disabled'] )
				{
					$this->user->auto_group();
				}
				if ( $this->form_values && isset($this->form_values['password_new']) && !empty($this->form_values['password_new']) )
				{
					$this->notify_password_changed = true;
				}
				// set lang for the record (users_histo)
				if ( $actor->data && ($actor->data['user_id'] == $this->user->data['user_id']) && (!$actor->data['user_lang'] || ($actor->data['user_lang'] != $sys->lang->active)) )
				{
					$sys->lang->set_lang($actor->data['user_lang'] ? $actor->data['user_lang'] : false);
					$session->update();
					$actor->login_history();
				}
				$this->notify();
				$message = $api->mode == 'profile' ? 'user_updated_profile' : 'user_updated';
				$linkback = $api->mode == 'profile' ? array() : array('backto_users' => $this->group_id ? $api->url(array(SYS_U_MODE => 'groups.membership.group.content', SYS_U_ITEM => $this->group_id)) : $api->url(array(SYS_U_MODE => 'groups.membership')));
			break;
			case 'register':
			case 'users.create':
				$this->user->insert($fields, $api->mode == 'register' ? false : $this->group_id);
				if ( !$this->user->data['user_disabled'] )
				{
					$this->user->auto_group();
				}
				$this->notify_new_registration = true;
				$this->notify();
				$message = $api->mode == 'register' ? ($this->notify_activation_required ? 'user_created_profile_inactive' : 'user_created_profile') : 'user_created';
				$linkback = $api->mode == 'register' ? array() : array('backto_users' => $this->group_id ? $api->url(array(SYS_U_MODE => 'groups.membership.group.content', SYS_U_ITEM => $this->group_id)) : $api->url(array(SYS_U_MODE => 'groups.membership')));
			break;
			case 'users.delete':
				$this->user->delete();
				$message = 'user_deleted';
				$linkback = array('backto_users' => $this->group_id ? $api->url(array(SYS_U_MODE => 'groups.membership.group.content', SYS_U_ITEM => $this->group_id)) : $api->url(array(SYS_U_MODE => 'groups.membership')));
			break;
		}
		$api->feedback($message, $linkback);
		exit;
	}

	function notify()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		if ( !$this->notify_new_registration && !$this->notify_password_changed && !$this->notify_activation_required && !$this->notify_activation_done )
		{
			return false;
		}

		$class = $sys->ini_get('emailer', 'class');
		$mailengine = emailer::get_config('mailengine');
		$class = $sys->ini_get('emailer.' . $mailengine, 'class');
		$emailer = new $class();
		$emailer->set($this->api_name);

		if ( $this->notify_new_registration || $this->notify_password_changed )
		{
			$emailer->add(array(
				'URL' => preg_replace('#/$#', '', $sys->get_server_url(true)),
				'NAME' => sys_string::htmlspecialchars($this->user->data['user_name']),
				'PASSWORD' => sys_string::htmlspecialchars($this->form_values['flat_password']),
				'REALNAME' => sys_string::htmlspecialchars($this->user->data['user_realname']),
			));
			if ( $this->notify_activation_required )
			{
				$emailer->add('activation', array(
					'URI' => '/' . preg_replace('#^[\./]+#is', '', $api->url(array(SYS_U_MODE => 'login', SYS_U_ACTKEY => $this->user->data['user_actkey']), true)),
				));
			}
			$emailer->reset_recipients();
			$emailer->set_lang($this->user->data['user_lang'] ? $this->user->data['user_lang'] : $sys->lang->active);
			$emailer->set_recipient($this->user->data['user_email']);
			if ( $this->notify_new_registration && ($report_to = $sys->ini_get('register.copy')) )
			{
				$emailer->set_bcc($report_to);
			}
			$result = $emailer->send('notify');
		}
		else if ( $this->notify_activation_done )
		{
			$emailer->add(array(
				'URL' => preg_replace('#/$#', '', $sys->get_server_url(true)),
				'NAME' => sys_string::htmlspecialchars($this->user->data['user_name']),
				'REALNAME' => sys_string::htmlspecialchars($this->user->data['user_realname']),
			));
			$emailer->reset_recipients();
			$emailer->set_lang($this->user->data['user_lang'] ? $this->user->data['user_lang'] : $sys->lang->active);
			$emailer->set_recipient($this->user->data['user_email']);
			$result = $emailer->send('notify.activation');
		}
		sys::kill($emailer);
		unset($emailer);
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;
		$user = &$api->user;

		if ( isset($this->form_values['password_new']) )
		{
			unset($this->form_values['password_new']);
		}
		$tpl_data = array();
		if ( $this->form_values )
		{
			foreach ( $this->form_values as $name => $value )
			{
				if ( $name == 'enable' )
				{
					$tpl_data += array(
						'ENABLE' => intval($value),
					);
					$tpl->add('activation');
				}
				else
				{
					$tpl_data += array(strtoupper($name) => sys_string::htmlspecialchars($value));
				}
			}
		}
		$tpl_data += array(
			'NEW' => in_array($api->mode, array('register', 'users.create')),
			'SELF' => !$user->data || !$user->data['user_id'] || ($user->data['user_id'] == $this->user->data['user_id']),
		);

		$tpl->add($tpl_data);

		// language
		if ( isset($this->form_values['lang']) && $sys->lang->availables && (count($sys->lang->availables) > 1) )
		{
			foreach ( $sys->lang->availables as $key => $desc )
			{
				$tpl->add('langs', array(
					'VALUE' => sys_string::htmlspecialchars($key),
					'DESC' => sys_string::htmlspecialchars($desc),
				));
			}
		}

		// timezone
		if ( $this->timeshifts && isset($this->form_values['timeshift']) )
		{
			foreach ( $this->timeshifts as $value => $desc )
			{
				$tpl->add('timeshifts', array(
					'VALUE' => sys_string::htmlspecialchars($value),
					'DESC' => sys_string::htmlspecialchars($desc),
				));
			}
		}

		// and finaly hide main ids
		$tpl->hide(array(
			SYS_U_USER => $this->user ? $this->user->data['user_id'] : false,
			SYS_U_GROUP => $this->group_id ? $this->group_id : false,
		));
		return 'users.edit.form';
	}

	function _get_actkey()
	{
		return md5(uniqid(mt_rand(), true));
	}
}

?>