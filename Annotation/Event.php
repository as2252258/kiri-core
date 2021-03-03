<?php


namespace Annotation;


use Exception;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;


/**
 * Class Event
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Event extends Attribute
{


	/**
	 * Event constructor.
	 * @param string $name
	 * @param array $params
	 */
	public function __construct(public string $name, public array $params = [])
	{
	}


	/**
	 * @param array $handler
	 * @return \Snowflake\Event
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function execute(array $handler): \Snowflake\Event
	{
		// TODO: Implement execute() method.
		$event = Snowflake::app()->getEvent();
		$event->on($this->name, $handler, $this->params);
		return $event;
	}

}
