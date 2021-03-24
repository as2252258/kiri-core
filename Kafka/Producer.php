<?php
declare(strict_types=1);

namespace Kafka;

use Exception;
use RdKafka\Conf;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
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
class Producer extends Component
{

	private string $_topic = '';


	private Conf $conf;
	private TopicConf $topicConf;

	private ?\RdKafka\Producer $producer = null;


	/**
	 * Producer constructor.
	 * @param array $config
	 */
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
	public function setBrokers(string $servers): static
	{
		$this->conf->set('metadata.broker.list', $servers);
		return $this;
	}


	/**
	 * @param string $groupId
	 * @return Producer
	 */
	public function setGroupId(string $groupId): static
	{
		$this->conf->set('group.id', $groupId);
		return $this;
	}


	/**
	 * @param $servers
	 * @return Producer
	 */
	public function setTopic(string $servers): static
	{
		$this->_topic = $servers;
		return $this;
	}


	/**
	 * @param string $topic
	 * @param array $params
	 * @param string|null $groupId
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws ConfigException
	 */
	public function dispatch(string $topic, array $params = [], string $groupId = null)
	{
		if ($groupId === null || empty($groupId)) {
			$consumers = Config::get('kafka.consumers.' . $topic);
			if (empty($consumers)) {
				$consumers = ['groupId' => $topic . ':' . Snowflake::localhost()];
			}
			$groupId = $consumers['groupId'];
		}
		$this->setGroupId($groupId)->setTopic($topic)->delivery(swoole_serialize($params));
	}


	/**
	 * @param $message
	 * @param null $key
	 * @param bool $isAck
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function delivery($message, $key = null, $isAck = false)
	{
		if (!$this->conf || !$this->topicConf) {
			throw new Exception('Error. Please set kafka conf.');
		}
		$this->conf->setErrorCb(function ($kafka, $err, $reason) {
			$this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});

		$event = Snowflake::app()->getEvent();
		$event->on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'flush']);

		if ($this->producer === null) {
			$this->producer = Snowflake::createObject(\RdKafka\Producer::class, [$this->conf]);
		}

		$this->setTopicAcks($isAck);
		$this->push($message, $key);
		if ($isAck === true) {
			$this->flush();
		}
	}


	/** @var ProducerTopic[] $topics */
	private array $topics = [];


	/**
	 * @param $message
	 * @param $key
	 */
	private function push($message, $key)
	{
		if (!isset($this->topics[$this->_topic])) {
			$this->topics[$this->_topic] = $this->producer->newTopic($this->_topic, $this->topicConf);
		}
		$this->topics[$this->_topic]->produce(RD_KAFKA_PARTITION_UA, 0, $message, $key);
		$this->producer->poll(0);
	}


	/**
	 * @param $isAck
	 */
	private function setTopicAcks(bool $isAck)
	{
		if ($isAck) {
			if ($this->producer->getOutQLen() > 0) {
				$this->flush();
			}
			$this->topicConf->set('request.required.acks', '1');
		} else {
			$this->topicConf->set('request.required.acks', '0');
		}
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
