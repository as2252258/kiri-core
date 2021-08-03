<?php
declare(strict_types=1);


namespace Snowflake;


use Exception;
use Snowflake\Abstracts\BaseObject;
use Swoole\Coroutine;

/**
 * Class Event
 * @package Snowflake
 */
class Event extends BaseObject
{

	public bool $isVide = true;

	private static array $_events = [];


	const PIPE_MESSAGE = 'SERVER:PIPE:MESSAGE';
	const TASK_FINISH = 'SERVER:TASK::FINISH';

	const EVENT_AFTER_REQUEST = 'SERVER:REQUEST:AFTER:START';
	const EVENT_BEFORE_REQUEST = 'SERVER:REQUEST:BEFORE:START';
	const RECEIVE_CONNECTION = 'SERVER:RECEIVE:CONNECTION';


	const SYSTEM_RESOURCE_RELEASES = 'SYSTEM::RESOURCE::RELEASES';
	const SYSTEM_RESOURCE_CLEAN = 'SYSTEM::RESOURCE::CLEAN';


	const PROCESS_WORKER_STOP = 'SERVER:PROCESS:WORKER:STOP';

	const SERVER_AFTER_RELOAD = 'SERVER:AFTER:RELOAD';
	const SERVER_BEFORE_RELOAD = 'SERVER:BEFORE:RELOAD';
	const SERVER_CONNECT = 'SERVER:CONNECT';
	const SERVER_PACKAGE = 'SERVER:PACKAGE';
	const SERVER_RECEIVE = 'SERVER:RECEIVE';

	const SERVER_EVENT_START = 'SERVER:EVENT:START';
	const SERVER_MANAGER_START = 'SERVER:EVENT:MANAGER:START';
	const SERVER_MANAGER_STOP = 'SERVER:EVENT:MANAGER:START';
	const SERVER_WORKER_STOP = 'SERVER:EVENT:WORKER:STOP';
	const SERVER_WORKER_START = 'SERVER:EVENT:WORKER:START';
	const SERVER_AFTER_WORKER_START = 'SERVER:EVENT:AFTER:WORKER:START';
	const SERVER_BEFORE_START = 'SERVER:EVENT:BEFORE:START';
	const BEFORE_COMMAND_EXECUTE = 'COMMAND:EVENT:BEFORE:EXECUTE';
	const AFTER_COMMAND_EXECUTE = 'COMMAND:EVENT:AFTER:EXECUTE';
	const SERVER_TASK_START = 'SERVER:EVENT:TASK:START';
	const SERVER_WORKER_EXIT = 'SERVER:EVENT:WORKER:EXIT';
	const SERVER_WORKER_ERROR = 'SERVER:EVENT:WORKER:ERROR';
	const SERVER_SHUTDOWN = 'SERVER:EVENT:SHUTDOWN';

	const SERVER_HANDSHAKE = 'on handshake';
	const SERVER_MESSAGE = 'on message';
	const SERVER_CLIENT_CLOSE = 'SERVER:CLIENT:CLOSE';

	const SERVER_ON_START = 'Start';
	const SERVER_ON_SHUTDOWN = 'Shutdown';
	const SERVER_ON_WORKER_START = 'WorkerStart';
	const SERVER_ON_WORKER_STOP = 'WorkerStop';
	const SERVER_ON_WORKER_EXIT = 'WorkerExit';
	const SERVER_ON_CONNECT = 'Connect';
	const SERVER_ON_RECEIVE = 'Receive';
	const SERVER_ON_PACKET = 'Packet';
	const SERVER_ON_REQUEST = 'request';
	const SERVER_ON_CLOSE = 'Close';
	const SERVER_ON_TASK = 'Task';
	const SERVER_ON_FINISH = 'Finish';
	const SERVER_ON_PIPE_MESSAGE = 'PipeMessage';
	const SERVER_ON_WORKER_ERROR = 'WorkerError';
	const SERVER_ON_MANAGER_START = 'ManagerStart';
	const SERVER_ON_MANAGER_STOP = 'ManagerStop';
	const SERVER_ON_BEFORE_RELOAD = 'BeforeReload';
	const SERVER_ON_AFTER_RELOAD = 'AfterReload';


	/**
	 * @param $name
	 * @param $callback
	 * @param bool $isAppend
	 * @throws Exception
	 */
	public static function on($name, $callback, bool $isAppend = false)
	{
		if (!isset(static::$_events[$name])) {
			static::$_events[$name] = [];
		}
		if (is_array($callback) && is_string($callback[0])) {
			if (!class_exists($callback[0])) {
				throw new Exception('Undefined callback class.');
			}
			$callback[0] = di($callback[0]);
		}
		if (static::exists($name, $callback)) {
			return;
		}
		if (!empty(static::$_events[$name]) && $isAppend === true) {
			array_unshift(static::$_events[$name], [$callback]);
		} else {
			static::$_events[$name][] = [$callback];
		}
	}


	/**
	 * @param $name
	 * @param $callback
	 */
	public static function of($name, $callback): void
	{
		if (!isset(static::$_events[$name])) {
			return;
		}
		foreach (static::$_events[$name] as $index => $event) {
			[$handler] = $event;
			if ($handler !== $callback) {
				continue;
			}
			unset(static::$_events[$name][$index]);
		}
	}


	/**
	 * @param $name
	 */
	public static function offName($name): void
	{
		unset(static::$_events[$name]);
	}


	/**
	 * @param $name
	 * @param null $callback
	 * @return bool
	 */
	public static function exists($name, $callback): bool
	{
		if ($callback instanceof \Closure || !isset(static::$_events[$name])) {
			return false;
		}
		foreach (static::$_events[$name] as $event) {
			[$handler] = $event;
			if ($handler === $callback) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @param $name
	 * @param $handler
	 * @return mixed
	 */
	public static function get($name, $handler): mixed
	{
		if (!static::exists($name, $handler)) {
			return null;
		}
		if (empty($handler)) {
			return static::$_events[$name];
		}
		foreach (static::$_events[$name] as $event) {
			[$callback] = $event;
			if ($callback === $handler) {
				return [$event];
			}
		}
		return null;
	}


	public static function clean()
	{
		static::$_events = [];
	}


	/**
	 * @param $name
	 * @param array $params
	 * @return bool
	 * @throws Exception
	 */
	public function dispatch($name, array $params = []): bool
	{
		return static::trigger($name, $params);
	}


	/**
	 * @param $name
	 * @param null $parameter
	 * @param false $is_remove
	 * @return bool
	 * @throws Exception
	 */
	public static function trigger($name, $parameter = null, bool $is_remove = false): bool
	{
		foreach ((static::$_events[$name] ?? []) as $key => $event) {
			static::execute($event, $parameter);
			if ($event instanceof \Closure) {
				unset(static::$_events[$name][$key]);
			}
		}
		if ($is_remove) {
			unset(static::$_events[$name]);
		}
		return true;
	}


	/**
	 * @param $event
	 * @param $parameter
	 * @return void
	 * @throws Exception
	 */
	private static function execute($event, $parameter): void
	{
		try {
			call_user_func($event[0], ...$parameter);
		} catch (\Throwable $throwable) {
			logger()->addError($throwable, 'throwable');
			return;
		}
	}



}
