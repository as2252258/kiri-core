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
     * @param $message
     * @throws
     */
    protected function system_mail($message)
    {
        try {
            if (!Config::get('email.enable', false)) {
                return;
            }
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
