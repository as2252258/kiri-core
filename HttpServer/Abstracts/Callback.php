<?php
declare(strict_types=1);

namespace HttpServer\Abstracts;


use Database\Connection;
use Exception;
use HttpServer\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Error\LoggerProcess;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Process;
use Swoole\Server;


/**
 * Class Callback
 * @package HttpServer\Abstracts
 */
abstract class Callback extends HttpService
{



    const EVENT_ERROR = 'WORKER:ERROR';
    const EVENT_STOP = 'WORKER:STOP';
    const EVENT_EXIT = 'WORKER:EXIT';


    private array $_MESSAGE = [
        self::EVENT_ERROR => 'The server error. at No.',
        self::EVENT_STOP  => 'The server stop. at No.',
        self::EVENT_EXIT  => 'The server exit. at No.',
    ];



    /**
     * @return PHPMailer
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws ConfigException
     */
    private function createEmail(): PHPMailer
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
	 * @param $fd
	 * @param $data
	 * @param $reID
	 * @return Request
	 * @throws Exception
	 */
	protected function _request($fd, $data, $reID): Request
	{
		return Request::createListenRequest($fd, $data, $reID);
	}

	/**
	 * @param $messageContent
	 * @throws Exception
	 */
    protected function system_mail($messageContent)
    {
        try {
	        $email = Config::get('email');
	        if (empty($email) || !$email['enable']) {
		        return;
	        }
	        $transport = (new \Swift_SmtpTransport($email['host'], $email['465']))
		        ->setUsername($email['username'])
		        ->setPassword($email['password']);
	        $mailer = new \Swift_Mailer($transport);

	        // Create a message
	        $message = (new \Swift_Message('Wonderful Subject'))
		        ->setFrom([$email['send']['address'] => $email['send']['nickname']])
		        ->setBody('Here is the message itself');

	        foreach ($email['receive'] as $item) {
		        $message->setTo([$item['address'], $item['address'] => $item['nickname']]);
	        }
	        $mailer->send($messageContent);
        } catch (\Throwable $e) {
            $this->addError($e, 'email');
        }
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    protected function clearMysqlClient()
    {
        $databases = Config::get('databases', []);
        if (empty($databases)) {
            return;
        }
        $application = Snowflake::app();
        foreach ($databases as $name => $database) {
            /** @var Connection $connection */
            $connection = $application->get('databases.' . $name, false);
            if (empty($connection)) {
                continue;
            }
            $connection->disconnect();
        }
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    protected function clearRedisClient()
    {
        $redis = Snowflake::app()->getRedis();
        $redis->destroy();
    }

}
