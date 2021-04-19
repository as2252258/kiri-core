<?php
declare(strict_types=1);


namespace Snowflake;


use Annotation\Annotation;
use Database\ActiveRecord;
use Database\Collection;
use Exception;
use HttpServer\IInterface\Task;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Di\Container;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Process\Process;
use Swoole\Coroutine;
use Swoole\WebSocket\Server;


defined('DB_ERROR_BUSY') or define('DB_ERROR_BUSY', 'The database is busy. Please try again later.');
defined('SELECT_IS_NULL') or define('SELECT_IS_NULL', 'Query data does not exist, please check the relevant conditions.');
defined('PARAMS_IS_NULL') or define('PARAMS_IS_NULL', 'Required items cannot be empty, please add.');
defined('CONTROLLER_PATH') or define('CONTROLLER_PATH', APP_PATH . 'app/Http/');
defined('CRONTAB_PATH') or define('CRONTAB_PATH', APP_PATH . 'app/Crontab/');
defined('CLIENT_PATH') or define('CLIENT_PATH', APP_PATH . 'app/Client/');
defined('TASK_PATH') or define('TASK_PATH', APP_PATH . 'app/Async/');
defined('LISTENER_PATH') or define('LISTENER_PATH', APP_PATH . 'app/Listener/');
defined('KAFKA_PATH') or define('KAFKA_PATH', APP_PATH . 'app/Kafka/');
defined('RPC_SERVICE_PATH') or define('RPC_SERVICE_PATH', APP_PATH . 'app/Http/Rpc/');
defined('RPC_CLIENT_PATH') or define('RPC_CLIENT_PATH', APP_PATH . 'app/Client/Rpc/');
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


	/** @var ?Application */
	private static ?Application $service = null;


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
	 * @param $alias
	 * @param array $array
	 * @return mixed
	 * @throws Exception
	 */
	public static function set($alias, $array = []): mixed
	{
		return static::app()->set($alias, $array);
	}

	/**
	 * @return Application|null
	 */
	public static function app(): ?Application
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
	 * @param $port
	 * @return bool
	 * @throws Exception
	 */
	public static function port_already($port): bool
	{
		if (empty($port)) {
			return false;
		}
		if (Snowflake::getPlatform()->isLinux()) {
			exec('netstat -tunlp | grep ' . $port, $output);
		} else {
			exec('lsof -i :' . $port . ' | grep -i "LISTEN"', $output);
		}
		return !empty($output);
	}


	/**
	 * @return Annotation
	 * @throws Exception
	 */
	public static function getAnnotation(): Annotation
	{
		return static::app()->getAnnotation();
	}


	/**
	 * @param $service
	 * @return string
	 */
	#[Pure] public static function listen($service): string
	{
		return sprintf('Check listen %s::%d -> ok', $service['host'], $service['port']);
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
		$path = Config::get('storage', $default);
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
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
	public static function setManagerId($workerId): mixed
	{
		if (empty($workerId) || static::isDcoker()) {
			return $workerId;
		}

		$tmpFile = storage($workerId . '.sock', 'pid/manager');

		return self::writeFile($tmpFile, $workerId);
	}


	/**
	 * @param $workerId
	 * @return mixed
	 * @throws Exception
	 */
	public static function setProcessId($workerId): mixed
	{
		if (empty($workerId) || static::isDcoker()) {
			return $workerId;
		}

		$tmpFile = storage($workerId . '.sock', 'pid/process');

		return self::writeFile($tmpFile, $workerId);
	}


	/**
	 * @return bool
	 */
	public static function isDcoker(): bool
	{
		$output = shell_exec('[ -f /.dockerenv ] && echo yes || echo no');
		if (trim($output) === 'yes') {
			return true;
		}
		return false;
	}


	/**
	 * @param $workerId
	 * @return mixed
	 * @throws Exception
	 */
	public static function setWorkerId($workerId): mixed
	{
		if (empty($workerId) || static::isDcoker()) {
			return $workerId;
		}

		$tmpFile = storage($workerId . '.sock', 'pid/worker');

		return self::writeFile($tmpFile, $workerId);
	}


	/**
	 * @param $workerId
	 * @return mixed
	 * @throws Exception
	 */
	public static function setTaskId($workerId): mixed
	{
		if (empty($workerId) || static::isDcoker()) {
			return $workerId;
		}

		$tmpFile = storage($workerId . '.sock', 'pid/task');

		return self::writeFile($tmpFile, $workerId);
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
	 * @param bool $isWorker
	 * @throws Exception
	 */
	public static function clearProcessId($workerId, $isWorker = false)
	{
		clearstatcache();
		$directory = $isWorker === true ? 'pid/worker' : 'pid/task';
		if (!file_exists($file = storage($workerId, $directory))) {
			return;
		}
		shell_exec('rm -rf ' . $file);
	}


	/**
	 * @param string|null $taskPid
	 * @throws Exception
	 */
	public static function clearTaskPid(string $taskPid = null)
	{
		if (empty($taskPid)) {
			exec('rm -rf ' . storage(null, 'pid/task'));
		} else {
			static::clearProcessId($taskPid);
		}
	}


	/**
	 * @param $taskPid
	 * @throws Exception
	 */
	public static function clearWorkerPid($taskPid = null)
	{
		if (empty($taskPid)) {
			exec('rm -rf ' . storage(null, 'pid/worker'));
		} else {
			static::clearProcessId($taskPid, true);
		}
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
		if (empty($server) || !$server->isEstablished($fd)) {
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
		return current(swoole_get_local_ip());
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

		$server->task(swoole_serialize($class));
	}


	/**
	 * @param $v1
	 * @param $v2
	 * @return float
	 */
	#[Pure] public static function distance(array $v1, array $v2): float
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
	 * @throws Exception
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
	 * @return Environmental
	 * @throws
	 */
	public static function getPlatform(): Environmental
	{
		return Snowflake::createObject(Environmental::class);
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public static function reload(): mixed
	{
		return Snowflake::app()->getSwoole()->reload();
	}


	private static array $_autoload = [];


	const PROCESS = 'process';
	const TASK = 'task';
	const WORKER = 'worker';


	/**
	 * @param string $event
	 * @param null $data
	 * @return false|string
	 */
	public static function param(string $event, $data = NULL): bool|string
	{
		if (is_object($data)) {
			if ($data instanceof ActiveRecord || $data instanceof Collection) {
				$data = $data->getAttributes();
			} else {
				$data = get_object_vars($data);
			}
		}
		if (!is_array($data)) $data = ['data' => $data];
		return json_encode(array_merge(['callback' => $event], $data));
	}


	/**
	 * @return string|null
	 */
	#[Pure] public static function getEnvironmental(): ?string
	{
		return env('environmental');
	}


	/**
	 * @return bool
	 */
	#[Pure] public static function isTask(): bool
	{
		return static::getEnvironmental() == static::TASK;
	}


	/**
	 * @return bool
	 */
	#[Pure] public static function isWorker(): bool
	{
		return static::getEnvironmental() == static::WORKER;
	}


	/**
	 * @return bool
	 */
	#[Pure] public static function isProcess(): bool
	{
		return static::getEnvironmental() == static::PROCESS;
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
