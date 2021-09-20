<?php


namespace Http\Handler\Abstracts;


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
		if (!isset(static::$_middlewares[$class])) {
			static::$_middlewares[$class] = [];
		}
		if (!isset(static::$_middlewares[$class][$method])) {
			static::$_middlewares[$class][$method] = [];
		}

        $source = &static::$_middlewares[$class][$method];
		if (is_string($middlewares) && !in_array($middlewares, $source)) {
            $source[] = di($middlewares);
		} else {
			foreach ($middlewares as $middleware) {
				if (in_array($middleware, $source)) {
					continue;
				}
                $source[] = di($middleware);
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
		if ($handler instanceof Closure || !isset(static::$_middlewares[$handler[0]])) {
			return null;
		}
        return static::$_middlewares[$handler[0]][$handler[1]] ?? null;
	}


}