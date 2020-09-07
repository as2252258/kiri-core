<?php


namespace Snowflake;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Config;
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
	public static function app()
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
		$default = APP_PATH . 'storage' . DIRECTORY_SEPARATOR;
		$path = Config::get('storage', false, $default);
		if (!is_dir($path)) {
			mkdir($path);
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
	 * @return Container
	 */
	public static function getDi()
	{
		return static::$container;
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
	 * @param $workerId
	 * @return false|int|mixed
	 * @throws Exception
	 */
	public static function setWorkerId($workerId)
	{
		return self::writeFile(storage($workerId . '.sock', 'worker'), $workerId);
	}


	/**
	 * @throws Exception
	 */
	public static function clearWorkerId()
	{
		$dir = storage(null, 'worker');
		foreach (glob($dir . '/*') as $file) {
			@unlink($file);
		}
	}


	/**
	 * @param $fileName
	 * @param $content
	 * @param null $is_append
	 * @return false|int|mixed
	 */
	public static function writeFile($fileName, $content, $is_append = null)
	{
		$params = [$fileName, $content];
		if ($is_append !== null) {
			$params[] = $is_append;
		}
		return !self::inCoroutine() ? file_put_contents(...$params) : Coroutine::writeFile(...$params);
	}


	/**
	 * @param $object
	 * @param $config
	 * @return mixed
	 */
	public static function configure($object, $config)
	{
		foreach ($config as $key => $value) {
			if (!property_exists($object, $key)) {
				continue;
			}
			$object->$key = $value;
		}
		return $object;
	}

	public static function clearProcessId($worker_pid)
	{

	}


	public static function rename($tmp)
	{
		$hash = md5_file($tmp['tmp_name']);

		$later = '.' . exif_imagetype($tmp['tmp_name']);

		$match = '/(\w{12})(\w{5})(\w{9})(\w{6})/';
		$tmp = preg_replace($match, '$1-$2-$3-$4', $hash);

		return strtoupper($tmp) . $later;
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
