<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 16:35
 */

namespace Snowflake\Cache;

/**
 * Interface ICache
 * @package BeReborn\Cache
 */
interface ICache
{
	/**
	 * @param $key
	 * @param $val
	 * @return mixed
	 */
	public function set($key, $val);

	/**
	 * @param $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * @param $key
	 * @param $hashKeys
	 * @return mixed
	 */
	public function hMget($key, array $hashKeys);

	/**
	 * @param $key
	 * @param array $val
	 * @return mixed
	 */
	public function hMset($key, array $val);

	/**
	 * @param $key
	 * @param $hashKey
	 * @return mixed
	 */
	public function hget($key, $hashKey);

	/**
	 * @param $key
	 * @param $hashKey
	 * @param $hashValue
	 * @return mixed
	 */
	public function hset($key, $hashKey, $hashValue);

	/**
	 * @param $key
	 * @return bool
	 */
	public function exists($key);
}
