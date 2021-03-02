<?php
declare(strict_types=1);


namespace Snowflake;


use Exception;
use HttpServer\IInterface\Task;

use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Di\Container;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Process\Process;
use Swoole\Coroutine;
use Swoole\WebSocket\Server;


defined('DB_ERROR_BUSY') or define('DB_ERROR', 'The database is busy. Please try again later.');
defined('SELECT_IS_NULL') or define('SELECT_IS_NULL', 'Query data does not exist, please check the relevant conditions.');
defined('PARAMS_IS_NULL') or define('PARAMS_IS_NULL', 'Required items cannot be empty, please add.');
defined('CONTROLLER_PATH') or define('CONTROLLER_PATH', APP_PATH . 'app/Http/Controllers/');
defined('MODEL_PATH') or define('MODEL_PATH', APP_PATH . 'app/Models/');
defined('SOCKET_PATH') or define('SOCKET_PATH', APP_PATH . 'app/Websocket/');


/**
 * Class Snowflake
 * @package Snowflake
 */
class Snowflake
{

	/** @var Container */
	public static Container $container;


	/** @var Application */
	private static Application $service;


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
	public static function app(): Application
	{
		return static::$service;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public static function has($name): bool
	{
		return static::$service->has($name);
	}


	/**
	 * @param $className
	 * @param $id
	 */
	public static function setAlias($className, $id)
	{
		static::$service->setAlias($className, $id);
	}


	/**
	 * @param $className
	 * @param array $construct
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function createObject($className, $construct = []): mixed
	{
		if (is_object($className)) {
			return $className;
		}
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
	public static function getStoragePath(): string
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
	public static function inCoroutine(): bool
	{
		return Coroutine::getCid() > 0;
	}


	/**
	 * @return Container
	 */
	public static function getDi(): Container
	{
		return static::$container;
	}


	/**
	 * @param $workerId
	 * @return mixed
	 * @throws Exception
	 */
	public static function setProcessId($workerId): mixed
	{
		return self::writeFile(storage('socket.sock'), $workerId);
	}


	/**
	 * @param $workerId
	 * @return mixed
	 * @throws Exception
	 */
	public static function setWorkerId($workerId): mixed
	{
		if (empty($workerId)) {
			return $workerId;
		}
		return self::writeFile(storage($workerId . '.sock', 'worker'), $workerId);
	}


	/**
	 * @param $fileName
	 * @param $content
	 * @param null $is_append
	 * @return mixed
	 */
	public static function writeFile($fileName, $content, $is_append = null): mixed
	{
		$params = [$fileName, (string)$content];
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
	public static function configure($object, $config): mixed
	{
		foreach ($config as $key => $value) {
			if (!property_exists($object, $key)) {
				continue;
			}
			$object->$key = $value;
		}
		return $object;
	}


	/**
	 * @param $workerId
	 * @throws Exception
	 */
	public static function clearProcessId($workerId)
	{
		@unlink(storage($workerId . '.sock', 'worker'));
	}


	/**
	 * @return Server|null
	 * @throws
	 */
	public static function getWebSocket(): ?Server
	{
		$server = static::app()->getSwoole();
		if (!($server instanceof Server)) {
			return null;
		}
		return $server;
	}


	/**
	 * @return false|string
	 * @throws Exception
	 */
	public static function getMasterPid(): bool|string
	{
		$pid = Snowflake::app()->getSwoole()->setting['pid_file'];

		return file_get_contents($pid);
	}


	/**
	 * @param int $fd
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	public static function push(int $fd, $data): mixed
	{
		$server = static::getWebSocket();
		if (empty($server)) {
			return false;
		}
		if (!is_string($data)) {
			$data = Json::encode($data);
		}
		return $server->push($fd, $data);
	}


	/**
	 * @return mixed
	 */
	public static function localhost(): mixed
	{
		return Snowflake::app()->getFirstLocal();
	}


	/**
	 * @param string $class
	 * @param array $params
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function async(string $class, array $params = [])
	{
		$server = static::app()->getSwoole();
		if (!isset($server->setting['task_worker_num']) || !class_exists($class)) {
			return;
		}

		/** @var Task $class */
		$class = static::createObject($class);
		$class->setParams($params);

		$server->task(serialize($class));
	}


	/**
	 * @param $v1
	 * @param $v2
	 * @return float
	 */
	public static function distance(array $v1, array $v2): float
	{
		$maxX = max($v1['x'], $v2['x']);
		$minX = min($v1['x'], $v2['x']);

		$maxZ = max($v1['z'], $v2['z']);
		$minZ = min($v1['z'], $v2['z']);

		$dx = abs($maxX - $minX);
		$dy = abs($maxZ - $minZ);

		$sqrt = sqrt($dx * $dx + $dy * $dy);
		if ($sqrt < 0) {
			$sqrt = abs($sqrt);
		}
		return (float)$sqrt;
	}


	/**
	 * @param $process
	 * @throws ComponentException
	 */
	public static function shutdown($process): void
	{
		static::app()->getSwoole()->shutdown();
		if ($process instanceof Process) {
			$process->exit(0);
		}
	}


	/**
	 * @param $tmp
	 * @return string
	 */
	public static function rename($tmp): string
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
	public static function isMac(): bool
	{
		$output = strtolower(PHP_OS | PHP_OS_FAMILY);
		if (str_contains('mac', $output)) {
			return true;
		} else if (str_contains('darwin', $output)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public static function isLinux(): bool
	{
		if (!static::isMac()) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public static function reload(): mixed
	{
		return Process::kill((int)Snowflake::getMasterPid(), SIGUSR1);
	}


	private static array $_autoload = [];


	const PROCESS = 'process';
	const TASK = 'task';
	const WORKER = 'worker';


	/**
	 * @return string|null
	 */
	public static function getEnvironmental(): ?string
	{
		return env('environmental');
	}


	/**
	 * @param $class
	 * @param $file
	 */
	public static function setAutoload($class, $file)
	{
		if (isset(static::$_autoload[$class])) {
			return;
		}
		static::$_autoload[$class] = $file;
		include_once "$file";
	}


	/**
	 * @param $className
	 */
	public static function autoload($className)
	{
		if (!isset(static::$_autoload[$className])) {
			return;
		}
		$file = static::$_autoload[$className];
		require_once "$file";
	}


}

//spl_autoload_register([Snowflake::class, 'autoload'], true, true);
Snowflake::$container = new Container();
