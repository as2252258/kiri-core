<?php


namespace HttpServer;

use Exception;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

class ServerManager
{


	/**
	 * @param $pool
	 * @param $process
	 * @param $workerId
	 * @return mixed
	 */
	public static function create($pool, $process, $workerId)
	{
		try {
			$application = Snowflake::get();
			if (is_string($process) && class_exists($process)) {
				return static::createProcess($process, $application, $pool, $workerId);
			}
			[$category, $config, $handlers, $settings] = $process;
			$server = new $category[1](...static::parameter($application, $config, $category));
			$server->set($settings ?? [], $pool, $handlers, $config);
			static::notice($application, $workerId, $config);
			if (property_exists($server, 'pack')) {
				$server->pack = $config['message']['pack'] ?? function ($data) {
						return $data;
					};
			}
			if (property_exists($server, 'unpack')) {
				$server->unpack = $config['message']['unpack'] ?? function ($data) {
						return $data;
					};
			}
			return $server->start();
		} catch (Exception $exception) {
			echo $exception->getMessage() . PHP_EOL;
			return $pool->shutdown();
		}
	}


	/**
	 * @param $application
	 * @param $config
	 * @param $category
	 * @return array
	 */
	protected static function parameter($application, $config, $category)
	{
		return [$application, $config['host'], $config['port'], SWOOLE_PROCESS, $category[0]];
	}


	/**
	 * @param $process
	 * @param $application
	 * @param $pool
	 * @param $workerId
	 * @return mixed
	 */
	protected static function createProcess($process, $application, $pool, $workerId)
	{
		$application->set($pool->getProcess($workerId));
		$process = new $process($application);
		$application->debug(sprintf('Worker #%d is running.', $workerId));
		return $process->start();
	}


	/**
	 * @param $application
	 * @param $workerId
	 * @param $config
	 */
	protected static function notice($application, $workerId, $config)
	{
		$application->debug(sprintf('Worker #%d Listener %s::%d is running.', $workerId, $config['host'], $config['port']));
	}

	/**
	 * @param $server
	 * @param $settings
	 * @param \Snowflake\Application $application
	 * @param array $events
	 * @param array $config
	 * @return mixed|void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public static function set($server, $settings, $application, $events = [], $config = [])
	{
		$server->on('start', static::createHandler('start'));
		$server->on('workerStop', static::createHandler('workerStop'));
		$server->on('workerExit', static::createHandler('workerExit'));
		$server->on('workerStart', static::createHandler('workerStart'));
		$server->on('workerError', static::createHandler('workerError'));
		$server->on('managerStop', static::createHandler('managerStop'));
		$server->on('managerStart', static::createHandler('managerStart'));
		static::addListener($server, $application, $config);
		static::bindCallback($server, $events);
		static::addTask($server, $settings);
	}


	/**
	 * @param $server
	 * @param $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	protected static function addTask($server, $settings)
	{
		if (($taskNumber = $settings['task_worker_num'] ?? 0) > 0) {
			$server->on('finish', static::createHandler('finish'));
			$callback = static::createHandler('task');
			if ($settings['task_enable_coroutine'] ?? false) {
				$server->on('task', [$callback, 'onContinueTask']);
			} else {
				$server->on('task', [$callback, 'onTask']);
			}
		}
	}


	/**
	 * @param $server
	 * @param $application
	 * @param $config
	 * @return void
	 */
	protected static function addListener($server, $application, $config)
	{
		$grpc = $config['grpc'] ?? [];
		if (empty($grpc) || !is_array($grpc)) {
			return;
		}
		$listener = $server->addListener($grpc['host'], $grpc['port'], $grpc['mode']);
		$listener->set($grpc['settings'] ?? []);
		if (!isset($grpc['receive'])) {
			$application->error(sprintf('must add listener %s::%s callback', $grpc['host'], $grpc['port']));
			return;
		}
		if ($grpc['receive'] instanceof \Closure) {
			$grpc['receive'] = \Closure::bind($grpc['receive'], $server);
		}
		$listener->on('receive', $grpc['receive']);
	}


	/**
	 * @param $server
	 * @param $callbacks
	 */
	protected static function bindCallback($server, $callbacks)
	{
		if (empty($callbacks) || !is_array($callbacks)) {
			return;
		}
		foreach ($callbacks as $callback) {
			$server->on($callback[0], [$server, $callback[1][1]]);
		}
	}


	/**
	 * @param $eventName
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected static function createHandler($eventName)
	{
		$classPrefix = 'HttpServer\Events\Trigger\On' . ucfirst($eventName);
		if (!class_exists($classPrefix)) {
			throw new Exception('class not found.');
		}
		$class = Snowflake::createObject($classPrefix, [Snowflake::get()]);
		return [$class, 'onHandler'];
	}

}
