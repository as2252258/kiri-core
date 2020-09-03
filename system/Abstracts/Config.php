<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */

namespace Snowflake;

use Exception;
use Snowflake\Exception\ConfigException;
use Snowflake\Abstracts\Component;


/**
 * Class Config
 * @package Snowflake\Snowflake\Base
 */
class Config extends Component
{

	const ERROR_MESSAGE = 'The not find :key in app configs.';

	public $data;

	/**
	 * @param $key
	 * @param bool $try
	 * @param mixed $default
	 * @return mixed
	 * @throws
	 */
	public static function get($key, $try = FALSE, $default = null)
	{
		$config = Snowflake::app()->config;

		if (strpos($key, '.') === false) {
			if (isset($config->data[$key])) {
				return $config->data[$key];
			} else if ($default !== null) {
				return $default;
			} else if ($try) {
				throw new ConfigException(str_replace(':key', $key, self::ERROR_MESSAGE));
			}
			return NULL;
		}
		$keys = explode('.', $key);

		$parent = $config->data[array_shift($keys)] ?? null;
		if (empty($parent)) {
			return $default;
		}
		if (empty($keys) || !is_array($parent)) {
			return $parent;
		}
		foreach ($keys as $key) {
			if (!isset($parent[$key])) {
				return $parent;
			}
			$parent = $parent[$key];
			if (empty($keys) || !is_array($parent)) {
				return $parent;
			}
		}
		return $parent;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 * @throws Exception
	 */
	public static function set($key, $value)
	{
		$config = Snowflake::app()->config;
		return $config->data[$key] = $value;
	}

	/**
	 * @param $key
	 * @param bool $must_not_null
	 * @return bool
	 */
	public static function has($key, $must_not_null = false)
	{
		$config = Snowflake::app()->config;
		if (!isset($config->data[$key])) {
			return false;
		}
		$config = $config->data[$key];
		if ($must_not_null === false) {
			return true;
		}
		return !empty($config);
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
}
