<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Annotation\Annotation as SAnnotation;
use Exception;
use HttpServer\Client\Http2;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\HttpFilter;
use HttpServer\Route\Router;
use HttpServer\Server;
use HttpServer\Shutdown;
use JetBrains\PhpStorm\Pure;
use Kafka\KafkaProvider;
use ReflectionException;
use Server\ServerManager;
use Snowflake\Aop;
use Snowflake\Async;
use Snowflake\Cache\Redis;
use Snowflake\Di\Service;
use Snowflake\Error\ErrorHandler;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\InitException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Jwt\Jwt;
use Snowflake\Pool\Connection;
use Snowflake\Pool\Pool;
use Snowflake\Pool\Redis as SRedis;
use Snowflake\Snowflake;
use Swoole\Table;

/**
 * Class BaseApplication
 * @package Snowflake\Snowflake\Base
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
		Snowflake::init($this);

		$config = sweep(APP_PATH . '/config');

		$this->moreComponents();
		$this->parseInt($config);
		$this->parseEvents($config);
		$this->initErrorHandler();
		$this->enableEnvConfig();

		parent::__construct();
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
	#[Pure] protected function looksLikeSetter(string $line): bool
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
	 * @throws \ReflectionException
	 * @throws \Snowflake\Exception\NotFindClassException
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
				$value = Snowflake::createObject($value);
			}
			$this->addEvent($key, $value);
		}
	}


	/**
	 * @param $key
	 * @param $value
	 * @throws InitException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function addEvent($key, $value): void
	{
		if ($value instanceof \Closure) {
			Event::on($key, $value, true);
			return;
		}
		if (is_object($value)) {
			Event::on($key, $value, true);
			return;
		}
		if (is_array($value)) {
			if (is_object($value[0]) && !($value[0] instanceof \Closure)) {
				Event::on($key, $value, true);
				return;
			}

			if (is_string($value[0])) {
				$value[0] = Snowflake::createObject($value[0]);
				Event::on($key, $value, true);
				return;
			}

			foreach ($value as $item) {
				if (!is_callable($item, true)) {
					throw new InitException("Class does not hav callback.");
				}
				Event::on($key, $item, true);
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
	 * @throws \ReflectionException
	 * @throws \Snowflake\Exception\NotFindClassException
	 */
	public function get($name): mixed
	{
		return di(Service::class)->get($name);
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
	 * @throws Exception
	 */
	public function getLogger(): Logger
	{
		return $this->get('logger');
	}


	/**
	 * @return \Redis|Redis
	 * @throws Exception
	 */
	public function getRedis(): Redis|\Redis
	{
		return $this->get('redis');
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
	 * @throws Exception
	 */
	public function getError(): ErrorHandler
	{
		return $this->get('error');
	}


	/**
	 * @return Connection
	 * @throws Exception
	 */
	public function getMysqlFromPool(): Connection
	{
		return $this->get('connections');
	}


	/**
	 * @return SRedis
	 * @throws Exception
	 */
	public function getRedisFromPool(): SRedis
	{
		return $this->get('redis_connections');
	}


	/**
	 * @param $name
	 * @return Table
	 * @throws Exception
	 */
	public function getTable($name): Table
	{
		return $this->get($name);
	}


	/**
	 * @return Config
	 * @throws Exception
	 */
	public function getConfig(): Config
	{
		return $this->get('config');
	}


	/**
	 * @return Router
	 * @throws Exception
	 */
	public function getRouter(): Router
	{
		return $this->get('router');
	}


	/**
	 * @return Jwt
	 * @throws Exception
	 */
	public function getJwt(): Jwt
	{
		return $this->get('jwt');
	}


	/**
	 * @return Server
	 * @throws Exception
	 */
	public function getServer(): Server
	{
		return $this->get('server');
	}


	/**
	 * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	 */
	public function getSwoole(): \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
	{
		return ServerManager::getContext()->getServer();
	}


	/**
	 * @return SAnnotation
	 * @throws Exception
	 */
	public function getAnnotation(): SAnnotation
	{
		return $this->get('annotation');
	}


	/**
	 * @return Async
	 * @throws Exception
	 */
	public function getAsync(): Async
	{
		return $this->get('async');
	}


	/**
	 * @return \Rpc\Producer
	 * @throws Exception
	 */
	public function getRpc(): \Rpc\Producer
	{
		return $this->get('rpc');
	}


	/**
	 * @return Pool
	 * @throws Exception
	 */
	public function getClientsPool(): Pool
	{
		return $this->get('clientsPool');
	}


	/**
	 * @param $array
	 * @throws \ReflectionException
	 * @throws \Snowflake\Exception\NotFindClassException
	 */
	private function setComponents($array): void
	{
		di(Service::class)->setComponents($array);
	}


	/**
	 * @param $id
	 * @param $definition
	 * @throws \ReflectionException
	 * @throws \Snowflake\Exception\NotFindClassException
	 */
	public function set($id, $definition): void
	{
		di(Service::class)->set($id, $definition);
	}


	/**
	 * @param $id
	 * @param $definition
	 * @throws \ReflectionException
	 * @throws \Snowflake\Exception\NotFindClassException
	 */
	public function has($id): bool
	{
		return di(Service::class)->has($id);
	}


	/**
	 * @throws Exception
	 */
	protected function moreComponents(): void
	{
		$this->setComponents([
			'error'             => ['class' => ErrorHandler::class],
			'connections'       => ['class' => Connection::class],
			'redis_connections' => ['class' => SRedis::class],
			'clientsPool'       => ['class' => Pool::class],
			'config'            => ['class' => Config::class],
			'logger'            => ['class' => Logger::class],
			'annotation'        => ['class' => SAnnotation::class],
			'router'            => ['class' => Router::class],
			'event'             => ['class' => Event::class],
			'redis'             => ['class' => Redis::class],
			'databases'         => ['class' => \Database\Connection::class],
			'aop'               => ['class' => Aop::class],
			'input'             => ['class' => HttpParams::class],
			'header'            => ['class' => HttpHeaders::class],
			'jwt'               => ['class' => Jwt::class],
			'async'             => ['class' => Async::class],
			'kafka-container'   => ['class' => KafkaProvider::class],
			'filter'            => ['class' => HttpFilter::class],
			'goto'              => ['class' => BaseGoto::class],
			'response'          => ['class' => Response::class],
			'request'           => ['class' => Request::class],
			'rpc'               => ['class' => \Rpc\Producer::class],
			'rpc-service'       => ['class' => \Rpc\Service::class],
			'http2'             => ['class' => Http2::class],
			'shutdown'          => ['class' => Shutdown::class],
		]);
	}
}
