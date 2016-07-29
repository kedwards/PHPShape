<?php
//
//	file: api.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 29/01/2008
//	version: 0.0.2 - 19/11/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class api extends sys_page
{
	var $user;
	var $mode;
	var $ajax;
	var $mode_base;
	var $mode_sub;
	var $mode_action;
	var $modes;
	var $crons;
	var $hooks;
	var $handle;
	var $ignore_cancel;
	var $debug_mode;

	var $page_header_set;

	function __construct()
	{
		parent::__construct();
		$this->user = false;
		$this->mode = false;
		$this->ajax = false;
		$this->mode_base = false;
		$this->mode_sub = false;
		$this->mode_action = false;
		$this->modes = array();
		$this->crons = array();
		$this->hooks = false;
		$this->handle = false;
		$this->ignore_cancel = false;
		$this->debug_mode = false;
		$this->page_header_set = false;
	}

	function __destruct()
	{
		unset($this->page_header);
		unset($this->debug_mode);
		unset($this->ignore_cancel);
		unset($this->handle);
		if ( isset($this->hooks) && is_object($this->hooks) )
		{
			sys::kill($this->hooks);
			unset($this->hooks);
		}
		unset($this->crons);
		unset($this->modes);
		unset($this->mode_action);
		unset($this->mode_sub);
		unset($this->mode_base);
		unset($this->ajax);
		unset($this->mode);
		if ( isset($this->user) && is_object($this->user) )
		{
			sys::kill($this->user);
			unset($this->user);
		}
		parent::__destruct();
	}

	function set($api_name, $root, $requester)
	{
		parent::set($api_name, $root, $requester);

		$sys = &$GLOBALS[SYS];

		$sys->lang->register('api');
		$sys->tpl->set_cache(false);
		if ( !isset($sys->rstats) || !is_object($sys->rstats) )
		{
			$sys->tpl->set_cache(true, true);
		}
		$sys->tpl->register_ini($sys->root . 'styles/api.ini' . $sys->ext);

		$this->set_debug_mode();

		$this->hooks->process('api.set');
	}

	function set_debug_mode()
	{
		$sys = &$GLOBALS[SYS];

		$this->debug_mode = $sys->ini_get('rstats');
	}

	function ini_set()
	{
		$sys = &$GLOBALS[SYS];

		parent::ini_set();

		// base api (actor, users, groups)
		$sys->ini_load('ROOT/api/api.ini');

		// instantiate api components: hooks
		$class = $sys->ini_get('hooks.processor', 'class');
		$this->hooks = new $class();
		$this->hooks->set($this->api_name);

		$this->modes = $sys->ini_get('modes');
		$sys->ini_unset('modes');

		$this->crons = $sys->ini_get('crons');
		$sys->ini_unset('crons');

		$this->hooks->register($sys->ini_get('hooks'));
		$sys->ini_unset('hooks');

		// the real api
		if ( ($files = func_get_args()) )
		{
			foreach ( $files as $file )
			{
				$this->ini_load($file);
			}
		}

		// plug-ins
		if ( @file_exists($sys->root . 'plugs/') && is_dir($sys->root . 'plugs/') )
		{
			$dir_handler = opendir($sys->root . 'plugs/');
			while ( ($fname = readdir($dir_handler)) )
			{
				if ( is_file($sys->root . 'plugs/' . $fname) && preg_match('#\.ini' . preg_quote($sys->ext, '#') . '$#i', $fname) )
				{
					$this->ini_load($sys->root . 'plugs/' . sys_string::substr($fname, 0, -sys_string::strlen($sys->ext)));
				}
			}
			closedir($dir_handler);
		}
	}

	function ini_load($file)
	{
		$sys = &$GLOBALS[SYS];

		// get the file
		$sys->ini_load($file);

		// modes
		if ( ($modes = $sys->ini_get('modes')) )
		{
			$this->modes = array_merge($this->modes, $modes);
			$sys->ini_unset('modes');
		}

		// crons
		if ( ($crons = $sys->ini_get('crons')) )
		{
			$this->crons = $this->crons ? array_merge($this->crons, $crons) : $crons;
			$sys->ini_unset('crons');
		}

		// hooks
		$this->hooks->register($sys->ini_get('hooks'));
		$sys->ini_unset('hooks');
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

		// get the user
		$class = $sys->ini_get('actor', 'class');
		$this->user = new $class();
		$this->user->set($this->api_name);
		$this->user->read_groups();
		$this->user->set_lang();

		return true;
	}

	function check()
	{
		$sys = &$GLOBALS[SYS];

		// get the mode
		$mode = $sys->io->read(SYS_U_MODE, '');
		$this->mode = $mode ? preg_replace('#\.x$#', '', $mode) : '';
		$this->ajax = $this->mode && ($mode != $this->mode);
		unset($mode);

		$this->ignore_cancel = false;
		if ( !$this->user->data && ($except = $sys->ini_get('login.forced')) && (!in_array($this->mode, $except['except']) || $sys->io->button('cancel_form')) )
		{
			$this->mode = $except['login'];
			$this->ignore_cancel = true;
		}
		if ( !$this->mode )
		{
			$this->mode = $sys->ini_get('default.mode');
		}
		$this->slice_mode();
	}

	function slice_mode()
	{
		$slices = explode('.', $this->mode);
		$this->mode_base = array_shift($slices);
		$this->mode_sub = $slices ? array_shift($slices) : '';
		$this->mode_action = $slices ? implode('.', $slices) : '';
	}

	function validate()
	{
		$sys = &$GLOBALS[SYS];

		$mode = $this->mode;
		$this->handle = false;
		if ( $this->mode && ($class = $sys->ini_get($this->modes[$this->mode], 'class')) )
		{
			$handler = new $class();
			$handler->set($this->api_name);
			$this->handle = $handler->process();
			sys::kill($handler);
			unset($handler);
		}
		if ( !$this->handle && ($mode != $this->mode) )
		{
			if ( !$this->mode )
			{
				$this->mode = $sys->ini_get('default.mode');
				$this->slice_mode();
			}
			$this->validate();
		}
	}

	function display($handle=false)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( $handle )
		{
			$this->handle = $handle;
		}

		if ( $this->ajax && !$this->handle )
		{
			return false;
		}

		// default
		if ( !$this->handle )
		{
			trigger_error('err_not_authorized', E_USER_ERROR);
		}

		// display
		if ( $this->ajax )
		{
			$tpl->set_debug(false);
		}
		return parent::display($this->handle);
	}

	function page_header()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		if ( $this->page_header )
		{
			return false;
		}
		$this->page_header = true;

		// generate cookie
		if ( $this->session && $this->user )
		{
			if ( $this->session->created || $this->session->updated || $this->session->deleted || ($this->session->channel !== '_COOKIE') )
			{
				$unset = !$this->user->data || !isset($this->user->data['user_id']) || !intval($this->user->data['user_id']);
				$this->session->setcookie($unset);
				if ( $unset || $this->user->login_id )
				{
					$this->user->setcookie($unset);
				}
			}
		}

		// send page header vars
		parent::page_header();

		// page title
		$tpl_data = array(
			'AJAX' => $this->ajax,
			'MODE_BASE' => $this->mode_base,
			'MODE_ACTION' => $this->mode_action,
			'MODE_SUB' => $this->mode_sub,
			'MODE_FULL' => $this->mode . ($this->ajax ? '.x' : ''),
			'ROOT' => $sys->root,
		);
		if ( $this->user )
		{
			$this->user->display();
			if ( $this->user->data )
			{
				// refresh time ?
				$tpl_data += array(
					'NEW_SESSION' => ($this->session->created || (isset($this->session->data['session_first_hit']) && $this->session->data['first_hit'])) && !$this->ajax,
					'NEW_SESSION_TSHIFT' => $this->user->timeshift(),
					'NEW_SESSION_TSHIFT_DISABLE' => intval($this->user->data['user_timeshift_disable']),
				);
			}
		}
		$mode = $this->mode;
		if ( $mode == $sys->ini_get('default.mode') )
		{
			$mode = '';
		}
		if ( $mode )
		{
			$tpl_data += array(
				'MODE' => $mode,
			);
		}
		$tpl->add($tpl_data);

		// session id
		if ( $this->session->id )
		{
			$tpl->hide(array(
				$this->session->sid_name => $this->session->id,
			));
			$tpl->add(array(
				'SESSION_KEY' => $this->session->sid_name,
				'SESSION_ID' => $this->session->id,
			));
		}
		if ( $mode )
		{
			$tpl->hide(array(
				SYS_U_MODE => $mode,
			));
		}

		// send menus
		if ( ($class = $sys->ini_get('api.menus', 'class')) )
		{
			$menus = new $class();
			$menus->set($this->api_name);
			$menus->process();
			sys::kill($menus);
			unset($menus);
		}
	}

	// debug
	function log($str, $reset=false)
	{
		$sys = &$GLOBALS[SYS];
		if ( !$sys->rstats || !is_object($sys->rstats) )
		{
			return false;
		}
		$fname = 'log.txt';
		if ( $reset )
		{
			sys::write(sys::realpath($fname), $str);
		}
		else
		{
			file_put_contents($fname, $str . "\n", FILE_APPEND);
		}
		return true;
	}
}

?>