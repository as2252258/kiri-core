<?php


namespace HttpServer\Events\Abstracts;


use Exception;
use HttpServer\Application;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Config;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Timer;

abstract class Callback extends Application
{


	/**
	 * @param $server
	 * @param $worker_id
	 * @param $message
	 * @throws Exception
	 */
	protected function clear($server, $worker_id, $message)
	{
		Timer::clearAll();
		$event = Snowflake::app()->event;

		$event->offName(Event::EVENT_AFTER_REQUEST);
		$event->offName(Event::EVENT_BEFORE_REQUEST);
		$this->eventNotify($message, $event);

		Snowflake::clearProcessId($server->worker_pid);

		$logger = Snowflake::app()->getLogger();
		$logger->write($this->_MESSAGE[$message] . $worker_id);
		$logger->clear();
	}


	const EVENT_ERROR = 'WORKER:ERROR';
	const EVENT_STOP = 'WORKER:STOP';
	const EVENT_EXIT = 'WORKER:EXIT';


	private $_MESSAGE = [
		self::EVENT_ERROR => 'The server error. at No.',
		self::EVENT_STOP  => 'The server stop. at No.',
		self::EVENT_EXIT  => 'The server exit. at No.',
	];

	/**
	 * @param $message
	 * @param $event
	 */
	private function eventNotify($message, $event)
	{
		switch ($message) {
			case self::EVENT_ERROR:
				if (!$event->exists(Event::SERVER_WORKER_ERROR)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_ERROR);
				break;
			case self::EVENT_EXIT:
				if (!$event->exists(Event::SERVER_WORKER_EXIT)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_EXIT);
				break;
			case self::EVENT_STOP:
				if (!$event->exists(Event::SERVER_WORKER_STOP)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_STOP);
				break;
		}
	}


	/**
	 * @return PHPMailer
	 * @throws \PHPMailer\PHPMailer\Exception
	 * @throws ConfigException
	 */
	private function createEmail()
	{
		$mail = new PHPMailer(true);
		$mail->SMTPDebug = SMTP::DEBUG_SERVER;                                                // Enable verbose debug output
		$mail->isSMTP();                                                                      // Send using SMTP
		$mail->Host = Config::get('email.host');                                              // Set the SMTP server to send through
		$mail->SMTPAuth = true;                                                               // Enable SMTP authentication
		$mail->Debugoutput = false;                                                           // Enable SMTP authentication
		$mail->CharSet = "UTF8";                                                              // Enable SMTP authentication
		$mail->Username = Config::get('email.username');                                      // SMTP username
		$mail->Password = Config::get('email.password');                                      // SMTP password
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;                                      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
		$mail->Port = Config::get('email.port');                                              // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
		$mail->setFrom(Config::get('email.send.address'), Config::get('email.send.nickname'));
		return $mail;
	}


	/**
	 * @param $message
	 * @throws
	 */
	protected function system_mail($message)
	{
		try {
			$mail = $this->createEmail();
			$receives = Config::get('email.receive');
			if (empty($receives) || !is_array($receives)) {
				throw new Exception('接收人信息错误');
			}
			foreach ($receives as $receive) {
				$mail->addAddress($receive['address'], $receive['nickname']);                 // Add a recipient
			}
			$mail->isHTML(true);                                                                                            // Set email format to HTML
			$mail->Subject = 'service error';
			$mail->Body = $message;
			$mail->AltBody = $message;
			$mail->send();
		} catch (Exception $e) {
			$this->addError($e->getMessage(), 'email');
		}
	}

}
