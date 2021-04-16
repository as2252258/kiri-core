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

    private ?\RdKafka\Producer $producer = null;

    private bool $isAck = true;

    /**
     * Producer constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        if (!class_exists(Conf::class)) {
            return;
        }
        $this->conf = new Conf();
        $this->topicConf = new TopicConf();
        $this->conf->setErrorCb(function ($kafka, $err, $reason) {
            $this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
        });
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
     * @throws Exception
     */
    public function dispatch(string $topic, array $params = [], string $groupId = null)
    {
        $this->beforePushMessage($topic, $groupId);
        $this->sendMessage($topic, [$params]);
    }

    /**
     * @return \RdKafka\Producer
     * @throws Exception
     */
    private function getProducer(): \RdKafka\Producer
    {
        $pool = Snowflake::app()->getChannel();
        return $pool->pop(\RdKafka\Producer::class, function () {
            return Snowflake::createObject(\RdKafka\Producer::class, [$this->conf]);
        });
    }


	/**
	 * @param \RdKafka\Producer $producer
	 * @param $topic
	 * @return ProducerTopic
	 * @throws Exception
	 */
    private function getProducerTopic(\RdKafka\Producer $producer, $topic): ProducerTopic
    {
        $pool = Snowflake::app()->getChannel();
        return $pool->pop($topic . '::' . ProducerTopic::class, function () use ($producer, $topic) {
            return $producer->newTopic($topic, $this->topicConf);
        });
    }


    /**
     * @param string $toPic
     * @param string|null $key
     * @param array $data
     * @param string|null $groupId
     */
    public function batch(string $toPic, ?string $key, array $data, ?string $groupId = null)
    {
        $this->beforePushMessage($toPic, $groupId);

        $this->sendMessage($toPic, $data, $key);
    }


    /**
     * @param $topic
     * @param $groupId
     * @param $brokers
     * @return ProducerTopic
     * @throws \Snowflake\Exception\ConfigException
     */
    private function beforePushMessage($topic, $groupId): void
    {
        $consumers = Config::get('kafka.producers.' . $topic);
        if (empty($consumers) || !is_array($consumers)) {
            throw new Exception('You need set kafka.producers config');
        }
        if (!isset($consumers['brokers'])) {
            throw new Exception('You need set brokers config.');
        }
        if (!empty($groupId)) {
            $consumers['groupId'] = $groupId;
        } else if (!isset($consumers['groupId'])) {
            $consumers['groupId'] = $topic . ':' . Snowflake::localhost();
        }
        $this->setGroupId($consumers['groupId']);
        $this->setBrokers($consumers['brokers']);
        $this->setTopic($topic);
    }


    /**
     * @param TopicConf $topicConf
     * @param string $topic
     * @param array $message
     * @param string $key
     * @throws Exception
     */
    private function sendMessage(string $topic, array $message, string $key = '')
    {
        $producer = $this->getProducer();
        $producerTopic = $this->getProducerTopic($producer, $topic);

        if ($this->isAck) {
            $this->flush($producer);
        }

        foreach ($message as $value) {
            $producerTopic->produce(RD_KAFKA_PARTITION_UA, 0, swoole_serialize($value), $key);
            $producer->poll(0);
        }
        $this->flush($producer);
        $this->recover($topic, $producer, $producerTopic);
    }


    /**
     * @param bool $ack
     */
    public function setAsk(bool $ack){
        $this->isAck = $ack;
        $this->topicConf->set('request.required.acks', $this->isAck ? '1' : '0');
    }



    /**
     * @param \RdKafka\Producer $producer
     * @param ProducerTopic $producerTopic
     * @throws Exception
     */
    private function recover(string $topic, \RdKafka\Producer $producer, ProducerTopic $producerTopic)
    {
        $channel = Snowflake::app()->getChannel();
        $channel->push($producerTopic, $topic . '::' . ProducerTopic::class);
        $channel->push($producer, \RdKafka\Producer::class);
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
