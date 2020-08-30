<?php


namespace Snowflake;


use Exception;
use ReflectionException;
use Snowflake\Di\Container;
use Snowflake\Exception\NotFindClassException;
use Swoole\Coroutine;

class Snowflake
{

	/** @var Container */
	public static $container;


	/** @var Application */
	private static $service;


	/**
	 * @param $service
	 *
	 * 初始化服务
	 */
	public static function init($service)
	{
		static::$service = $service;
	}

	/**
	 * @return mixed
	 */
	public static function get()
	{
		return static::$service;
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public static function has($name)
	{
		return static::$service->has($name);
	}


	/**
	 * @param $className
	 * @param $id
	 */
	public static function setAlias($className, $id)
	{
		return static::$service->setAlias($className, $id);
	}


	/**
	 * @param $className
	 * @param array $construct
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function createObject($className, $construct = [])
	{
		if (is_string($className)) {
			return static::$container->get($className, $construct);
		} else if (is_array($className)) {
			if (!isset($className['class']) || empty($className['class'])) {
				throw new Exception('Object configuration must be an array containing a "class" element.');
			}
			$class = $className['class'];
			unset($className['class']);
			return static::$container->get($class, $construct, $className);
		} else if (is_callable($className, TRUE)) {
			return call_user_func($className, $construct);
		} else {
			throw new Exception('Unsupported configuration type: ' . gettype($className));
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function getStoragePath()
	{
		$path = realpath(static::$service->storage);
		if (!is_dir($path)) {
//			mkdir($path);
		}
		return $path;
	}


	/**
	 * @return bool
	 */
	public static function inCoroutine()
	{
		return Coroutine::getCid() > 0;
	}


	/**
	 * @param $workerId
	 * @return false|int|mixed
	 * @throws Exception
	 */
	public static function setProcessId($workerId)
	{
		return self::writeFile(storage('socket.sock'), $workerId);
	}


	/**
	 * @param $fileName
	 * @param $content
	 * @param int $is_append
	 * @return false|int|mixed
	 */
	public static function writeFile($fileName, $content, $is_append = FILE_APPEND)
	{
		return false;
//		if (self::inCoroutine()) {
//			return Coroutine::writeFile($fileName, $content, $is_append);
//		} else {
//			return file_put_contents($fileName, $content, $is_append);
//		}
	}


	/**
	 * @param $object
	 * @param $config
	 * @return mixed
	 */
	public static function configure($object, $config)
	{
		foreach ($config as $key => $value) {
			$object->$key = $value;
		}
		return $object;
	}

	public static function clearProcessId($worker_pid)
	{

	}


	/**
	 * @return bool
	 */
	public static function isMac()
	{
		$output = strtolower(PHP_OS | PHP_OS_FAMILY);
		if (strpos('mac', $output) !== false) {
			return true;
		} else if (strpos('darwin', $output) !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public static function isLinux()
	{
		if (!static::isMac()) {
			return true;
		} else {
			return false;
		}
	}

	public static function reload()
	{
	}

}

Snowflake::$container = new Container();
