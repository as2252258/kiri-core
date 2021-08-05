<?php
declare(strict_types=1);

namespace Kafka;

use Exception;
use RdKafka\Conf;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use ReflectionException;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * +------------------------------------------------------------------------------
 * Kafka protocol since Kafka v0.8
 * +------------------------------------------------------------------------------
 *
 * @package
 * @version $_SWANBR_VERSION_$
 * @copyright Copyleft
 * @author $_SWANBR_AUTHOR_$
 * +------------------------------------------------------------------------------
 */
class Producer extends BaseObject
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
	 * @throws ReflectionException
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
	 * @param string|null $groupId
	 * @throws Exception
	 */
	public function dispatch(array $params = [], string $groupId = null)
	{
		$this->sendMessage([$params]);
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
	 * @return ProducerTopic
	 * @throws Exception
	 */
	private function getProducerTopic(\RdKafka\Producer $producer, $topic): ProducerTopic
	{
		return $producer->newTopic($topic, $this->topicConf);
	}


	/**
	 * @param string|null $key
	 * @param array $data
	 * @param string|null $groupId
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function batch(?string $key, array $data, ?string $groupId = null)
	{
		$this->sendMessage($data, $key);
	}


	/**
	 * @param array $message
	 * @param string $key
	 * @throws Exception
	 */
	private function sendMessage(array $message, string $key = '')
	{
		$producer = $this->getProducer();
		$producerTopic = $this->getProducerTopic($producer, $this->topic);
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
	 * @param bool $ack
	 */
	public function setAsk(bool $ack)
	{
		$this->isAck = $ack;
		$this->topicConf->set('request.required.acks', $this->isAck ? '1' : '0');
	}


	/**
	 * @param \RdKafka\Producer $producer
	 */
	public function flush(\RdKafka\Producer $producer)
	{
		while ($producer->getOutQLen() > 0) {
			$result = $producer->flush(100);
			if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
				break;
			}
		}
	}

}
