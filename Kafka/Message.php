<?php

namespace Kafka;


use Server\SInterface\PipeMessage;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 *
 */
class Message implements PipeMessage
{


	/**
	 * @param Struct $struct
	 */
	public function __construct(public Struct $struct)
	{
	}


	/**
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 */
	public function process(): void
	{
		/** @var KafkaProvider $container */
		$container = Snowflake::getDi()->get(KafkaProvider::class);
		$data = $container->getConsumer($this->struct->topic);
		var_dump($data);
	}

}
