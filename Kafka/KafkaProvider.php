<?php


namespace Kafka;


use Snowflake\Abstracts\BaseObject;


/**
 * Class KafkaProvider
 * @package Kafka
 */
class KafkaProvider extends BaseObject
{


    private array $_topics = [];


    /**
     * @param $topic
     * @param $handler
     */
    public function addConsumer($topic, $handler)
    {
        if (isset($this->_topics[$topic])) {
            return;
        }
        var_dump($topic, $handler);
        $this->_topics[$topic] = $handler::class;
    }


	/**
	 * @param string $topic
	 * @return mixed
	 */
    public function getConsumer(string $topic): mixed
    {
        return $this->_topics[$topic] ?? null;
    }

}
