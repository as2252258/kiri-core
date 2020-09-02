<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/10/7 0007
 * Time: 2:13
 */

namespace Snowflake\Abstracts;


use Exception;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\Route\Router;
use HttpServer\Server;
use Snowflake\Annotation\Annotation;
use Snowflake\Cache\Redis;
use Snowflake\Config;
use Snowflake\Di\Service;
use Snowflake\Error\ErrorHandler;
use Snowflake\Error\Logger;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\InitException;
use Snowflake\Jwt\Jwt;
use Snowflake\Pool\Connection;
use Snowflake\Pool\RedisClient;
use Snowflake\Processes;
use Snowflake\Snowflake;
use Snowflake\Event;
use Snowflake\Pool\Pool as SPool;
use Database\DatabasesProviders;

/**
 * Class BaseApplication
 * @package Snowflake\Snowflake\Base
 * @property $json
 * @property Annotation $annotation
 * @property Event $event
 * @property Router $router
 * @property Processes $processes
 * @property SPool $pool
 * @property Server $servers
 * @property DatabasesProviders $db
 * @property Connection $connections
 * @property Logger $logger
 * @property Jwt $jwt
 */
abstract class BaseApplication extends Service
{

	/**
	 * @var string
	 */
	public $storage = APP_PATH . '/storage';

	public $envPath = APP_PATH . '/.env';

	/**
	 * Init constructor.
	 *
	 * @param array $config
	 *
	 * @throws
	 */
	public function __construct(array $config = [])
	{
		Snowflake::init($this);

		$this->moreComponents();
		$this->parseInt($config);
		$this->initErrorHandler();
		$this->enableEnvConfig();

		Component::__construct($config);
	}


	/**
	 * @return mixed
	 */
	public function enableEnvConfig()
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
	protected function readLinesFromFile($filePath)
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
	protected function isComment($line)
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
	protected function looksLikeSetter($line)
	{
		return strpos($line, '=') !== false;
	}


	/**
	 * @param $config
	 *
	 * @throws
	 */
	public function parseInt($config)
	{
		foreach ($config as $key => $value) {
			Config::set($key, $value);
		}
		if ($storage = Config::get('storage', false, 'storage')) {
			if (strpos($storage, APP_PATH) === false) {
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
	 * @throws ComponentException
	 */
	public function clone($name)
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
	 * @return mixed
	 */
	public function getLocalIps()
	{
		return swoole_get_local_ip();
	}

	/**
	 * @return mixed
	 */
	public function getFirstLocal()
	{
		return current($this->getLocalIps());
	}


	/**
	 * @return Logger
	 * @throws ComponentException
	 */
	public function getLogger(): Logger
	{
		return $this->get('logger');
	}


	/**
	 * @return \Redis
	 * @throws ComponentException
	 */
	public function getRedis()
	{
		return $this->get('redis');
	}

	/**
	 * @param $ip
	 * @return bool
	 */
	public function isLocal($ip)
	{
		return $this->getFirstLocal() == $ip;
	}


	/**
	 * @return ErrorHandler
	 * @throws ComponentException
	 */
	public function getError()
	{
		return $this->get('error');
	}


	/**
	 * @return Processes
	 * @throws ComponentException
	 */
	public function getProcesses()
	{
		return $this->get('processes');
	}


	/**
	 * @return Annotation
	 * @throws ComponentException
	 */
	public function getAnnotation()
	{
		return $this->get('annotation');
	}


	/**
	 * @return Connection
	 * @throws ComponentException
	 */
	public function getConnections()
	{
		return $this->get('connections');
	}


	/**
	 * @return Pool
	 * @throws ComponentException
	 */
	public function getPool()
	{
		return $this->get('pool');
	}

	/**
	 * @return Response
	 * @throws ComponentException
	 */
	public function getResponse()
	{
		return $this->get('response');
	}

	/**
	 * @return Request
	 * @throws ComponentException
	 */
	public function getRequest()
	{
		return $this->get('request');
	}


	/**
	 * @return Config
	 * @throws ComponentException
	 */
	public function getConfig()
	{
		return $this->get('config');
	}


	/**
	 * @return Router
	 * @throws ComponentException
	 */
	public function getRouter()
	{
		return $this->get('router');
	}


	/**
	 * @return Jwt
	 * @throws ComponentException
	 */
	public function getJwt()
	{
		return $this->get('jwt');
	}


	/**
	 * @throws Exception
	 */
	protected function moreComponents()
	{
		return $this->setComponents([
			'error'             => ['class' => ErrorHandler::class],
			'event'             => ['class' => Event::class],
			'annotation'        => ['class' => Annotation::class],
			'processes'         => ['class' => Processes::class],
			'connections'       => ['class' => Connection::class],
			'redis_connections' => ['class' => RedisClient::class],
			'pool'              => ['class' => SPool::class],
			'response'          => ['class' => Response::class],
			'request'           => ['class' => Request::class],
			'config'            => ['class' => Config::class],
			'logger'            => ['class' => Logger::class],
			'router'            => ['class' => Router::class],
			'redis'             => ['class' => Redis::class],
			'jwt'               => ['class' => Jwt::class],
			'server'            => ['class' => Server::class],
		]);
	}
}
