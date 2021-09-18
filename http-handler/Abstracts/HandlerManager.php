<?php

namespace Http\Handler\Abstracts;

use Closure;
use Kiri\Kiri;

class HandlerManager
{


	private static array $handlers = [];


	/**
	 * @param string $path
	 * @param string $method
	 * @param \Http\Handler\Handler|Closure $handler
	 */
	public static function add(string $path, string $method, \Http\Handler\Handler|Closure $handler)
	{
		if (!isset(static::$handlers[$path])) {
			static::$handlers[$path] = [];
		}
		static::$handlers[$path][$method] = $handler;
	}


	/**
	 * @param $path
	 * @param $method
	 * @return null|int|array|Closure
	 */
	public static function get($path, $method): null|int|\Http\Handler\Handler|Closure
	{
		if (!isset(static::$handlers[$path])) {
			return null;
		}
		$array = static::$handlers[$path][$method] ?? null;
		if (is_null($array)) {
			return 405;
		}
		return $array;
	}

}
