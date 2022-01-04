<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */
declare(strict_types=1);

namespace Kiri\Abstracts;


use Note\Note as SNote;
use Database\Connection;
use Exception;
use Http\Handler\Router;
use Kiri\Events\OnBeforeCommandExecute;
use Server\Server;
use Kafka\KafkaProvider;
use Kiri\Async;
use Kiri\Cache\Redis;
use Kiri\Di\LocalService;
use Kiri\Error\ErrorHandler;
use Kiri\Error\Logger;
use Kiri\Events\EventProvider;
use Kiri\Exception\InitException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\ServerManager;
use Server\Contract\OnTaskInterface;
use Server\Tasker\AsyncTaskExecute;
use Swoole\Table;

/**
 * Class BaseApplication
 * @package Kiri\Kiri\Base
 */
abstract class BaseApplication extends Component
{

	use TraitApplication;


	/**
	 * @var string
	 */
	public string $storage = APP_PATH . 'storage';

	public string $envPath = APP_PATH . '.env';

	/**
	 * Init constructor.
	 *
	 *
	 * @throws
	 */
	public function __construct()
	{
		Kiri::init($this);

		$config = sweep(APP_PATH . '/config');

		$this->moreComponents();
		$this->parseInt($config);
		$this->parseEvents($config);
		$this->initErrorHandler();
		$this->enableEnvConfig();
		$this->mapping($config['mapping'] ?? []);

		parent::__construct();
	}


	/**
	 * @param array $mapping
	 */
	public function mapping(array $mapping)
	{
		$di = Kiri::getDi();
		foreach ($mapping as $interface => $class) {
			$di->mapping($interface, $class);
		}
	}


	/**
	 * @return array
	 */
	public function enableEnvConfig(): array
	{
		if (!file_exists($this->envPath)) {
			return [];
		}
		$lines = $this->readLinesFromFile($this->envPath);
		foreach ($lines as $line) {
			if (!$this->isComment($line) && $this->looksLikeSetter($line)) {
				[$key, $value] = explode('=', $line);
				putenv(trim($key) . '=' . trim($value));
			}
		}
		return $lines;
	}


	/**
	 * Read lines from the file, auto detecting line endings.
	 *
	 * @param string $filePath
	 *
	 * @return array
	 */
	protected function readLinesFromFile(string $filePath): array
	{
		// Read file into an array of lines with auto-detected line endings
		$autodetect = ini_get('auto_detect_line_endings');
		ini_set('auto_detect_line_endings', '1');
		$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		ini_set('auto_detect_line_endings', $autodetect);

		return $lines;
	}

	/**
	 * Determine if the line in the file is a comment, e.g. begins with a #.
	 *
	 * @param string $line
	 *
	 * @return bool
	 */
	protected function isComment(string $line): bool
	{
		$line = ltrim($line);

		return isset($line[0]) && $line[0] === '#';
	}

