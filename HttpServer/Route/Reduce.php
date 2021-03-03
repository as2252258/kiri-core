<?php
declare(strict_types=1);

namespace HttpServer\Route;


use Closure;
use HttpServer\IInterface\After;
use HttpServer\IInterface\Middleware;
use Snowflake\Core\Json;
use Snowflake\Event;

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
				try {
					if (!($pipe instanceof After)) {
						return call_user_func($pipe, $request, $passable, $stack);
					}
					return $pipe->onHandler($request, $passable);
				} catch (\Throwable $throwable) {
					return Json::to(0, $throwable);
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
					return call_user_func($pipe, $passable, $stack);
				}
			};
		};
	}

}
