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

		$array = [];
		foreach ($this->middleware as $key => $value) {
			$sn = Snowflake::createObject($value);
			if (!($sn instanceof \HttpServer\IInterface\Middleware)) {
				continue;
			}
			$array[] = [$sn, 'onHandler'];
		}
		$this->middleware = $array;
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
