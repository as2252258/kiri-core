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
	 * @param null $key
	 * @return mixed
	 */
	public static function setContext($id, $context, $key = null): mixed
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
	 * @return array
	 */
	private static function setStatic($id, $context, $key = null): array
	{
		if (empty($key)) {
			return static::$_contents[$id] = $context;
		}
		if (!is_array(static::$_contents[$id])) {
			static::$_contents[$id] = [$key => $context];
		} else {
			static::$_contents[$id][$key] = $context;
		}
		return $context;
	}

	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return mixed
	 */
	private static function setCoroutine($id, $context, $key = null): mixed
	{
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
	 * @param int $value
	 * @return bool|int
	 */
	public static function increment($id, $key = null, $value = 1): bool|int
	{
		if (!static::inCoroutine()) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] += $value;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @param int $value
	 * @return bool|int
	 */
	public static function decrement($id, $key = null, $value = 1): bool|int
	{
		if (!static::inCoroutine() || !static::hasContext($id)) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] -= $value;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	public static function getContext($id, $key = null): mixed
	{
		if (!static::hasContext($id)) {
			return null;
		}
		if (static::inCoroutine()) {
			return static::loadByContext($id, $key);
		} else {
			return static::loadByStatic($id, $key);
		}
	}


	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	private static function loadByContext($id, $key = null): mixed
	{
		$data = Coroutine::getContext()[$id] ?? null;
		if ($data === null) {
			return null;
		}
		if ($key !== null) {
			return $data[$key] ?? null;
		}
		return $data;
	}


	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	private static function loadByStatic($id, $key = null): mixed
	{
		$data = static::$_contents[$id] ?? null;
		if ($data === null) {
			return null;
		}
		if ($key !== null) {
			return $data[$key] ?? null;
		}
		return $data;
	}


	/**
	 * @return mixed
	 */
	public static function getAllContext(): mixed
	{
		if (static::inCoroutine()) {
			return Coroutine::getContext() ?? [];
		} else {
			return static::$_contents ?? [];
		}
	}

	/**
	 * @param string $id
	 * @param string|null $key
	 */
	public static function remove(string $id, string $key = null)
	{
		if (!static::hasContext($id, $key)) {
			return;
		}
		if (static::inCoroutine()) {
			unset(static::$_contents[$id]);
			return;
		}
		if (!empty($key)) {
			unset(Coroutine::getContext()[$id][$key]);
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
		if (!static::inCoroutine()) {
			return static::searchByStatic($id, $key);
		} else {
			return static::searchByCoroutine($id, $key);
		}
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
		return Coroutine::getCid() > 0;
	}

}



