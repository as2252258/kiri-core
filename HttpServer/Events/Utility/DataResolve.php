<?php


namespace HttpServer\Events\Utility;


use Closure;
use Exception;
use ReflectionException;
use Snowflake\Core\JSON;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

class DataResolve
{


	/**
	 * @param $unpack
	 * @param $data
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function pack($unpack, $data)
	{
		if (empty($unpack)) {
			$params = JSON::encode($data);
		} else {
			$params = self::callbackResolve($unpack, null, null, $data);
		}
		if ($params === null) {
			return 'Format error.';
		}
		return $params;
	}


	/**
	 * @param $unpack
	 * @param $address
	 * @param $port
	 * @param $data
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public static function unpack($unpack, $address, $port, $data)
	{
		if (empty($unpack)) {
			$params = JSON::decode($data);
		} else {
			$params = self::callbackResolve($unpack, $address, $port, $data);
		}
		if ($params === null) {
			return 'Format error.';
		}
		return $params;
	}


	/**
	 * @param $callback
	 * @param $address
	 * @param $port
	 * @param $data
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private static function callbackResolve($callback, $address, $port, $data): mixed
	{
		if ($callback instanceof Closure) {
			if (empty($address) && empty($port)) {
				return $callback($data);
			}
			return $callback($address, $port, $data);
		}
		if (is_string($callback)) {
			$callback = [$callback, 'onHandler'];
		}
		if (isset($callback[0]) && is_string($callback[0])) {
			$callback[0] = Snowflake::getDi()->get($callback[0]);
		}
		if (!empty($address) && !empty($port)) {
			return call_user_func($callback, $address, $port, $data);
		}
		return call_user_func($callback, $data);
	}

}
