<?php


namespace Snowflake;


use Snowflake\Abstracts\BaseObject;
use Snowflake\Core\ArrayAccess;

/**
 * Class Event
 * @package Snowflake
 */
class Event extends BaseObject
{

	public $isVide = true;

	private $_events = [];

	const EVENT_ERROR = 'WORKER:ERROR';
	const EVENT_STOP = 'WORKER:STOP';
	const EVENT_EXIT = 'WORKER:EXIT';

	const EVENT_AFTER_REQUEST = 'SERVER:REQUEST:AFTER:START';
	const EVENT_BEFORE_REQUEST = 'SERVER:REQUEST:BEFORE:START';
	const RECEIVE_CONNECTION = 'SERVER:RECEIVE:CONNECTION';


	const PROCESS_WORKER_STOP = 'SERVER:PROCESS:WORKER:STOP';

	const RELEASE_ALL = 'SERVER:RELEASE:ALL';

	const SERVER_EVENT_START = 'SERVER:EVENT:START';
	const SERVER_MANAGER_START = 'SERVER:EVENT:MANAGER:START';
	const SERVER_MANAGER_STOP = 'SERVER:EVENT:MANAGER:START';
	const SERVER_WORKER_STOP = 'SERVER:EVENT:WORKER:STOP';
	const SERVER_WORKER_START = 'SERVER:EVENT:WORKER:START';
	const SERVER_WORKER_EXIT = 'SERVER:EVENT:WORKER:EXIT';
	const SERVER_WORKER_ERROR = 'SERVER:EVENT:WORKER:ERROR';

	const SERVER_HANDSHAKE = 'on handshake';
	const SERVER_MESSAGE = 'on message';
	const SERVER_CLOSE = 'on close';


	/**
	 * @param $name
	 * @param $callback
	 * @param array $parameter
	 * @param bool $isAppend
	 * @throws \Exception
	 */
	public function on($name, $callback, $parameter = [], $isAppend = true)
	{
		if (!isset($this->_events[$name])) {
			$this->_events[$name] = [];
		}
		if ($callback instanceof \Closure) {
			$callback = \Closure::bind($callback, Snowflake::get());
		} else if (is_array($callback) && is_string($callback[0])) {
			if (!class_exists($callback[0])) {
				throw new \Exception('Undefined callback class.');
			}
			$callback[0] = Snowflake::createObject($callback[0]);
		}
		if (!empty($this->_events[$name]) && $isAppend === true) {
			array_unshift($this->_events[$name], [$callback, $parameter]);
		} else {
			$this->_events[$name][] = [$callback, $parameter];
		}
	}


	/**
	 * @param $name
	 * @param $callback
	 */
	public function of($name, $callback)
	{
		if (!isset($this->_events[$name])) {
			return;
		}
		foreach ($this->_events[$name] as $index => $event) {
			[$handler, $parameter] = $event;
			if ($handler !== $callback) {
				continue;
			}
			unset($this->_events[$name][$index]);
		}
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function offName($name)
	{
		if (!$this->exists($name)) {
			return true;
		}
		unset($this->_events[$name]);
		return $this->exists($name);
	}


	/**
	 * @param $name
	 * @param null $callback
	 * @return bool
	 */
	public function exists($name, $callback = null)
	{
		if (!isset($this->_events[$name])) {
			return false;
		}
		if ($callback === null) {
			return true;
		}
		foreach ($this->_events[$name] as $event) {
			[$handler, $parameter] = $event;
			if ($handler === $callback) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @param $name
	 * @param $handler
	 * @return mixed|null
	 */
	public function get($name, $handler)
	{
		if (!$this->exists($name)) {
			return null;
		}
		foreach ($this->_events[$name] as $event) {
			[$callback, $parameter] = $event;
			if ($callback === $handler) {
				return $event;
			}
		}
		return null;
	}


	public function clean()
	{
		$this->_events = [];
	}


	/**
	 * @param $name
	 * @param null $handler
	 * @param null $parameter
	 * @param false $is_remove
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function trigger($name, $parameter = null, $handler = null, $is_remove = false)
	{
		if (!$this->exists($name)) {
			return false;
		}
		if (!empty($handler) && $this->exists($name, $handler)) {
			[$handler, $defaultParameter] = $this->get($name, $handler);
			if (!empty($parameter)) {
				$defaultParameter = ArrayAccess::merge($defaultParameter, $parameter);
			}
			if (!is_array($defaultParameter)) {
				$defaultParameter = [$defaultParameter];
			}
			$result = call_user_func($handler, ...$defaultParameter);
			if ($is_remove) {
				$this->of($name, $handler);
			}
			return $result;
		}
		foreach ($this->_events[$name] as $event) {
			[$handler, $defaultParameter] = $event;
			try {
				if (!empty($parameter)) {
					$defaultParameter = ArrayAccess::merge($defaultParameter, $parameter);
				}
				if (!is_array($defaultParameter)) {
					$defaultParameter = [$defaultParameter];
				}
				call_user_func($handler, ...$defaultParameter);
			} catch (\Throwable $exception) {
				$this->error($exception->getMessage());
			}
		}
		if ($is_remove) {
			$this->offName($name);
		}
		return true;
	}


}
