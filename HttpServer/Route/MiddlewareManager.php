<?php


namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\Middleware;
use Snowflake\Abstracts\BaseObject;


/**
 * Class MiddlewareManager
 * @package HttpServer\Route
 */
class MiddlewareManager extends BaseObject
{

	private static array $_middlewares = [];


	/**
	 * @param $class
	 * @param $method
	 * @param array|string $middlewares
	 */
	public function addMiddlewares($class, $method, array|string $middlewares)
	{
		if (is_object($class)) {
			$class = $class::class;
		}
		if (!isset(static::$_middlewares[$class . '::' . $method])) {
			static::$_middlewares[$class . '::' . $method] = [];
		}

		if (is_string($middlewares) && !in_array($middlewares, static::$_middlewares[$class . '::' . $method])) {
			static::$_middlewares[$class . '::' . $method][] = $middlewares;
			return;
		}
		foreach ($middlewares as $middleware) {
			if (in_array($middlewares, static::$_middlewares[$class . '::' . $method])) {
				continue;
			}
			static::$_middlewares[$class . '::' . $method][] = $middleware;
		}
	}


	/**
	 * @param $class
	 * @param $method
	 * @return bool
	 */
	public function hasMiddleware($class, $method): bool
	{
		if (is_object($class)) {
			$class = $class::class;
		}
		return isset(static::$_middlewares[$class . '::' . $method]);
	}


	/**
	 * @param $class
	 * @param $method
	 * @param $caller
	 * @return mixed
	 */
	public function callerMiddlewares($class, $method, $caller): mixed
	{
		if (is_object($class)) {
			$class = $class::class;
		}
		$middlewares = static::$_middlewares[$class . '::' . $method] ?? [];
		if (empty($middlewares)) {
			return $caller;
		}
		return array_reduce(array_reverse($middlewares), function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof Middleware) {
					return $pipe->onHandler($passable, $stack);
				}
				return call_user_func($pipe, $passable, $stack);
			};
		}, $caller);
	}


	/**
	 * @param $middlewares
	 * @param Closure $caller
	 * @return Closure
	 */
	public function closureMiddlewares($middlewares, Closure $caller): Closure
	{
		return array_reduce(array_reverse($middlewares), function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof Middleware) {
					return $pipe->onHandler($passable, $stack);
				}
				return call_user_func($pipe, $passable, $stack);
			};
		}, $caller);
	}

}
