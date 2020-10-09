<?php


namespace Kafka;


use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Process;
use Snowflake\Abstracts\Config as SConfig;

/**
 * Class Queue
 * @package Queue
 */
class Kafka extends \Snowflake\Process\Process
{

	/**
	 * @param Process $process
	 * @throws ConfigException
	 */
	public function onHandler(Process $process)
	{
		$kafka = SConfig::get('kafka');
		$config = \Kafka\ConsumerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(
			$kafka['metadataRefreshIntervalMs'] ?? 1000
		);
		$config->setMetadataBrokerList($kafka['brokers']);
		$config->setGroupId($kafka['groupId']);
		$config->setBrokerVersion($kafka['version']);
		$config->setTopics($kafka['topics']);

		$consumer = new \Kafka\Consumer();
		$consumer->setLogger(new Logger());
		$consumer->start(function ($topic, $part, $message) {
			try {
				var_dump($topic, $part, $message);
				$namespace = 'App\\Kafka\\' . ucfirst($topic) . 'Consumer';
				if (!class_exists($namespace)) {
					return;
				}
				$class = Snowflake::createObject($namespace);
				if ($class instanceof ConsumerInterface) {
					$class->onHandler(
						$message['offset'],
						$part,
						$message['message']['crc'],
						$message['message']['magic'],
						$message['message']['attr'],
						$message['message']['timestamp'],
						$message['message']['key'],
						$message['message']['value']
					);
				}
			} catch (\Throwable $exception) {
				$this->application->error($exception->getMessage());
			}
		});

	}

}
