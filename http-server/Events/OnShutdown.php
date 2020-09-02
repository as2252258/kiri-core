<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Config;
use Snowflake\Core\JSON;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Server;
use Closure;

/**
 * Class OnShutdown
 * @package HttpServer\Events
 */
class OnShutdown extends Callback
{

	/**
	 * @param Server $server
	 * @throws ConfigException
	 */
	public function onHandler(Server $server)
	{
		$email = Config::get('email');
		$nickname = Config::get('nickname');

		$this->system_mail($email, $nickname);
	}


	/**
	 * @param $email
	 * @param $nickname
	 */
	protected function system_mail($email, $nickname)
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
			$mail->setFrom('system@example.com', '系统管理员');
			$mail->addAddress($email, $nickname);                 // Add a recipient

			// Attachments
//			$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//			$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

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


}
