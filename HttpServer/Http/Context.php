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
	 * @return mixed
	 */
	public static function setContext($id, $context): mixed
	{
		if (Coroutine::getCid() === -1) {
			return static::$_contents[$id] = $context;
		}
		return Coroutine::getContext()[$id] = $context;
	}

	/**
	 * @param $id
	 * @param int $value
	 * @return bool|int
	 */
	public static function increment($id, int $value = 1): bool|int
	{
		if (!isset(Coroutine::getContext()[$id])) {
			return Coroutine::getContext()[$id] += $value;
		}
		return false;
	}

	/**
	 * @param $id
	 * @param int $value
	 * @return bool|int
	 */
	public static function decrement($id, int $value = 1): bool|int
	{
		if (!static::hasContext($id)) {
			return false;
		}
		if (isset(Coroutine::getContext()[$id])) {
			return Coroutine::getContext()[$id] -= $value;
		}
		return false;
	}

	/**
	 * @param $id
	 * @param null $default
	 * @return mixed
	 */
	public static function getContext($id, $default = null): mixed
	{
		if (Coroutine::getCid() === -1) {
			return static::loadByStatic($id, $default);
		}
		return static::loadByContext($id, $default);
	}


	/**
	 * @param $id
	 * @param null $default
	 * @return mixed
	 */
	private static function loadByContext($id, $default = null): mixed
	{
		$data = Coroutine::getContext()[$id] ?? null;
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
	 * @return mixed
	 */
	public static function getAllContext(): mixed
	{
		if (Coroutine::getCid() === -1) {
			return Coroutine::getContext() ?? [];
		} else {
			return static::$_contents ?? [];
		}
	}

	/**
	 * @param string $id
	 */
	public static function remove(string $id)
	{
		if (!static::hasContext($id)) {
			return;
		}
		if (Coroutine::getCid() === -1) {
			unset(static::$_contents[$id]);
		} else {
			unset(Coroutine::getContext()[$id]);
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return bool
	 */
	public static function hasContext($id, $key = null): bool
	{
		if (Coroutine::getCid() === -1) {
			return static::searchByStatic($id, $key);
		}
		return static::searchByCoroutine($id, $key);
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
	 * @return bool
	 */
	private static function searchByCoroutine($id, $key = null): bool
	{
		if (!isset(Coroutine::getContext()[$id])) {
			return false;
		}
		if ($key !== null) {
			return isset((Coroutine::getContext()[$id] ?? [])[$key]);
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



