<?php


namespace Annotation;


use Exception;
use Kafka\ConsumerInterface;
use Kafka\KafkaProvider;
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
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = null): bool
    {
        if (!($class instanceof ConsumerInterface)) {
            return false;
        }

        /** @var KafkaProvider $container */
        $container = Snowflake::app()->get('kafka-container');
        $container->addConsumer($this->topic, $class);

        return true;
    }


}
