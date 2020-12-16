<?php


namespace Annotation\Route;


use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor
{


	/**
	 * Interceptor constructor.
	 * @param string|array $interceptor
	 * @throws
	 */
	public function __construct(public string|array $interceptor)
	{
		if (is_string($this->interceptor)) {
			$this->interceptor = [$this->interceptor];
		}
		foreach ($this->interceptor as $key => $item) {
			$this->interceptor[$key] = [Snowflake::createObject($item), 'Interceptor'];
		}
	}

}
