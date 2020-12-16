<?php


namespace Annotation\Route;


use Snowflake\Snowflake;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware
{


	/**
	 * Interceptor constructor.
	 * @param string|array $middleware
	 * @throws
	 */
	public function __construct(public string|array $middleware)
	{
		if (is_string($this->middleware)) {
			$this->middleware = [$this->middleware];
		}
		foreach ($this->middleware as $key => $item) {
			$this->middleware[$key] = [Snowflake::createObject($item), 'onHandler'];
		}
	}

}
