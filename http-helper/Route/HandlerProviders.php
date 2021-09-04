<?php

namespace Http\Route;

use Kiri\Abstracts\BaseObject;


/**
 *
 */
class HandlerProviders extends BaseObject
{

	private static array $handlers = [];


	/**
	 * @param $path
	 * @param $method
	 * @return mixed
	 */
	public static function get($path, $method): ?Pipeline
	{
		return static::$handlers[$method][$path] ?? null;
	}


	/**
	 * @param $method
	 * @param $path
	 * @param $handler
	 */
	public static function add($method, $path, $handler)
	{
        static::$handlers[$method][$path] = $handler;
	}

}
