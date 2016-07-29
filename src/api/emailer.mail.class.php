<?php
//
//	file: inc/emailer.mail.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 30/06/2009
//	version: 0.0.1 - 30/06/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

class emailer_mail extends emailer
{
	function send($tpl_name)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];

		if ( !$this->sender )
		{
			$this->sender = $this->get_config('mailsender');
		}
		if ( !$this->recipients || !$this->sender )
		{
			trigger_error('Mail parameter incomplete', E_USER_ERROR);
		}

		$message = $this->get_parse($tpl_name);
		$subject = '';

		// guess subject
		$is_html = preg_match('#<html>#is', $message);
		$mask = $is_html ? '#<title>(.*?)</title>#si' : '#^(.*?)\\n(.*)#is';
		$matches = array();
		if ( preg_match($mask, $message, $matches) )
		{
			$subject = str_replace('\n', '', $matches[1]);
			unset($matches[0]);
			unset($matches[1]);
			$message = implode("\n\r", $matches);
		}
		$message = str_replace(array('\n', '\s', '\t'), array('', ' ', '   '), $message);

		// email header
		$domain = explode('@', $this->sender);
		$headers = array();
		$headers[] = 'Reply-To: ' . $this->sender;
		$headers[] = 'Return-Path: <' . $this->sender . '>';
		$headers[] = 'Sender: <' . $this->sender . '>';
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Message-ID: <' . md5(uniqid(time())) . '@' . $domain[1] . '>';
		$headers[] = 'Date: ' . date('r', time());
		$headers[] = 'Content-type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=utf-8';
		$headers[] = 'Content-Transfer-Encoding: 8bit';

		// from, cc, bcc
		$headers[] = 'From: ' . $this->sender . ' <' . $this->sender . '>';
		if ( $this->cc )
		{
			$headers[] = 'Cc: ' . implode(', ', $this->cc);
		}
		if ( $this->bcc )
		{
			$headers[] = 'Bcc: ' . implode(', ', $this->bcc);
		}
		$headers = implode("\n", $headers);

		// and finaly output the mail
		if ( !$api->debug_mode )
		{
			mail(implode(', ', $this->recipients), $subject, $message, $headers);
		}
		else
		{
			_dump(array('headers' => $headers, 'recipients' => implode(', ', $this->recipients), 'subject' => $subject, 'text' => $message));
		}
		return $message;
	}
}

?>