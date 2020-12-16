<?php


namespace Annotation\Route;


use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class After
{


	/**
	 * Interceptor constructor.
	 * @param \HttpServer\IInterface\After|\HttpServer\IInterface\After[] $after
	 * @throws
	 */
	public function __construct(public string|array $after)
	{
		if (is_string($this->after)) {
			$this->after = [$this->after];
		}
		foreach ($this->after as $key => $item) {
			$this->after[$key] = Snowflake::createObject($item);
		}
	}

}
