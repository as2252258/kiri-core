<?php


namespace Annotation;


use Kafka\ConsumerInterface;
use Kafka\TaskContainer;
use Snowflake\Snowflake;

/**
 * Class Kafka
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Kafka extends Attribute
{


    /**
     * Kafka constructor.
     * @param string $topic
     */
    public function __construct(public string $topic)
    {

    }


    /**
     * @param array $handler
     * @return mixed
     */
    public function execute(mixed $class, mixed $method = null): mixed
    {
        if (!($class instanceof ConsumerInterface)) {
            return false;
        }

        /** @var TaskContainer $container */
        $container = Snowflake::app()->get('kafka-container');
        $container->addConsumer($this->topic, [$class, 'onHandler']);

        return true;
    }


}
