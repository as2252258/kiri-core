<?php

defined('APP_PATH') or define('APP_PATH', __DIR__ . '/../../');

use HttpServer\Http\Response;
use Snowflake\Snowflake;
use HttpServer\Http\Context;

if (!function_exists('make')) {


	/**
	 * @param $name
	 * @param $default
	 * @return stdClass
	 * @throws
	 */
	function make($name, $default)
	{
		if (Snowflake::has($name)) {
			$class = Snowflake::get()->$name;
		} else if (Snowflake::has($default)) {
			$class = Snowflake::get()->$default;
		} else {
			$class = Snowflake::createObject($default);
			Snowflake::setAlias($name, $default);
		}
		return $class;
	}


}


if (!function_exists('storage')) {

	/**
	 * @param string $fileName
	 * @param string $path
	 * @return string
	 * @throws Exception
	 */
	function storage($fileName = '', $path = '')
	{
		$basePath = Snowflake::getStoragePath();
		if (empty($path)) {
			return $basePath . '/' . $fileName;
		} else if (empty($fileName)) {
			return initDir($basePath, $path);
		}
		return initDir($basePath, $path) . $fileName;
	}


	/**
	 * @param $basePath
	 * @param $path
	 * @return false|string
	 * @throws Exception
	 */
	function initDir($basePath, $path)
	{
		$explode = array_filter(explode('/', $path));
		foreach ($explode as $value) {
			$path .= $value . '/';
			if (!is_dir($basePath . $path)) {
				mkdir($basePath . $path);
			}
			if (!is_dir($basePath . $path)) {
				throw new Exception('System error, directory ' . $basePath . $path . ' is not writable');
			}
		}
		return realpath($basePath . $path);
	}


}


if (!function_exists('alias')) {

	/**
	 * @param $class
	 * @param $name
	 */
	function alias($class, $name)
	{
		Snowflake::setAlias($class, $name);
	}

}



if (!function_exists('name')) {

	/**
	 * @param string $name
	 */
	function name($name)
	{
		swoole_set_process_name($name);
	}

}

if (!function_exists('response')) {

	/**
	 * @return Response|stdClass
	 * @throws
	 */
	function response()
	{
		if (!Context::hasContext('response')) {
			return make('response', Response::class);
		}
		return Context::getContext('response');
	}

}

if (!function_exists('redirect')) {

	function redirect($url)
	{
		return response()->redirect($url);
	}

}



if (!function_exists('env')) {

	/**
	 * @param $key
	 * @param null $default
	 * @return array|false|string|null
	 */
	function env($key, $default = null)
	{
		$env = getenv($key);
		if ($env === false) {
			return $default;
		}
		return $env;
	}

}
