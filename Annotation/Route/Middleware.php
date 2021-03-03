<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Snowflake\Snowflake;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware extends Attribute
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
		foreach ($this->middleware as $key => $value) {
			$sn = Snowflake::createObject($value);

			if (!($sn instanceof \HttpServer\IInterface\Middleware)) {
				continue;
			}
			$this->middleware[$key] = [$sn, 'onHandler'];
		}
	}


	/**
	 * @param array $handler
	 * @return Middleware
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
