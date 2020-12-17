<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 16:35
 */
declare(strict_types=1);

namespace Snowflake\Cache;

/**
 * Interface ICache
 * @package Snowflake\Snowflake\Cache
 */
interface ICache
{
	/**
	 * @param $key
	 * @param $val
	 * @return string|int
	 */
	public function set($key, $val): string|int;

	/**
	 * @param $key
	 * @return string|int|bool
	 */
	public function get($key): string|int|bool;

	/**
	 * @param $key
	 * @param array $hashKeys
	 * @return array|bool|null
	 */
	public function hMGet($key, array $hashKeys): array|bool|null;

	/**
	 * @param $key
	 * @param array $val
	 * @return mixed
	 */
	public function hMSet($key, array $val): mixed;

	/**
	 * @param string $key
	 * @param string $hashKey
	 * @return string|int|bool
	 */
	public function hGet(string $key, string $hashKey): string|int|bool;

	/**
	 * @param $key
	 * @param $hashKey
	 * @param $hashValue
	 * @return mixed
	 */
	public function hSet($key, $hashKey, $hashValue): mixed;

	/**
	 * @param $key
	 * @return bool
	 */
	public function exists($key): bool;
}
