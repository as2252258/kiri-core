<?php
declare(strict_types=1);


error_reporting(0);


use Database\Collection;
use Database\ModelInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Annotation;
use Kiri\Application;
use Kiri\Core\Json;
use Kiri\Di\Container;
use Kiri\Environmental;
use Kiri\Di\ContainerInterface;
use Kiri\Exception\ConfigException;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\WebSocket\Server;

defined('DB_ERROR_BUSY') or define('DB_ERROR_BUSY', 'The database is busy. Please try again later.');
defined('SELECT_IS_NULL') or define('SELECT_IS_NULL', 'Query data does not exist, please check the relevant conditions.');
defined('PARAMS_IS_NULL') or define('PARAMS_IS_NULL', 'Required items cannot be empty, please add.');
defined('CONTROLLER_PATH') or define('CONTROLLER_PATH', realpath(APP_PATH . 'controllers/'));
defined('MODEL_PATH') or define('MODEL_PATH', realpath(APP_PATH . 'models/'));
defined('COMPONENT_PATH') or define('COMPONENT_PATH', realpath(APP_PATH . 'components/'));
defined('URL_MATCH') or define('URL_MATCH', '/(http[s]?:\/\/)?((?:[\w\-_]+\.)+\w+(?::\d+)?)(?:(\/[a-zA-Z0-9-\/]+)+[\/]?(\?[a-zA-Z]+=.*)?)?/');


/**
 * Class Kiri
 * @package Kiri
 */
class Kiri
{

	/** @var Container */
	private static Container $container;


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
	 * @param Container $container
	 */
	public static function setContainer(Container $container)
	{
		$container->setBindings(ContainerInterface::class, $container);
		static::$container = $container;
	}


	/**
	 * @return Container
	 */
	public static function getContainer(): Container
	{
		return static::$container;
	}


	/**
	 * @param $alias
	 * @param array $array
	 * @throws Exception
	 */
	public static function set($alias, array $array = [])
	{
		static::app()->set($alias, $array);
	}


	/**
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public static function getApp(string $name): mixed
	{
		return static::app()->get($name);
	}

	/**
	 * @return Application|null
	 */
	public static function app(): ?Application
	{
		return static::$service;
	}


	/**
	 * @return Application|null
	 */
	public static function getFactory(): ?Application
	{
		return static::$service;
	}


	/**
	 * @return Application|null
	 */
	public static function getApplicationContext(): ?Application
	{
		return static::$service;
	}


	/**
	 * @return Container|null
	 */
	public static function getContainerContext(): ?Container
	{
		return static::$container;
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
	 * @return Annotation
	 * @throws Exception
	 */
	public static function getAnnotation(): Annotation
	{
		return static::getDi()->get(Annotation::class);
	}


	/**
	 * @param $className
	 * @param array $construct
	 * @return mixed
	 * @throws Exception
	 */
	public static function createObject($className, array $construct = []): mixed
	{
		if (is_string($className) && class_exists($className)) {
			return static::$container->get($className, $construct);
		} else if (is_array($className) && isset($className['class'])) {
			$class = $className['class'];
			unset($className['class']);
			return static::$container->create($class, $construct, $className);
		} else if (is_callable($className, TRUE)) {
			return call_user_func($className, $construct);
		} else {
			throw new Exception('Unsupported configuration type: ' . gettype($className));
		}
	}


	/**
	 * @param $prefix
	 * @return void
	 * @throws ConfigException
	 */
	public static function setProcessName($prefix): void
	{
		if (Kiri::getPlatform()->isMac()) {
			return;
		}
		$name = '[' . Config::get('id', 'system-service') . ']';
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
		swoole_set_process_name($name);
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
	 * @return Container
	 */
	public static function getDi(): Container
	{
		return static::$container;
	}


	/**
	 * @return Container
	 */
	public static function di(): Container
	{
		return static::$container;
	}


	/**
	 * @return bool
	 */
	public static function isDocker(): bool
	{
		$output = shell_exec('[ -f /.dockerenv ] && echo yes || echo no');
		if (trim($output) === 'yes') {
			return true;
		}
		return false;
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
		return !(Coroutine::getCid() > 0) ? file_put_contents(...$params) : Coroutine::writeFile(...$params);
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
	 * @return mixed
	 */
	public static function localhost(): mixed
	{
		return current(swoole_get_local_ip());
	}


	/**
	 * @param array $v1
	 * @param array $v2
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
	 * @param $tmp_name
	 * @return string
	 */
	public static function rename($tmp_name): string
	{
		$hash = md5_file($tmp_name);

		$later = '.' . exif_imagetype($tmp_name);

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
		return Kiri::createObject(Environmental::class);
	}


	const PROCESS = 'process';
	const TASK = 'task';
	const WORKER = 'worker';

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

}

Kiri::setContainer(new Container());
