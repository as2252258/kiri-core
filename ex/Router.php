<?php


class Router
{

	private static array $_routers = [];


	/**
	 * @param string $path
	 * @param Closure|array|string $callback
	 */
	public static function get(string $path, Closure|array|string $callback)
	{
		static::$_routers[$path] = $callback;
	}


	/**
	 * @param $path
	 * @return mixed
	 */
	public static function findPath($path): mixed
	{
		if (!isset(static::$_routers[$path])) {
			return null;
		}
		return static::$_routers[$path];
	}


}
