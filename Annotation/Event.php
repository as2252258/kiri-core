<?php


namespace Annotation;


use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;


/**
 * Class Event
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Event
{


	/**
	 * Event constructor.
	 * @param string $name
	 * @param array $params
	 * @throws ComponentException
	 */
	public function __construct(public string $name, public array $params = [])
	{
		$event = Snowflake::app()->getEvent();
	}

}
