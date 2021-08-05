<?php

namespace Kafka;

use Exception;
use RdKafka\Conf;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 *
 */
class KafkaClient extends BaseObject
{


	private Conf $conf;
	private TopicConf $topicConf;

	private ?\RdKafka\Producer $producer = null;

	private bool $isAck = true;

	/**
	 * Producer constructor.
	 * @param string $topic
	 * @param string $groupId
	 * @param string $brokers
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function __construct(public string $topic, string $groupId, string $brokers)
	{
		parent::__construct([]);
		$this->conf = di(Conf::class);
		$this->conf->set('metadata.broker.list', $brokers);
		$this->conf->set('group.id', $groupId);
		$this->conf->setErrorCb(function ($kafka, $err, $reason) {
			$this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});
		$this->topicConf = di(TopicConf::class);
	}

	/**
	 * @param array $params
	 * @param bool $isAck
	 * @throws Exception
	 */
	public function dispatch(array $params = [], bool $isAck = false)
	{
		$this->sendMessage([$params], $isAck);
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
	 * @return \RdKafka\Producer
	 * @throws Exception
	 */
	private function getProducer(): \RdKafka\Producer
	{
		return Snowflake::getDi()->get(\RdKafka\Producer::class, [$this->conf]);
	}


	/**
	 * @param \RdKafka\Producer $producer
	 * @param $topic
	 * @param $isAck
	 * @return ProducerTopic
	 */
	private function getProducerTopic(\RdKafka\Producer $producer, $topic, $isAck): ProducerTopic
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
	 * @param \RdKafka\Producer $producer
	 */
	private function flush(\RdKafka\Producer $producer)
	{
		while ($producer->getOutQLen() > 0) {
			$result = $producer->flush(100);
			if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
				break;
			}
		}
	}


}
