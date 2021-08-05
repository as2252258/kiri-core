<?php

namespace Kafka;


use Server\SInterface\PipeMessage;

/**
 *
 */
class Message implements ConsumerInterface, PipeMessage
{


	/**
	 * @param Struct $struct
	 */
	public function __construct(public Struct $struct)
	{
	}


	/**
	 *
	 */
	public function process(): void
	{
		// TODO: Implement process() method.
		var_dump($this->struct);
	}

}
