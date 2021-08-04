<?php


namespace Kafka;


use Snowflake\Abstracts\BaseObject;


/**
 * Class TaskContainer
 * @package Kafka
 */
class TaskContainer extends BaseObject
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
        $this->_topics[$topic] = $handler;
    }


	/**
	 * @param string $topic
	 * @return mixed
	 */
    public function getConsumer(string $topic): mixed
    {
        return $this->_topics[$topic] ?? null;
    }


    /**
     * @param $topic
     * @param \Kafka\Struct $struct
     */
    public function process($topic, Struct $struct)
    {
        $handler = $this->_topics[$topic] ?? null;
        if (empty($handler)) {
            return;
        }
        call_user_func($handler, $struct);
    }

}
