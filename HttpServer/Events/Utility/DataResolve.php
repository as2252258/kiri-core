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
			return JSON::encode($data);
		}
		return self::callbackResolve($unpack, null, null, $data);
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
			return JSON::decode($data);
		}
		return self::callbackResolve($unpack, $address, $port, $data);
	}


	/**
	 * @param $callback
	 * @param $address
	 * @param $port
	 * @param $data
	 * @return array|mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private static function callbackResolve($callback, $address, $port, $data)
	{
		if ($callback instanceof Closure) {
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
