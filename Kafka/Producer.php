<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +---------------------------------------------------------------------------
// | SWAN [ $_SWANBR_SLOGAN_$ ]
// +---------------------------------------------------------------------------
// | Copyright $_SWANBR_COPYRIGHT_$
// +---------------------------------------------------------------------------
// | Version  $_SWANBR_VERSION_$
// +---------------------------------------------------------------------------
// | Licensed ( $_SWANBR_LICENSED_URL_$ )
// +---------------------------------------------------------------------------
// | $_SWANBR_WEB_DOMAIN_$
// +---------------------------------------------------------------------------

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


	public function __construct($config = [])
	{
		$this->conf = new Conf();
		$this->topicConf = new TopicConf();
		parent::__construct($config);
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
		$this->topicConf->set('request.required.acks', (int)$value);
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
	public function delivery($message, $key = null, $timeout = 5)
	{
		if (!$this->conf || !$this->topicConf) {
			throw new \Exception('Error. Please set kafka conf.');
		}
		$this->conf->setDrmSgCb(function ($kafka, $message) {
//				$this->debug(var_export($message, true));
		});
		$this->conf->setErrorCb(function ($kafka, $err, $reason) {
			$this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});

		$rk = Snowflake::createObject(\RdKafka\Producer::class, [$this->conf]);
		$topic = $rk->newTopic($this->_topic, $this->topicConf);
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, $message, $key);
		$rk->poll($timeout);
		$rk->flush($timeout);

		$event = Snowflake::app()->getEvent();
		$event->on(Event::EVENT_AFTER_REQUEST, [$this,'onFlush'], [$rk, $timeout]);
	}


	/**
	 * @param $rk
	 * @param $timeout
	 */
	public function onFlush($rk, $timeout)
	{
		$this->debug(Event::EVENT_AFTER_REQUEST);
		$rk->flush($timeout);
	}
}
