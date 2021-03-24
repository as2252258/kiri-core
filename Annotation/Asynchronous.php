<?php


namespace Annotation;


use Exception;
use Snowflake\Snowflake;


/**
 * Class Asynchronous
 * @package Annotation
 * Task任务
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Asynchronous extends Attribute
{


	/**
	 * Asynchronous constructor.
	 * @param string $name
	 */
	public function __construct(public string $name)
	{

	}


	/**
	 * @param array $handler
	 * @return bool
	 * @throws Exception
	 */
	public function execute(array $handler): bool
	{
		$async = Snowflake::app()->getAsync();
		$async->addAsync($this->name, current($handler));
		return true;
	}

}
