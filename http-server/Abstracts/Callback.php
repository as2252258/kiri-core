<?php


namespace HttpServer\Events\Abstracts;


use Exception;
use HttpServer\Application;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Error\Logger;
use Snowflake\Event;
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
		$event = Snowflake::get()->event;

		$event->offName(Event::EVENT_AFTER_REQUEST);
		$event->offName(Event::EVENT_BEFORE_REQUEST);
		$this->eventNotify($message, $event);

		Snowflake::clearProcessId($server->worker_pid);

		$logger = Snowflake::get()->getLogger();
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
	 * @param $email
	 * @param $nickname
	 * @param $message
	 * @throws
	 */
	protected function system_mail($email, $nickname, $message)
	{
		$mail = new PHPMailer(true);
		try {
			//Server settings
			$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
			$mail->isSMTP();                                            // Send using SMTP
			$mail->Host = 'smtp1.example.com';                          // Set the SMTP server to send through
			$mail->SMTPAuth = true;                                     // Enable SMTP authentication
			$mail->Username = 'user@example.com';                       // SMTP username
			$mail->Password = 'secret';                                 // SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
			$mail->Port = 587;                                          // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

			//Recipients
			$mail->setFrom('system@example.com', '系统管理员');
			$mail->addAddress($email, $nickname);                 // Add a recipient

			// Attachments
//			$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//			$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

			// Content
			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject = 'Here is the subject';
			$mail->Body = $message;
			$mail->AltBody = $message;

			$mail->send();
		} catch (Exception $e) {
			$this->addError($e->getMessage(),'email');
		}
	}

}
