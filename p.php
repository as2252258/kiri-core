<?php


ini_set('memory_limit', '3096M');

use Snowflake\Application;

require_once __DIR__ . '/vendor/autoload.php';

\Swoole\Coroutine\run(function (){
	$client = HttpServer\Client\Client::NewRequest();
	$client->setHost('127.0.0.1');
	$client->setPort(9628);
	$client->setErrorField('code');
	$client->setErrorMsgField('message');
	var_dump($client->sendTo('', [],SWOOLE_UDP)->getMessage());

	$client = HttpServer\Client\Client::NewRequest();
	$client->setHost('127.0.0.1');
	$client->setPort(9629);
	$client->setErrorField('code');
	$client->setErrorMsgField('message');
	var_dump($client->sendTo('', [],SWOOLE_TCP)->getMessage());

	$client = HttpServer\Client\Client::NewRequest();
	$client->setHost('127.0.0.1');
	$client->setPort(5555);
	$client->setErrorField('code');
	$client->setErrorMsgField('message');
	var_dump($client->sendTo('', [],SWOOLE_SOCK_TCP)->getMessage());

	$client = HttpServer\Client\Client::NewRequest();
	$client->setHost('127.0.0.1');
	$client->setPort(9527);
	$client->setErrorField('code');
	$client->setErrorMsgField('message');
	var_dump($client->send('', [])->getMessage());
});
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
	//Server settings
	$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;                      // Enable verbose debug output
	$mail->isSMTP();                                            // Send using SMTP
	$mail->Host       = 'smtp1.example.com';                    // Set the SMTP server to send through
	$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
	$mail->Username   = 'user@example.com';                     // SMTP username
	$mail->Password   = 'secret';                               // SMTP password
	$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
	$mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

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
	$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
	$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

	$mail->send();
	echo 'Message has been sent';
} catch (Exception $e) {
	echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

