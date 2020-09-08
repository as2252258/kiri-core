<?php


namespace HttpServer\Route;


use Closure;

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
