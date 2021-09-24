<?php


namespace Http\Handler\Abstracts;


use Closure;
use Co\Iterator;
use Kiri\Abstracts\BaseObject;


/**
 * Class MiddlewareManager
 * @package Http\Route
 */
class MiddlewareManager extends BaseObject
{

	private static array $_middlewares = [];


	/**
	 * @param $class
	 * @param $method
	 * @param array|string $middlewares
	 * @return bool
	 */
	public static function add($class, $method, array|string $middlewares): bool
	{
		if (is_object($class)) {
			$class = $class::class;
		}
		if (!isset(static::$_middlewares[$class])) {
			static::$_middlewares[$class] = [];
		}
		if (!isset(static::$_middlewares[$class][$method])) {
			static::$_middlewares[$class][$method] = new Iterator();
		}
		if (is_string($middlewares)) {
			$middlewares = [$middlewares];
		}
		$source = static::$_middlewares[$class][$method];
		foreach ($middlewares as $middleware) {
			if (isset($source[$middleware])) {
				continue;
			}
			$source[$middleware] = di($middleware);
		}
		return true;
	}


	/**
	 * @param $handler
	 * @return mixed
	 */
	public static function get($handler): mixed
	{
		if ($handler instanceof Closure || !isset(static::$_middlewares[$handler[0]])) {
			return null;
		}
		return static::$_middlewares[$handler[0]][$handler[1]] ?? null;
	}


}
