<?php


namespace Kafka;


use RdKafka\Conf;
use RdKafka\KafkaConsumer;
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
//	public function initConfig()
//	{
//		$kafka = SConfig::get('kafka');
//		$config = ConsumerConfig::getInstance();
//		$config->setMetadataRefreshIntervalMs(
//			$kafka['metadataRefreshIntervalMs'] ?? 1000
//		);
//		$config->setMetadataBrokerList($kafka['brokers']);
//		$config->setGroupId($kafka['groupId']);
//		$config->setBrokerVersion($kafka['version']);
//		$config->setTopics($kafka['topics']);
//		$this->channelListener($kafka);
//
//		return [new Consumer(), $kafka];
//	}


	/**
	 * @param Process $process
	 * @throws ConfigException
	 * @throws \Exception
	 */
	public function onHandler(Process $process)
	{
		$this->channelListener();
		[$config, $conf] = $this->kafkaConfig();
		$objRdKafka = new KafkaConsumer($config);
		$objRdKafka->subscribe(['test']);
		while (true) {
			$message = $objRdKafka->consume($conf['metadataRefreshIntervalMs'] ?? 1000);
			if (empty($message)) {
				continue;
			}
			switch ($message->err) {
				case RD_KAFKA_RESP_ERR_NO_ERROR:
					$this->channel->push([$message->topic_name, $message]);
					break;
				case RD_KAFKA_RESP_ERR__PARTITION_EOF:
					$this->application->error('No more messages; will wait for more');
					break;
				case RD_KAFKA_RESP_ERR__TIMED_OUT:
					$this->application->error('Kafka Timed out');
					break;
				default:
					throw new \Exception($message->errstr(), $message->err);
			}
		}
	}


	/**
	 * 监听通道数据传递
	 */
	public function channelListener()
	{
		$this->channel = new Channel(100);
		Coroutine::create(function () {
			$group = new WaitGroup();
			for ($i = 0; $i < 100; $i++) {
				$group->add();
				go(function () use ($group) {
					defer(function () use ($group) {
						$group->done();
					});
					while ($messages = $this->channel->pop()) {
						$this->handlerExecute($messages[0], $messages[1]);
					}
				});
			}
			$group->wait();
		});
	}


	/**
	 * @param $topic
	 * @param $message
	 */
	protected function handlerExecute($topic, $message)
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
			$class->onHandler(new Struct($topic, $message));
		} catch (\Throwable $exception) {
			$this->application->error($exception->getMessage());
		}
	}


	/**
	 * @return array
	 * @throws ConfigException
	 */
	private function kafkaConfig()
	{
		$conf = new Conf();

		$kafka = SConfig::get('kafka');
		$conf->setRebalanceCb([$this, 'rebalanced_cb']);

		$conf->set('metadata.broker.list', '127.0.0.1:9092');
		$conf->set('socket.timeout.ms', 300000);

		//多进程和信号
		if (function_exists('pcntl_sigprocmask')) {
			pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
			$conf->set('internal.termination.signal', SIGIO);
		} else {
			$conf->set('queue.buffering.max.ms', 1);
		}
		return [$conf, $kafka];
	}


	/**
	 * @param KafkaConsumer $kafka
	 * @param $err
	 * @param array|null $partitions
	 * @throws \RdKafka\Exception
	 * @throws \Exception
	 */
	public function rebalanced_cb(KafkaConsumer $kafka, $err, array $partitions = null)
	{
		if ($err == RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
			$kafka->assign($partitions);
		} else if ($err == RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
			$kafka->assign(NULL);
		} else {
			throw new \Exception($err);
		}
	}


}
