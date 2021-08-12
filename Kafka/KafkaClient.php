<?php

namespace Kafka;

use Exception;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use ReflectionException;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;


/**
 *
 */
class KafkaClient
{


	private Conf $conf;
	private TopicConf $topicConf;

	private bool $isAck = true;

	/**
	 * Producer constructor.
	 * @param string $topic
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws ConfigException
	 */
	public function __construct(public string $topic)
	{
		$this->conf = di(Conf::class);
		$this->topicConf = di(TopicConf::class);
		$this->setConfig($this->conf);
	}


	/**
	 * @throws ConfigException
	 */
	private function setConfig(Conf $kafkaConfig)
	{
		$config = Config::get('kafka.producers.' . $this->topic, null, true);
		if (!isset($config['brokers']) || !isset($config['groupId'])) {
			throw new ConfigException('Please configure relevant information.');
		}
		$kafkaConfig->set('metadata.broker.list', $config['brokers']);
		$kafkaConfig->set('group.id', $config['groupId']);
		$kafkaConfig->setErrorCb(function ($kafka, $err, $reason) {
			logger()->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});
	}


	/**
	 * @param string $key
	 * @param string|array $params
	 * @param bool $isAck
	 * @throws Exception
	 */
	public function dispatch(string $key, string|array $params, bool $isAck = false)
	{
		$this->sendMessage([$params], $key, $isAck);
	}


	/**
	 * @param string|null $key
	 * @param array $data
	 * @param bool $isAck
	 * @throws Exception
	 */
	public function batch(?string $key, array $data, bool $isAck = false)
	{
		$this->sendMessage($data, $key, $isAck);
	}


	/**
	 * @return Producer
	 * @throws Exception
	 */
	private function getProducer(): Producer
	{
		return Kiri::getDi()->get(Producer::class, [$this->conf]);
	}


	/**
	 * @param Producer $producer
	 * @param $topic
	 * @param $isAck
	 * @return ProducerTopic
	 */
	private function getProducerTopic(Producer $producer, $topic, $isAck): ProducerTopic
	{
		$this->topicConf->set('request.required.acks', $isAck ? '1' : '0');
		return $producer->newTopic($topic, $this->topicConf);
	}


	/**
	 * @param array $message
	 * @param string $key
	 * @param bool $isAck
	 * @throws Exception
	 */
	private function sendMessage(array $message, string $key = '', bool $isAck = false)
	{
		$producer = $this->getProducer();
		$producerTopic = $this->getProducerTopic($producer, $this->topic, $isAck);
		if ($this->isAck) {
			$this->flush($producer);
		}
		foreach ($message as $value) {
			$producerTopic->produce(RD_KAFKA_PARTITION_UA, 0, swoole_serialize($value), $key);
			$producer->poll(0);
		}
		$this->flush($producer);
	}


	/**
	 * @param Producer $producer
	 */
	private function flush(Producer $producer)
	{
		while ($producer->getOutQLen() > 0) {
			$result = $producer->flush(100);
			if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
				break;
			}
		}
	}


}
