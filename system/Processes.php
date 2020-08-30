<?php


namespace Snowflake;


use Exception;
use HttpServer\ServerManager;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Abstracts\Component;
use Swoole\Process;
use Swoole\Process\Pool;
use Swoole\Timer;

/**
 * Class Processes
 * @package Snowflake
 */
class Processes extends Component
{

	public $processes = [];


	/**
	 * 构建服务
	 * @throws Exception
	 */
	public function initCore()
	{
		$application = Snowflake::get();
		$server = $application->set(Pool::class, new Pool($this->size(), SWOOLE_IPC_UNIXSOCK));
		$server->on('workerStart', function (Pool $pool, int $workerId) use ($application) {
			ServerManager::create($pool, $this->processes[$workerId], $application, $workerId);
		});
		$server->on('workerStop', function (Pool $pool, int $workerId) {
			$event = Snowflake::get()->event;
			if ($event->exists(Event::PROCESS_WORKER_STOP)) {
				$event->trigger(Event::PROCESS_WORKER_STOP);
			}
		});
		$server->on('message', function ($pool, $message) {
			file_put_contents('a.log', func_get_args());
		});
		return $server;
	}


	protected function system_mail()
	{
		try {
			$mail = new PHPMailer(true);
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
			$mail->setFrom('from@example.com', 'Mailer');
			$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
			$mail->addAddress('ellen@example.com');               // Name is optional
			$mail->addReplyTo('info@example.com', 'Information');
			$mail->addCC('cc@example.com');
			$mail->addBCC('bcc@example.com');

			// Attachments
			$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

			// Content
			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject = 'Here is the subject';
			$mail->Body = 'This is the HTML message body <b>in bold!</b>';
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			$mail->send();
			echo 'Message has been sent';
		} catch (Exception $e) {
			echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	}

	/**
	 * @return int
	 */
	protected function size()
	{
		return count($this->processes);
	}


	/**
	 * @throws Exception
	 */
	public function start()
	{
		$server = $this->initCore();
		$server->start();
	}


	/**
	 * @param array $servers
	 * @return $this
	 */
	public function push(array $servers)
	{
		foreach ($servers as $server) {
			$this->processes[] = $server;
		}
		return $this;
	}
}
