<?php
declare(strict_types=1);


namespace Snowflake;


use Exception;
use HttpServer\IInterface\Task;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\JSON;
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
defined('SOCKET_PATH') or define('SOCKET_PATH', APP_PATH . 'app/Websocket/');

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
	 * @return false|int|mixed
	 * @throws Exception
	 */
	public static function setProcessId($workerId): mixed
	{
		return self::writeFile(storage('socket.sock'), $workerId);
	}


	/**
	 * @param $workerId
	 * @return false|int|mixed
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
	#[Pure] public static function getWebSocket(): ?Server
	{
		$server = static::app()->server->getServer();
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
		$default = APP_PATH . 'storage/server.pid';
		$server = Config::get('settings.pid_file', false, $default);
		return file_get_contents($server);
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
			$data = JSON::encode($data);
		}
		return $server->push($fd, $data);
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
		$server = static::app()->server->getServer();
		if (!isset($server->setting['task_worker_num']) || !class_exists($class)) {
			return;
		}

		$randWorkerId = random_int(0, $server->setting['task_worker_num'] - 1);

		/** @var Task $class */
		$class = static::createObject($class);
		$class->setParams($params);

		$server->task(serialize($class), $randWorkerId);
	}


	/**
	 * @param $process
	 */
	public static function shutdown($process): void
	{
		static::app()->server->getServer()->shutdown();
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
	public static function isMac()
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
