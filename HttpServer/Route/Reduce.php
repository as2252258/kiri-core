<?php
declare(strict_types=1);

namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\After;
use HttpServer\IInterface\Middleware;

class Reduce
{


	/**
	 * @param $last
	 * @param $middleWares
	 * @return mixed
	 */
	public static function reduce($last, $middleWares): mixed
	{
		return array_reduce(array_reverse($middleWares), static::core(), $last);
	}


	/**
	 * @param $middleWares
	 * @return mixed
	 */
	public static function after($middleWares): mixed
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
	private static function core(): Closure
	{
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if ($pipe instanceof Middleware) {
					return $pipe->onHandler($passable, $stack);
				} else {
					var_dump($passable, $stack);
					return $pipe($passable, $stack);
				}
			};
		};
	}

}
