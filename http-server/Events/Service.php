<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Application;
use HttpServer\ServerManager;
use ReflectionException;
use Snowflake\Core\JSON;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Process\Pool;
use Swoole\Server;
use Closure;

/**
 * Class Service
 * @package HttpServer\Events
 */
abstract class Service extends Server
{

	/** @var \Snowflake\Application */
	protected $application;


	/** @var Closure|array */
	public $unpack;


	/** @var Closure|array */
	public $pack;


	/**
	 * Receive constructor.
	 * @param $application
	 * @param $host
	 * @param null $port
	 * @param null $mode
	 * @param null $sock_type
	 */
	public function __construct($application, $host, $port = null, $mode = null, $sock_type = null)
	{
		parent::__construct($host, $port, $mode, $sock_type);
		$this->application = $application;
	}


	/**
	 * @param array $settings
	 * @param null $pool
	 * @param array $events
	 * @param array $config
	 * @return mixed|void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function set(array $settings, $pool = null, $events = [], $config = [])
	{
		parent::set($settings);
		Snowflake::get()->set(Pool::class, $pool);
		ServerManager::set($this, $settings, $this->application, $events, $config);
	}


	/**
	 * @param $callbacks
	 */
	protected function bindCallback($callbacks)
	{
		if (empty($callbacks) || !is_array($callbacks)) {
			return;
		}
		foreach ($callbacks as $callback) {
			$this->on($callback[0], [$this, $callback[1][1]]);
		}
	}


	/**
	 * @param $eventName
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function createHandler($eventName)
	{
		$classPrefix = 'HttpServer\Events\Trigger\On' . ucfirst($eventName);
		if (!class_exists($classPrefix)) {
			throw new Exception('class not found.');
		}
		$class = Snowflake::createObject($classPrefix, [Snowflake::get()]);
		return [$class, 'onHandler'];
	}


	/**
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	public function pack($data)
	{
		$callback = $this->pack;
		if (is_callable($callback, true)) {
			return $callback($data);
		}
		return JSON::encode($data);
	}


	/**
	 * @param $data
	 * @return mixed
	 */
	public function unpack($data)
	{
		$callback = $this->unpack;
		if (is_callable($callback, true)) {
			return $callback($data);
		}
		return JSON::decode($data);
	}

}
