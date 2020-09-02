<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:28
 */

namespace Snowflake\Abstracts;


use Exception;
use Snowflake\Snowflake;

/**
 * Class Component
 * @package Snowflake\Snowflake\Base
 */
class Component extends BaseObject
{

	/**
	 * @var array
	 */
	private $_events = [];


	/**
	 * @param $name [事件名称]
	 * @param $callback [回调函数]
	 * @param array $param [函数参数]
	 *
	 * {
	 *      事件名, 回调, 参数
	 * }
	 */
	public function on($name, $callback, $param = [])
	{
		if (isset($this->_events[$name])) {
			array_push($this->_events[$name], [$callback, $param]);
		} else {
			$this->_events[$name][] = [$callback, $param];
		}
	}

	/**
	 * @param $name
	 * @param null $callback
	 * @return bool
	 */
	public function hasEvent($name, $callback = null)
	{
		if (!isset($this->_events[$name])) {
			return false;
		}
		if (!is_array($this->_events[$name])) {
			return false;
		}
		foreach ($this->_events[$name] as $event) {
			[$_callback, $param] = $event;
			if ($_callback === $callback) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $name
	 * @param null $event
	 * @param array $params
	 * @param bool $isRemove
	 * @throws Exception
	 */
	public function trigger($name, $event = null, $params = [], $isRemove = false)
	{
		$aEvents = Snowflake::get()->event;
		if (isset($this->_events[$name])) {
			$events = $this->_events[$name];
			foreach ($events as $key => $_event) {
				if (!empty($event)) {
					$_event = $event;
				}
				call_user_func($_event, ...$params);
				if ($isRemove) {
					unset($this->_events[$name][$key]);
					$aEvents->of($name, $_event);
				}
			}
		}
		$aEvents->trigger($name, $event);
	}

	/**
	 * @param $name
	 * @param null $handler
	 * @return mixed
	 */
	public function off($name, $handler = NULL)
	{
		$aEvents = Snowflake::get()->event;
		if (!isset($this->_events[$name])) {
			return $aEvents->of($name, $handler);
		}

		if (empty($handler)) {
			unset($this->_events[$name]);

			return $aEvents->of($name, $handler);
		}

		foreach ($this->_events[$name] as $key => $val) {
			if ($val[0] != $handler) {
				continue;
			}
			unset($this->_events[$name][$key]);

			break;
		}
		return $aEvents->of($name, $handler);
	}

	/**
	 */
	public function offAll()
	{
		$this->_events = [];
		$aEvents = Snowflake::get()->event;
		$aEvents->clean();
	}


	/**
	 * @param $name
	 * @param $value
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		if (property_exists($this, $name)) {
			$this->$name = $value;
		} else {
			parent::__set($name, $value);
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->$name ?? null;
		} else {
			return parent::__get($name);
		}
	}
}
