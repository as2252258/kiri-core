<?php


namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\After;

class Reduce
{


	/**
	 * @param $last
	 * @param $middleWares
	 * @return mixed|null
	 */
	public static function reduce($last, $middleWares)
	{
		return array_reduce(array_reverse($middleWares), static::core(), $last);
	}


	/**
	 * @param $middleWares
	 * @return mixed|null
	 */
	public static function after($middleWares)
	{
		return array_reduce(array_reverse($middleWares), function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof After) {
					return $pipe->onHandler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		});
	}


	/**
	 * @return Closure
	 */
	private static function core()
	{
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof \HttpServer\IInterface\Middleware) {
					return $pipe->handler($passable, $stack);
				} else {
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
