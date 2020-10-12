<?php


namespace Kafka;


use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use Swoole\Process;
use Snowflake\Abstracts\Config as SConfig;
use function Amp\stop;

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
		$config = ConsumerConfig::getInstance();
		$config->setMetadataRefreshIntervalMs(
			$kafka['metadataRefreshIntervalMs'] ?? 1000
		);
		$config->setMetadataBrokerList($kafka['brokers']);
		$config->setGroupId($kafka['groupId']);
		$config->setBrokerVersion($kafka['version']);
		$config->setTopics($kafka['topics']);
		$this->channelListener($kafka);

		return [new Consumer(), $kafka];
	}


	/**
	 * @param Process $process
	 * @throws ConfigException
	 */
	public function onHandler(Process $process)
	{
		[$consumer, $kafka] = $this->initConfig();
		if ($kafka['debug'] ?? true) {
			$consumer->setLogger(new Logger());
		}
		$consumer->start(function ($topic, $part, $message) {
			$this->channel->push([$topic, $part, $message]);
		});
	}


	/**
	 * 监听通道数据传递
	 * @param $config
	 */
	public function channelListener($config)
	{
		$this->channel = new Channel($config['size'] ?? 100);
		Coroutine::create(function () use ($config) {
			$group = new WaitGroup();
			for ($i = 0; $i < $config['size'] ?? 100; $i++) {
				$group->add();
				go(function () use ($group) {
					defer(function () use ($group) {
						$group->done();
					});
					while ([$topic, $part, $message] = $this->channel->pop()) {
						$this->handlerExecute($topic, $part, $message);
					}
				});
			}
			$group->wait();
		});
	}


	/**
	 * @param $topic
	 * @param $part
	 * @param $message
	 */
	protected function handlerExecute($topic, $part, $message)
	{
		try {
			$namespace = 'App\\Kafka\\' . ucfirst($topic) . 'Consumer';
			if (!class_exists($namespace)) {
				return;
			}
			$class = Snowflake::createObject($namespace);
			if (!($class instanceof ConsumerInterface)) {
				return;
			}
			$class->onHandler(new Struct($topic, $part, $message));
		} catch (\Throwable $exception) {
			$this->application->error($exception->getMessage());
		}
	}


}
