<?php


namespace HttpServer\Http;

use HttpServer\Abstracts\BaseContext;
use Swoole\Coroutine;

/**
 * Class Context
 * @package Yoc\http
 */
class Context extends BaseContext
{

	protected static $_requests = [];

	protected static $_response = [];


	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return mixed
	 */
	public static function setContext($id, $context, $key = null)
	{
		if (static::inCoroutine()) {
			return self::setCoroutine($id, $context, $key);
		} else {
			return self::setStatic($id, $context, $key);
		}
	}

	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return mixed
	 */
	private static function setStatic($id, $context, $key = null)
	{
		if (!empty($key)) {
			if (!is_array(static::$_requests[$id])) {
				static::$_requests[$id] = [$key => $context];
			} else {
				static::$_requests[$id][$key] = $context;
			}
		} else {
			static::$_requests[$id] = $context;
		}
		return $context;
	}

	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return
	 */
	private static function setCoroutine($id, $context, $key = null)
	{
		if (!static::hasContext($id)) {
			Coroutine::getContext()[$id] = [];
		}
		if (empty($key)) {
			return Coroutine::getContext()[$id] = $context;
		}
		if (!is_array(Coroutine::getContext()[$id])) {
			Coroutine::getContext()[$id] = [$key => $context];
		} else {
			Coroutine::getContext()[$id][$key] = $context;
		}
		return $context;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return false|mixed
	 */
	public static function autoIncr($id, $key = null)
	{
		if (!static::inCoroutine()) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] += 1;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return false|mixed
	 */
	public static function autoDecr($id, $key = null)
	{
		if (!static::inCoroutine()) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] -= 1;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	public static function getContext($id, $key = null)
	{
		if (static::inCoroutine()) {
			$array = Coroutine::getContext()[$id] ?? null;
		} else {
			$array = static::$_requests[$id] ?? null;
		}
		if (empty($key) || !is_array($array)) {
			return $array;
		}
		return $array[$key];
	}

	/**
	 * @return mixed
	 */
	public static function getAllContext()
	{
		if (static::inCoroutine()) {
			return Coroutine::getContext() ?? [];
		} else {
			return static::$_requests ?? [];
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 */
	public static function deleteId($id, $key = null)
	{
		if (!static::hasContext($id, $key)) {
			return;
		}
		if (static::inCoroutine()) {
			if (!empty($key)) {
				Coroutine::getContext()[$id][$key] = null;
			} else {
				Coroutine::getContext()[$id] = null;
			}
		} else {
			unset(static::$_requests[$id]);
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	public static function hasContext($id, $key = null)
	{
		if (static::inCoroutine()) {
			if (!isset(Coroutine::getContext()[$id])) {
				return false;
			}
			$data = Coroutine::getContext()[$id];
		} else {
			if (!isset(static::$_requests[$id])) {
				return false;
			}
			$data = static::$_requests[$id];
		}
		if (empty($data)) {
			return false;
		}
		if (empty($key)) {
			return true;
		} else if (!is_array($data)) {
			return false;
		}
		return isset($data[$key]);
	}


	/**
	 * @return bool
	 */
	public static function inCoroutine()
	{
		return Coroutine::getCid() > 0;
	}

}



