<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/2 0002
 * Time: 14:51
 */
declare(strict_types=1);

namespace Snowflake\Cache;


use Exception;

use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;
use Swoole\Coroutine\System;

/**
 * Class File
 * @package Snowflake\Snowflake\Cache
 */
class File extends Component implements ICache
{
	public string $path;

	/**
	 * @param $key
	 * @param $val
	 * @return string|int
	 */
	public function set($key, $val): string|int
	{
		if (is_array($val) || is_object($val)) {
			$val = swoole_serialize($val);
		}
		$tmpFile = $this->getCacheKey($key);
		if (!$this->exists($tmpFile)) {
			touch($tmpFile);
		}
		return System::writeFile($tmpFile, $val, LOCK_EX);
	}

	/**
	 * @param $key
	 * @param array $hashKeys
	 * @return array|bool
	 */
	public function hMGet($key, array $hashKeys): array|bool
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$nowHash = [];
		foreach ($hashKeys as $hashKey) {
			$nowHash[$hashKey] = $hash[$hashKey] ?? null;
		}
		return $nowHash;
	}

	/**
	 * @param $key
	 * @param array $val
	 * @return bool|int|string
	 */
	public function hMSet($key, array $val): bool|int|string
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$merge = array_merge($hash, $val);
		return $this->set($key, $merge);
	}

	/**
	 * @param string $key
	 * @param string $hashKey
	 * @return string|int|bool
	 */
	public function hGet(string $key, string $hashKey): string|int|bool
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}
		return $hash[$hashKey] ?? false;
	}

	/**
	 * @param $key
	 * @param $hashKey
	 * @param $hashValue
	 * @return bool|int|string
	 */
	public function hSet($key, $hashKey, $hashValue): bool|int|string
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$hash[$hashKey] = $hashValue;

		return $this->set($key, $hash);
	}

	/**
	 * @param $key
	 * @return bool
	 */
	#[Pure] public function exists($key): bool
	{
		return file_exists($key);
	}

	/**
	 * @param $key
	 * @return mixed|bool
	 */
	public function get($key): string|bool
	{
		$tmpFile = $this->getCacheKey($key);
		if (!$this->exists($tmpFile)) {
			return false;
		}
		$content = file_get_contents($tmpFile);
		return swoole_unserialize($content);
	}

	/**
	 * @param $key
	 * @return string
	 * @throws
	 */
	private function getCacheKey($key): string
	{
		return storage($key, 'cache');
	}
}
