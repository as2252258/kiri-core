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

	protected static array $_contents = [];


	/**
	 * @param $id
	 * @param $context
	 * @param null $coroutineId
	 * @return mixed
	 */
	public static function setContext($id, $context, $coroutineId = null): mixed
	{
		if (Coroutine::getCid() === -1) {
			return static::$_contents[$id] = $context;
		}
		return Coroutine::getContext($coroutineId)[$id] = $context;
	}

	/**
	 * @param $id
	 * @param int $value
	 * @param null $coroutineId
	 * @return bool|int
	 */
	public static function increment($id, int $value = 1, $coroutineId = null): bool|int
	{
		if (!isset(Coroutine::getContext($coroutineId)[$id])) {
			Coroutine::getContext($coroutineId)[$id] = 0;
		}
		return Coroutine::getContext($coroutineId)[$id] += $value;
	}

	/**
	 * @param $id
	 * @param int $value
	 * @param null $coroutineId
	 * @return bool|int
	 */
	public static function decrement($id, int $value = 1, $coroutineId = null): bool|int
	{
		if (!isset(Coroutine::getContext($coroutineId)[$id])) {
			Coroutine::getContext($coroutineId)[$id] = 0;
		}
		return Coroutine::getContext($coroutineId)[$id] -= $value;
	}

	/**
	 * @param $id
	 * @param null $default
	 * @param null $coroutineId
	 * @return mixed
	 */
	public static function getContext($id, $default = null, $coroutineId = null): mixed
	{
		if (Coroutine::getCid() === -1) {
			return static::loadByStatic($id, $default);
		}
		return static::loadByContext($id, $default, $coroutineId);
	}


	/**
	 * @param $id
	 * @param null $default
	 * @param null $coroutineId
	 * @return mixed
	 */
	private static function loadByContext($id, $default = null, $coroutineId = null): mixed
	{
		$data = Coroutine::getContext($coroutineId)[$id] ?? null;
		if ($data === null) {
			return $default;
		}
		return $data;
	}


	/**
	 * @param $id
	 * @param null $default
	 * @return mixed
	 */
	private static function loadByStatic($id, $default = null): mixed
	{
		$data = static::$_contents[$id] ?? null;
		if ($data === null) {
			return $default;
		}
		return $data;
	}


	/**
	 * @param null $coroutineId
	 * @return mixed
	 */
	public static function getAllContext($coroutineId = null): mixed
	{
		if (Coroutine::getCid() === -1) {
			return Coroutine::getContext($coroutineId) ?? [];
		} else {
			return static::$_contents ?? [];
		}
	}

	/**
	 * @param string $id
	 * @param null $coroutineId
	 */
	public static function remove(string $id, $coroutineId = null)
	{
		if (!static::hasContext($id, $coroutineId)) {
			return;
		}
		if (Coroutine::getCid() === -1) {
			unset(static::$_contents[$id]);
		} else {
			unset(Coroutine::getContext($coroutineId)[$id]);
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return bool
	 */
	public static function hasContext($id, $key = null, $coroutineId = null): bool
	{
		if (Coroutine::getCid() === -1) {
			return static::searchByStatic($id, $key);
		}
		return static::searchByCoroutine($id, $key, $coroutineId);
	}


	/**
	 * @param $id
	 * @param null $key
	 * @return bool
	 */
	private static function searchByStatic($id, $key = null): bool
	{
		if (!isset(static::$_contents[$id])) {
			return false;
		}
		if (!empty($key) && !isset(static::$_contents[$id][$key])) {
			return false;
		}
		return true;
	}


	/**
	 * @param $id
	 * @param null $key
	 * @param null $coroutineId
	 * @return bool
	 */
	private static function searchByCoroutine($id, $key = null, $coroutineId = null): bool
	{
		if (!isset(Coroutine::getContext($coroutineId)[$id])) {
			return false;
		}
		if ($key !== null) {
			return isset((Coroutine::getContext($coroutineId)[$id] ?? [])[$key]);
		}
		return true;
	}


	/**
	 * @return bool
	 */
	public static function inCoroutine(): bool
	{
		return Coroutine::getCid() !== -1;
	}

}



