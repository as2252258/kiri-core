<?php
declare(strict_types=1);

namespace HttpServer\Abstracts;


use Database\Connection;
use Exception;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;


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
	 * @param array $clientInfo
	 * @param string $event
	 * @return string
	 */
	protected function getName(array $clientInfo, string $event): string
	{
		return 'listen ' . $clientInfo['server_port'] . ' ' . Event::SERVER_CONNECT;
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
