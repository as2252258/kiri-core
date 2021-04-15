<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kafka\Struct;
use Snowflake\Crontab\Crontab;
use Snowflake\Event;
use Swoole\Server;

/**
 * Class OnPipeMessage
 * @package HttpServer\Events
 */
class OnPipeMessage extends Callback
{

	/**
	 * @param Server $server
	 * @param int $src_worker_id
	 * @param $swollen_universalize
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $src_worker_id, $swollen_universalize)
	{
		try {
			match ($swollen_universalize['action'] ?? null) {
				'kafka' => $this->onKafkaWorker($swollen_universalize),
				'crontab' => $this->onCrontabWorker($swollen_universalize),
				default => $this->onMessageWorker($server, $src_worker_id, $swollen_universalize)
			};
		} catch (\Throwable $exception) {
			$this->addError($exception);
		} finally {
			fire(Event::SYSTEM_RESOURCE_RELEASES);
		}
	}


	/**
	 * @param array $message
	 * @return string
	 * @throws Exception
	 */
	private function onCrontabWorker(array $message): string
	{
		/** @var Crontab $crontab */
		$crontab = $message['handler'] ?? null;
		if (!isset($message['handler'])) {
			throw new Exception('unknown handler');
		}
		$crontab = swoole_unserialize($crontab['handler']);
		$crontab->increment()->execute();
		return 'success';
	}


	/**
	 * @param $server
	 * @param $src_worker_id
	 * @param $message
	 * @return string
	 * @throws Exception
	 */
	private function onMessageWorker($server, $src_worker_id, $message): string
	{
		fire(Event::PIPE_MESSAGE, [$server, $src_worker_id, $message]);

		return 'success';
	}


	/**
	 * @param array $message
	 * @return string
	 */
	private function onKafkaWorker(array $message): string
	{
		[$topic, $rdMessage] = $message['body'];

		call_user_func($message['handler'], new Struct($topic, $rdMessage));

		return 'success';
	}


}
