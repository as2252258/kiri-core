<?php
declare(strict_types=1);

namespace Kafka;


use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use RdKafka\Consumer;
use RdKafka\ConsumerTopic;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use Server\Abstracts\CustomProcess;
use Swoole\Process;
use Throwable;

/**
 * Class Queue
 * @package Queue
 */
class Kafka extends CustomProcess
{


	/**
	 * @param array $kafkaConfig
	 */
	public function __construct(public array $kafkaConfig)
	{
	}


	/**
	 * @param Process $process
	 * @return string
	 * @throws ConfigException
	 */
	public function getProcessName(Process $process): string
	{
		$name = Config::get('id', 'system') . '[' . $process->pid . ']';

		return $name . '.' . 'Kafka Consumer ' . $this->kafkaConfig['topic'];
	}


	/**
	 * @param Process $process
	 * @throws \Exception
	 */
	public function onHandler(Process $process): void
	{
		$this->waite($process, $this->kafkaConfig);
	}


	/**
	 * @param Process $process
	 * @param array $kafkaServer
	 * @throws \Exception
	 */
	private function waite(Process $process, array $kafkaServer)
	{
		try {
			[$config, $topic, $conf] = $this->kafkaConfig($kafkaServer);
			if (empty($config) && empty($topic) && empty($conf)) {
				return;
			}
			$objRdKafka = new Consumer($config);
			$topic = $objRdKafka->newTopic($kafkaServer['topic'], $topic);

			$topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
			do {
				if ($this->checkProcessIsStop()) {
					$this->exit();
					break;
				}
				$this->resolve($topic, $conf['interval'] ?? 1000);
			} while (true);
		} catch (Throwable $exception) {
			logger()->addError($exception, 'throwable');
		}
	}


	/**
	 * @param ConsumerTopic $topic
	 * @param $interval
	 * @throws \Exception
	 */
	private function resolve(ConsumerTopic $topic, $interval)
	{
		try {
			$message = $topic->consume(0, $interval);
			if (!empty($message)) {
				if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
					$this->handlerExecute($message->topic_name, $message);
				} else if ($message->err == RD_KAFKA_RESP_ERR__PARTITION_EOF) {
					logger()->warning('No more messages; will wait for more');
				} else if ($message->err == RD_KAFKA_RESP_ERR__TIMED_OUT) {
					logger()->error('Kafka Timed out');
				} else {
					logger()->error($message->errstr());
				}
			}
		} catch (Throwable $exception) {
			logger()->addError($exception, 'throwable');
		}
	}


	/**
	 * @param $topic
	 * @param $message
	 * @throws \Exception
	 */
	protected function handlerExecute($topic, $message)
	{
		go(function () use ($topic, $message) {
			try {
				$server = Kiri::app()->getSwoole();

				$setting = $server->setting['worker_num'];

				/** @var KafkaProvider $container */
				$container = Kiri::getDi()->get(KafkaProvider::class);
				$data = $container->getConsumer($topic);
				if (!empty($data)) {
					$server->sendMessage(new $data(new Struct($topic, $message)), random_int(0, $setting - 1));
				}
			} catch (Throwable $exception) {
				logger()->addError($exception, 'throwable');
			}
		});
	}


	/**
	 * @param $kafka
	 * @return array
	 * @throws \Exception
	 */
	private function kafkaConfig($kafka): array
	{
		try {
			$conf = new Configuration();
			$conf->setRebalanceCb([$this, 'rebalanced_cb']);
			$conf->setGroupId($kafka['groupId']);
			$conf->setMetadataBrokerList($kafka['brokers']);
			$conf->setSocketTimeoutMs(30000);

			if (function_exists('pcntl_sigprocmask')) {
				pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
				$conf->setInternalTerminationSignal((string)SIGIO);
			}

			$topicConf = new TopicConfig();
			$topicConf->setAutoCommitEnable(true);
			$topicConf->setAutoCommitIntervalMs(100);

			//smallest：简单理解为从头开始消费，
			//largest：简单理解为从最新的开始消费
			$topicConf->setAutoOffsetReset('smallest');
			$topicConf->setOffsetStorePath('kafka_offset.log');
			$topicConf->setOffsetStoreMethod('broker');

			return [$conf, $topicConf, $kafka];
		} catch (Throwable $exception) {
			logger()->addError($exception, 'throwable');
			return [null, null, null];
		}
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
