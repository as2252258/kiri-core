<?php


namespace Kafka\Annotation;


use Annotation\Attribute;
use Exception;
use Kafka\ConsumerInterface;
use Kafka\KafkaProvider;
use Kiri\Kiri;

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


        var_dump($class);

        /** @var KafkaProvider $container */
        $container = Kiri::getDi()->get(KafkaProvider::class);
        $container->addConsumer($this->topic, $class);

        return true;
    }


}
