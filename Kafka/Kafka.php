<?php


namespace Kafka;


use RdKafka\Conf;
use RdKafka\ConsumerTopic;
use RdKafka\KafkaConsumer;
use RdKafka\TopicConf;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use Swoole\Process;
use Snowflake\Abstracts\Config as SConfig;
use Swoole\Timer;
use function Amp\stop;

/**
 * Class Queue
 * @package Queue
 */
class Kafka extends \Snowflake\Process\Process
{

	protected Channel $channel;


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

		$kafkaServers = SConfig::get('kafka');
		foreach ($kafkaServers as $kafkaServer) {
			$this->waite($kafkaServer);
		}
	}


	/**
	 * @param array $kafkaServer
	 */
	private function waite(array $kafkaServer)
	{
		go(function () use ($kafkaServer) {
			[$config, $topic, $conf] = $this->kafkaConfig($kafkaServer);
			$objRdKafka = new \RdKafka\Consumer($config);
			$topic = $objRdKafka->newTopic($kafkaServer['topic'], $topic);
			$topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
			while (true) {
				try {
					$message = $topic->consume(0, $conf['metadataRefreshIntervalMs'] ?? 1000);
					if (empty($message)) {
						$this->application->debug('message null.');
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
				} catch (\Throwable $exception) {
					$this->application->error($exception->getMessage());
				}
			}
		});
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
	 * @param $kafka
	 * @return array
	 */
	private function kafkaConfig($kafka)
	{
		$conf = new Conf();
		$conf->setRebalanceCb(function (KafkaConsumer $kafka, $err, array $partitions = null) {
			if ($err == RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
				$kafka->assign($partitions);
			} else if ($err == RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
				$kafka->assign(NULL);
			} else {
				throw new \Exception($err);
			}
		});
		$conf->set('group.id', $kafka['groupId']);
		$conf->set('metadata.broker.list', $kafka['brokers']);
		$conf->set('socket.timeout.ms', 30000);
		if (function_exists('pcntl_sigprocmask')) {
			pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
			$conf->set('internal.termination.signal', SIGIO);
		} else {
			$conf->set('queue.buffering.max.ms', 1);
		}
		$topicConf = new TopicConf();
		$topicConf->set('auto.commit.enable', 1);
		$topicConf->set('auto.commit.interval.ms', 100);
		//smallest：简单理解为从头开始消费，largest：简单理解为从最新的开始消费
		$topicConf->set('auto.offset.reset', 'smallest');
		$topicConf->set('offset.store.path', 'kafka_offset.log');

		return [$conf, $topicConf, $kafka];
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
