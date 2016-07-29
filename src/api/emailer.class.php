<?php
//
//	file: inc/emailer.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 30/06/2009
//	version: 0.0.1 - 30/06/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class emailer extends sys_stdclass
{
	var $sender;
	var $recipients;
	var $cc;
	var $bcc;
	var $lang;
	var $tpl_lang_path;

	function __construct()
	{
		parent::__construct();
		$this->sender = false;
		$this->recipients = array();
		$this->cc = array();
		$this->bcc = array();
		$this->tpl_lang_path = false;
	}

	function __destruct()
	{
		unset($this->tpl_lang_path);
		unset($this->bcc);
		unset($this->cc);
		unset($this->recipients);
		unset($this->sender);
		parent::__destruct();
	}

	// static: return mail engine
	function get_config($configvar)
	{
		static $done, $mailengine, $mailsender, $smtphost, $smtpuser, $smtppassword;
		$sys = &$GLOBALS[SYS];

		if ( !$done && ($done = true) )
		{
			if ( !($filename = $sys->ini_get('mail.config', 'file')) )
			{
				trigger_error('Sender email address configuration file not found', E_USER_NOTICE);
				return false;
			}
			$mailengine = $mailsender = $smtphost = $smtpport = $smtpuser = $smtppassword = false;
			include($filename);
		}

		switch ( $configvar )
		{
			case 'mailengine':
				return $mailengine;
			case 'mailsender':
				return $mailsender;
			case 'smtphost':
				return $smtphost;
			case 'smtport':
				return $smtpport;
			case 'smtpuser':
				return $smtpuser;
			case 'smtppassword':
				return $smtppassword;
			default:
				return false;
		}
	}

	function set_sender($email_address)
	{
		$this->sender = $email_address;
	}

	function reset_recipients()
	{
		$this->recipients = array();
		$this->cc = array();
		$this->bcc = array();
	}

	function set_lang($lang)
	{
		$this->lang = $lang;
	}

	function set_recipient($email_address)
	{
		if ( !$email_address )
		{
			return;
		}
		if ( !is_array($email_address) )
		{
			$email_address = array($email_address);
		}
		$this->recipients = array_keys(($this->recipients ? array_flip($this->recipients) : array()) + array_flip($email_address));
	}

	function set_cc($email_address)
	{
		if ( !$email_address )
		{
			return;
		}
		if ( !is_array($email_address) )
		{
			$email_address = array($email_address);
		}
		$this->cc = array_keys(($this->cc ? array_flip($this->cc) : array()) + array_flip($email_address));
	}

	function set_bcc($email_address)
	{
		if ( !$email_address )
		{
			return;
		}
		if ( !is_array($email_address) )
		{
			$email_address = array($email_address);
		}
		$this->bcc = array_keys(($this->bcc ? array_flip($this->bcc) : array()) + array_flip($email_address));
	}

	function set_tpl_lang_path($tpl_lang_path)
	{
		$this->tpl_lang_path = $tpl_lang_path;
	}

	function add()
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		$args = func_get_args();
		return call_user_func_array(array(&$tpl, 'add'), $args);
	}

	function get_parse($tpl_name)
	{
		$sys = &$GLOBALS[SYS];

		$tpl = $sys->tpl;
		$tpl->register_tpl(($this->tpl_lang_path === false ? $sys->root . 'languages/' : str_replace('//', '/', $this->tpl_lang_path . '/')) . $sys->lang->default . '/mails/');
		$tpl->register_tpl(($this->tpl_lang_path === false ? $sys->root . 'languages/' : str_replace('//', '/', $this->tpl_lang_path . '/')) . $this->lang . '/mails/');
		$tpl->set_debug(false);
		ob_start();
		$tpl->parse($tpl_name);
		$message = ob_get_contents();
		ob_end_clean();
		$tpl->set_debug($debug);
		unset($tpl);

		return $message;
	}

	function send($tpl_name)
	{
	}
}

?>