<?php


namespace Kafka;


use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use Swoole\Process;
use Snowflake\Abstracts\Config as SConfig;

/**
 * Class Queue
 * @package Queue
 */
class Kafka extends \Snowflake\Process\Process
{

	/** @var Channel */
	protected $channel;


	/**
	 * @throws ConfigException
	 */
	public function initConfig()
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

		$this->channel = new Channel(1000);
		go([$this, 'listener']);
	}


	/**
	 * @param Process $process
	 * @throws ConfigException
	 */
	public function onHandler(Process $process)
	{
		$this->initConfig();

		$consumer = new \Kafka\Consumer();
		$consumer->setLogger(new Logger());
		$consumer->start(function ($topic, $part, $message) {
			$this->channel->push([$topic, $part, $message]);
		});
	}


	/**
	 * 监听通道数据传递
	 */
	protected function listener()
	{
		$group = new WaitGroup();
		while ([$topic, $part, $message] = $this->channel->pop()) {
			try {
				$namespace = 'App\\Kafka\\' . ucfirst($topic) . 'Consumer';
				if (!class_exists($namespace)) {
					return;
				}
				$class = Snowflake::createObject($namespace);
				if (!($class instanceof ConsumerInterface)) {
					continue;
				}
				$group->add();
				go(function () use ($group, $class, $topic, $part, $message) {
					defer(function () use ($group) {
						$group->done();
					});
					$class->onHandler(new Struct($topic, $part, $message));
				});
			} catch (\Throwable $exception) {
				$this->application->error($exception->getMessage());
			}
		}
		$group->wait();
	}


}
