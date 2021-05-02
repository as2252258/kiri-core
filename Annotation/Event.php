<?php


namespace Annotation;


use Exception;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Snowflake\Event as SEvent;


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
	 * @return bool
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = null): mixed
	{
		// TODO: Implement execute() method.
		SEvent::on($this->name, [$class, $method], $this->params);
		return true;
	}

}
