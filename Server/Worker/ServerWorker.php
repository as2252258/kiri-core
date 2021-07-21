<?php

namespace Server\Worker;

use Exception;
use Psr\Log\Test\TestLogger;
use Server\Constant;
use Server\ServerManager;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Runtime;
use Snowflake\Snowflake;
use Swoole\Server;
use Swoole\Timer;


/**
 * Class ServerWorker
 * @package Server\Worker
 */
class ServerWorker extends \Server\Abstracts\Server
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerStart(Server $server, int $workerId)
	{
		$this->_setConfigCache($workerId);
		$annotation = Snowflake::app()->getAnnotation();
		$annotation->read(APP_PATH . 'app');

		$this->runEvent(Constant::WORKER_START, null, [$server, $workerId]);
		if ($workerId >= $server->setting['worker_num'] + 1) {
			$loader = Snowflake::app()->getRouter();
			$loader->_loader();

			$annotation->runtime(CONTROLLER_PATH);
			$annotation->runtime(MODEL_PATH);
		} else {
			$annotation->runtime(APP_PATH, [CONTROLLER_PATH]);
		}
	}


	/**
	 * @param $worker_id
	 * @throws Exception
	 */
	private function _setConfigCache($worker_id)
	{
		putenv('state=start');
		putenv('worker=' . $worker_id);

		$serialize = file_get_contents(storage(Runtime::CONFIG_NAME));
		if (empty($serialize)) {
			return;
		}
		Config::sets(unserialize($serialize));
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerStop(Server $server, int $workerId)
	{
		$this->runEvent(Constant::WORKER_STOP, null, [$server, $workerId]);

		Event::trigger(Event::SERVER_WORKER_STOP);

		fire(Event::SYSTEM_RESOURCE_CLEAN);

		Timer::clearAll();
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerExit(Server $server, int $workerId)
	{
		$this->runEvent(Constant::WORKER_EXIT, null, [$server, $workerId]);

		putenv('state=exit');

		Event::trigger(Event::SERVER_WORKER_EXIT, [$server, $worker_id]);

		Snowflake::getApp('logger')->insert();
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 * @throws Exception
	 */
	public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
		$this->runEvent(Constant::WORKER_ERROR, null, [$server, $worker_id, $worker_pid, $exit_code, $signal]);

		Event::trigger(Event::SERVER_WORKER_ERROR);

		$message = sprintf('Worker#%d::%d error stop. signal %d, exit_code %d, msg %s',
			$worker_id, $worker_pid, $signal, $exit_code, swoole_strerror(swoole_last_error(), 9)
		);
		write($message, 'worker-exit');

		$this->system_mail($message);
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
			error($e, 'email');
		}
	}

}
