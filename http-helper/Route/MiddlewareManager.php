<?php


namespace Http\Route;


use Closure;
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
		if (!isset(static::$_middlewares[$class . '::' . $method])) {
			static::$_middlewares[$class . '::' . $method] = [];
		}
		if (is_string($middlewares) && !in_array($middlewares, static::$_middlewares[$class . '::' . $method])) {
			static::$_middlewares[$class . '::' . $method][] = $middlewares;
		} else {
			foreach ($middlewares as $middleware) {
				if (in_array($middleware, static::$_middlewares[$class . '::' . $method])) {
					continue;
				}
				static::$_middlewares[$class . '::' . $method][] = $middleware;
			}
		}
		return true;
	}


	/**
	 * @param $handler
	 * @return mixed
	 */
	public static function get($handler): mixed
	{
		if ($handler instanceof Closure) {
			return null;
		}
		[$class, $method] = [$handler[0]::class, $handler[1]];
		if (!static::hasMiddleware($class, $method)) {
			return null;
		}
		return static::$_middlewares[$class . '::' . $method];
	}


	/**
	 * @param $class
	 * @param $method
	 * @return bool
	 */
	public static function hasMiddleware($class, $method): bool
	{
		if (is_object($class)) {
			$class = $class::class;
		}
		return isset(static::$_middlewares[$class . '::' . $method]);
	}

}
