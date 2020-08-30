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
use Snowflake\Config;
use Snowflake\Di\Service;
use Snowflake\Error\ErrorHandler;
use Snowflake\Error\Logger;
use Snowflake\Exception\ComponentException;
use Snowflake\Pool\Connection;
use Snowflake\Pool\RedisClient;
use Snowflake\Processes;
use Snowflake\Snowflake;
use Snowflake\Event;

/**
 * Class BaseApplication
 * @package BeReborn\Base
 * @property $json
 * @property Annotation $annotation
 * @property Event $event
 * @property Router $router
 * @property Processes $processes
 * @property \Snowflake\Pool\Pool $pool
 * @property Server $servers
 * @property Connection $connections
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
//		if (isset($config['id'])) {
//			$this->id = $config['id'];
//			unset($config['id']);
//		}
//		if (isset($config['storage'])) {
//			$this->storage = $config['storage'];
//			unset($config['storage']);
//		}
//		if (!empty($this->runtimePath)) {
//			if (!is_dir($this->runtimePath)) {
//				mkdir($this->runtimePath, 777);
//			}
//
//			if (!is_dir($this->runtimePath) || !is_writeable($this->runtimePath)) {
//				throw new InitException("Directory {$this->runtimePath} does not have write permission");
//			}
//		}
//		if (isset($config['aliases'])) {
//			$this->classAlias($config);
//		}
//
//		foreach ($this->moreComponents() as $key => $val) {
//			if (isset($config['components'][$key])) {
//				$config['components'][$key] = array_merge($val, $config['components'][$key]);
//			} else {
//				$config['components'][$key] = $val;
//			}
//		}
	}

	/**
	 * @param array $data
	 */
	private function classAlias(array &$data)
	{
		foreach ($data['aliases'] as $key => $val) {
			class_alias($val, $key, true);
		}
		unset($data['aliases']);
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
	 * @param $ip
	 * @return bool
	 */
	public function isLocal($ip)
	{
		return $this->getFirstLocal() == $ip;
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
			'pool'              => ['class' => \Snowflake\Pool\Pool::class],
			'response'          => ['class' => Response::class],
			'request'           => ['class' => Request::class],
			'config'            => ['class' => Config::class],
			'logger'            => ['class' => Logger::class],
			'router'            => ['class' => Router::class],
		]);
	}
}
