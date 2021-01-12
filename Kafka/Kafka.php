<?php
declare(strict_types=1);

namespace Kafka;


use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\ConsumerTopic;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\TopicConf;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use Swoole\IDEHelper\StubGenerators\Swoole;
use Swoole\Process;
use Snowflake\Abstracts\Config as SConfig;
use Throwable;

/**
 * Class Queue
 * @package Queue
 */
class Kafka extends \Snowflake\Process\Process
{

	protected Channel $channel;


	/**
	 * @param Process $process
	 * @throws \Exception
	 */
	public function onHandler(Process $process): void
	{
		$this->channelListener();

		$this->waite(json_decode($process->read(), true));
	}


	/**
	 * @param array $kafkaServer
	 */
	private function waite(array $kafkaServer)
	{
		try {
			[$config, $topic, $conf] = $this->kafkaConfig($kafkaServer);
			$objRdKafka = new Consumer($config);
			$topic = $objRdKafka->newTopic($kafkaServer['topic'], $topic);

			$topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
			do {
				$this->resolve($topic, $conf['interval'] ?? 1000);
			} while (true);
		} catch (Throwable $exception) {
			$this->application->error($exception->getMessage());
		}
	}


	/**
	 * @param ConsumerTopic $topic
	 * @param $interval
	 */
	private function resolve(ConsumerTopic $topic, $interval)
	{
		try {
			$message = $topic->consume(0, $interval);
			if (empty($message)) {
				return;
			}
			if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
				$this->channel->push([$message->topic_name, $message]);
			} else if ($message->err == RD_KAFKA_RESP_ERR__PARTITION_EOF) {
				$this->application->error('No more messages; will wait for more');
			} else if ($message->err == RD_KAFKA_RESP_ERR__TIMED_OUT) {
				$this->application->error('Kafka Timed out');
			} else {
				$this->application->error($message->errstr());
			}
		} catch (Throwable $exception) {
			$this->application->error($exception->getMessage());
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
		go(function () use ($topic, $message) {
			try {
				$topic = str_replace('-', '_', $topic);

				$namespace = 'App\\Kafka\\' . ucfirst($topic) . 'Consumer';
				if (!class_exists($namespace)) {
					return;
				}
				$class = Snowflake::createObject($namespace);
				if (!($class instanceof ConsumerInterface)) {
					return;
				}
				$class->onHandler(new Struct($topic, $message));
			} catch (Throwable $exception) {
				$this->application->error($exception->getMessage());
			}
		});
	}


	/**
	 * @param $kafka
	 * @return array
	 */
	private function kafkaConfig($kafka): array
	{
		$conf = new Conf();
		$conf->setRebalanceCb([$this, 'rebalanced_cb']);
		$conf->set('group.id', $kafka['groupId']);
		$conf->set('metadata.broker.list', $kafka['brokers']);
		$conf->set('socket.timeout.ms', '30000');

		if (function_exists('pcntl_sigprocmask')) {
			pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
			$conf->set('internal.termination.signal', (string)SIGIO);
		}

		$topicConf = new TopicConf();
		try {
			$topicConf->set('auto.commit.enable', '1');
			$topicConf->set('auto.commit.interval.ms', '100');

			//smallest：简单理解为从头开始消费，
			//largest：简单理解为从最新的开始消费
			$topicConf->set('auto.offset.reset', 'smallest');
			$topicConf->set('offset.store.path', 'kafka_offset.log');
			$topicConf->set('offset.store.method', 'broker');
		} catch (Throwable $exception) {
			var_dump($exception->getMessage());
		}

		return [$conf, $topicConf, $kafka];
	}


	/**
	 * @param KafkaConsumer $kafka
	 * @param $err
	 * @param array|null $partitions
	 * @throws Exception
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
