<?php


namespace Annotation;


use Kafka\ConsumerInterface;
use Kafka\TaskContainer;

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
    public function execute(array $handler): mixed
    {
        if (!($handler[0] instanceof ConsumerInterface)) {
            return false;
        }

        $container = TaskContainer::getInstance();
        $container->addConsumer($this->topic, [$handler[0], 'onHandler']);

        return parent::execute($handler); // TODO: Change the autogenerated stub
    }


}
