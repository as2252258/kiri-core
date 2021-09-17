<?php

namespace Http\Handler\Abstracts;

use Closure;
use Kiri\Kiri;

class HandlerManager
{


	private static array $handlers = [];


	/**
	 * @param $path
	 * @param $method
	 * @param $handler
	 */
	public static function add($path, $method, $handler)
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
		if ($array instanceof Closure) {
			return $array;
		}
		$array[1] = Kiri::getDi()->get($array[1]);
		return $array;
	}

}
