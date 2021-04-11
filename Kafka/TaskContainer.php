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


    private static TaskContainer $container;


    /**
     * @return \Kafka\TaskContainer
     */
    public static function getInstance(): TaskContainer
    {
        if (!(static::$container instanceof TaskContainer)) {
            static::$container = new TaskContainer();
        }
        return static::$container;
    }


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
