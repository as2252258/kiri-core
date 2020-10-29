<?php
declare(strict_types=1);

namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\After;

class Reduce
{


	/**
	 * @param $last
	 * @param $middleWares
	 * @return array
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
			return function ($request, $passable) use ($stack, $pipe) {
				if ($pipe instanceof After) {
					return $pipe->onHandler($request, $passable);
				} else {
					return $pipe($request, $passable, $stack);
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