	/**
	 * Determine if the given line looks like it's setting a variable.
	 *
	 * @param string $line
	 *
	 * @return bool
	 */
	protected function looksLikeSetter(string $line): bool
	{
		return str_contains($line, '=');
	}


	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseInt($config)
	{
		Config::sets($config);
		if ($storage = Config::get('storage', 'storage')) {
			if (!str_contains($storage, APP_PATH)) {
				$storage = APP_PATH . $storage . '/';
			}
			if (!is_dir($storage)) {
				mkdir($storage);
			}
			if (!is_dir($storage) || !is_writeable($storage)) {
				throw new InitException("Directory {$storage} does not have write permission");
			}
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function __get($name): mixed
	{
		if ($this->has($name)) {
			return $this->get($name);
		}
		return parent::__get($name); // TODO: Change the autogenerated stub
	}


	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseEvents($config)
	{
		if (!isset($config['events']) || !is_array($config['events'])) {
			return;
		}
		foreach ($config['events'] as $key => $value) {
			if (is_string($value)) {
				$value = Kiri::createObject($value);
			}
			$this->addEvent($key, $value);
		}
	}


	/**
	 * @param OnTaskInterface $execute
	 * @throws ReflectionException
	 */
	public function task(OnTaskInterface $execute): void
	{
		di(AsyncTaskExecute::class)->execute($execute);
	}


	/**
	 * @param $key
	 * @param $value
	 * @throws InitException
	 * @throws Exception
	 */
	private function addEvent($key, $value): void
	{
		if ($value instanceof \Closure || is_object($value)) {
			$this->eventProvider->on($key, $value, 0);
			return;
		}


		if (is_array($value)) {
			if (is_object($value[0]) && !($value[0] instanceof \Closure)) {
				$this->eventProvider->on($key, $value, 0);
				return;
			}

			if (is_string($value[0])) {
				$value[0] = Kiri::createObject($value[0]);
				$this->eventProvider->on($key, $value, 0);
				return;
			}


			foreach ($value as $item) {
				if (!is_callable($item, true)) {
					throw new InitException("Class does not hav callback.");
				}
				$this->eventProvider->on($key, $item, 0);
			}
		}

	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function clone($name): mixed
	{
		return clone $this->get($name);
	}

	/**
	 *
	 * @throws Exception
	 */
	public function initErrorHandler()
	{
		$this->get('error')->register();
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws
	 */
	public function get($name): mixed
	{
		return di(LocalService::class)->get($name);
	}


	/**
	 * @return mixed
	 */
	public function getLocalIps(): mixed
	{
		return swoole_get_local_ip();
	}

	/**
	 * @return mixed
	 */
	public function getFirstLocal(): mixed
	{
		return current($this->getLocalIps());
	}


	/**
	 * @return Logger
	 * @throws
	 */
	public function getLogger(): Logger
	{
		return $this->get('logger');
	}


	/**
	 * @return \Redis|Redis
	 * @throws
	 */
	public function getRedis(): Redis|\Redis
	{
		return Kiri::getDi()->get(Redis::class);
	}

	/**
	 * @param $ip
	 * @return bool
	 */
	public function isLocal($ip): bool
	{
		return $this->getFirstLocal() == $ip;
	}


	/**
	 * @return ErrorHandler
	 * @throws
	 */
	public function getError(): ErrorHandler
	{
		return $this->get('error');
	}


	/**
	 * @param $name
	 * @return Table
	 * @throws
	 */
	public function getTable($name): Table
	{
		return $this->get($name);
	}


	/**
	 * @return Config
	 * @throws
	 */
	public function getConfig(): Config
	{
		return $this->get('config');
	}


	/**
	 * @return Router
	 * @throws
	 */
	public function getRouter(): Router
	{
		return Kiri::getDi()->get(Router::class);
	}


	/**
	 * @return Server
	 * @throws
	 */
	public function getServer(): Server
	{
		return $this->get('server');
	}


	/**
	 * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	 * @throws
	 */
	public function getSwoole(): \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	{
		return di(ServerManager::class)->getServer();
	}


	/**
	 * @return SNote
	 * @throws
	 */
	public function getNote(): SNote
	{
		return $this->get('note');
	}


	/**
	 * @return Async
	 * @throws
	 */
	public function getAsync(): Async
	{
		return $this->get('async');
	}



	/**
	 * @param $array
	 */
	private function setComponents($array): void
	{
		di(LocalService::class)->setComponents($array);
	}


	/**
	 * @param $id
	 * @param $definition
	 */
	public function set($id, $definition): void
	{
		di(LocalService::class)->set($id, $definition);
	}


	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id): bool
	{
		return di(LocalService::class)->has($id);
	}


	/**
	 * @throws Exception
	 */
	protected function moreComponents(): void
	{
		$this->setComponents([
			'error'           => ['class' => ErrorHandler::class],
			'config'          => ['class' => Config::class],
			'logger'          => ['class' => Logger::class],
			'note'      => ['class' => SNote::class],
			'databases'       => ['class' => Connection::class],
			'jwt'             => ['class' => Jwt::class],
			'async'           => ['class' => Async::class],
			'kafka-container' => ['class' => KafkaProvider::class],
		]);
	}
}
