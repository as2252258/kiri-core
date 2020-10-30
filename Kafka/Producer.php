<?php
declare(strict_types=1);

namespace Kafka;

use RdKafka\Conf;
use RdKafka\TopicConf;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Event;
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
class Producer extends Component
{

	private string $_topic = '';


	private Conf $conf;
	private TopicConf $topicConf;

	private \RdKafka\Producer $producer;


	public function __construct($config = [])
	{
		parent::__construct($config);
		if (!class_exists(Conf::class)) {
			return;
		}
		$this->conf = new Conf();
		$this->topicConf = new TopicConf();
	}


	/**
	 * @param $servers
	 * @return Producer
	 */
	public function setBrokers(string $servers)
	{
		$this->conf->set('metadata.broker.list', $servers);
		return $this;
	}


	/**
	 * @param bool $value
	 * @return $this
	 */
	public function setAck(bool $value)
	{
//		$this->topicConf->set('request.required.acks', (string)$value);
		return $this;
	}


	/**
	 * @param $servers
	 * @return Producer
	 */
	public function setTopic(string $servers)
	{
		$this->_topic = $servers;
		return $this;
	}


	/**
	 * @param $message
	 * @param null $key
	 * @param int $timeout
	 * @throws
	 */
	public function delivery($message, $key = null, $timeout = 10)
	{
		if (!$this->conf || !$this->topicConf) {
			throw new \Exception('Error. Please set kafka conf.');
		}
		$this->conf->setErrorCb(function ($kafka, $err, $reason) {
			$this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});

		$event = Snowflake::app()->getEvent();
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'flush']);

		/** @var \RdKafka\Producer $rk */
		$this->producer = Snowflake::createObject(\RdKafka\Producer::class, [$this->conf]);
		$topic = $this->producer->newTopic($this->_topic, $this->topicConf);
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, $message, $key);
		$this->producer->poll(0);
	}

	public function flush()
	{
		while ($this->producer->getOutQLen() > 0) {
			$result = $this->producer->flush(100);
			if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
				break;
			}
		}
	}

}
