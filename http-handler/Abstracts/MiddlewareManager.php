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


	/**
	 * @var array<string, Iterator>
	 */
	private static array $_middlewares = [];


	/**
	 * @param $class
	 * @param $method
	 * @param array|string|null $middlewares
	 * @return bool
	 */
	public static function add($class, $method, array|string|null $middlewares): bool
	{
		[$class, $method] = static::setDefault($class, $method);
		if (empty($middlewares)) {
			return false;
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


	private static function setDefault($class, $method): array
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
		return [$class, $method];
	}


	/**
	 * @param $handler
	 * @return Iterator|null
	 */
	public static function get($handler): ?Iterator
	{
		if (!($handler instanceof Closure)) {
			return static::$_middlewares[$handler[0]][$handler[1]] ?? null;
		}
		return di(Iterator::class);
	}


}
