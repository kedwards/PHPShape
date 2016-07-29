<?php
//
//	file: inc/emailer.smtp.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 08/09/2009
//	version: 0.0.2 - 06/10/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//
// This one is in its great part based on phpBB 2 smtp class
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class emailer_smtp extends emailer
{
	function send($tpl_name)
	{
		$sys = &$GLOBALS[SYS];

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
		$headers = '';

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
		$message = str_replace('\n', '', $message);

		// linefeed
		$lf = "\r\n";

		// email header
		$headers .= 'MIME-Version: 1.0' . $lf;
		$headers .= 'Content-type: ' . ($is_html ? 'text/html' : 'text/plain') . '; charset=utf-8' . $lf;

		// from, cc, bcc
		$headers .= 'From: ' . $this->sender . $lf;
		if ( $this->cc )
		{
			$headers .= 'Cc: ' . implode(', ', $this->cc) . $lf;
		}
		// bcc are not part of the header
		if ( $this->bcc )
		{
			//$headers .= 'Bcc: ' . implode(', ', $this->bcc) . $lf;
		}

		// fix any bare linefeeds in the message to make it RFC821 Compliant.
		$message = preg_replace("#(?<!\r)\n#si", $lf, $message);

		// and finaly output the mail
		// open channel
		if( !($socket = @fsockopen($this->get_config('smtphost'), $this->get_config('smtpport'), $errno, $errstr, 20)) )
		{
			trigger_error('Could not connect to smtp host : ' . $errno . ' : ' . $errstr, E_USER_NOTICE);
			return false;
		}
		$this->server_response($socket, '220');

		// Do we want to use AUTH ?, send RFC2554 EHLO, else send RFC821 HELO
		// This improved as provided by SirSir to accomodate
		if( $this->get_config('smtpuser') && $this->get_config('smtppassword') )
		{
			@fwrite($socket, 'EHLO ' . $this->get_config('smtphost') . $lf);
			$this->server_response($socket, '250');

			@fwrite($socket, 'AUTH LOGIN' . $lf);
			$this->server_response($socket, '334');

			@fwrite($socket, base64_encode($this->get_config('smtpuser')) . $lf);
			$this->server_response($socket, '334');

			@fwrite($socket, base64_encode($this->get_config('smtppassword')) . $lf);
			$this->server_response($socket, '235');
		}
		else
		{
			@fwrite($socket, 'HELO ' . $this->get_config('smtphost') . $lf);
			$this->server_response($socket, '250');
		}

		// Specify who the mail is from....
		@fwrite($socket, 'MAIL FROM: <' . $this->sender . '>' . $lf);
		$this->server_response($socket, '250');

		// recipients
		$recipients = $this->recipients;
		$main_recipient = $recipients ? trim(array_shift($recipients)) : 'Undisclosed-recipients:;';
		if ( strpos($main_recipient, '@') !== false )
		{
			@fwrite($socket, 'RCPT TO: <' . $main_recipient . '>' . $lf);
			$this->server_response($socket, '250');
		}

		// other recipients
		if ( $recipients )
		{
			foreach ( $recipients as $recipient )
			{
				@fwrite($socket, 'RCPT TO: <' . $recipient . '>' . $lf);
				$this->server_response($socket, '250');
			}
		}

		// bcc
		if ( $this->bcc )
		{
			foreach ( $this->bcc as $bcc )
			{
				@fwrite($socket, 'RCPT TO: <' . $bcc . '>' . $lf);
				$this->server_response($socket, '250');
			}
		}

		// cc
		if ( $this->cc )
		{
			foreach ( $this->cc as $cc )
			{
				@fwrite($socket, 'RCPT TO: <' . $cc . '>' . $lf);
				$this->server_response($socket, '250');
			}
		}

		// Ok now we tell the server we are ready to start sending data
		@fwrite($socket, 'DATA' . $lf);
		$this->server_response($socket, '354');

		@fwrite($socket, 'Subject: ' . $subject . $lf);
		@fwrite($socket, 'To: ' . $main_recipient . $lf);
		@fwrite($socket, $headers . $lf . $lf);
		@fwrite($socket, $message . $lf);

		@fwrite($socket, '.' . $lf);
		$this->server_response($socket, '250');
		@fwrite($socket, 'QUIT' . $lf);
		@fclose($socket);

		return $message;
	}

	function server_response($socket, $expected_response)
	{
		$server_response = '';
		while ( (substr($server_response, 3, 1) != ' ') )
		{
			if ( !($server_response = @fgets($socket, 256)) )
			{
				trigger_error('Failed to obtain response from mail server.', E_USER_ERROR);
			}
		}
		if ( (substr($server_response, 0, 3) != $expected_response) )
		{
			trigger_error('Failed to send the mail: error: ' . $server_response, E_USER_ERROR);
		}
	}
}

?>