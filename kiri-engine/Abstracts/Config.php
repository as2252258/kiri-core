<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */
declare(strict_types=1);

namespace Kiri\Abstracts;

use Exception;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;


/**
 * Class Config
 * @package Kiri\Kiri\Base
 */
class Config extends Component
{

	const ERROR_MESSAGE = 'The not find %s in app configs.';

	protected static mixed $data = [];


	/**
	 * @return mixed
	 */
	public static function getData(): mixed
	{
		return static::$data;
	}


	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public static function setData($key, $value): mixed
	{
		return static::$data[$key] = $value;
	}


	/**
	 * @param array $configs
	 */
	public static function sets(array $configs)
	{
		if (empty($configs)) {
			return;
		}
		static::$data = $configs;
	}

	/**
	 * @param $key
	 * @param bool $try
	 * @param mixed|null $default
	 * @return mixed
	 * @throws ConfigException
	 */
	public static function get($key, mixed $default = null, bool $try = FALSE): mixed
	{
		$instance = static::$data;
		if (!str_contains($key, '.')) {
			return $instance[$key] ?? $default;
		}
		foreach (explode('.', $key) as $value) {
			if (empty($value)) {
				continue;
			}
			if (!isset($instance[$value])) {
				if ($try) {
					throw new ConfigException(sprintf(self::ERROR_MESSAGE, $key));
				}
				return $default;
			}
			if (!is_array($instance[$value])) {
				return $instance[$value];
			}
			$instance = $instance[$value];
		}
		return empty($instance) ? $default : $instance;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public static function set($key, $value): mixed
	{
		return static::setData($key, $value);
	}

	/**
	 * @param $key
	 * @param bool $must_not_null
	 * @return bool
	 */
	public static function has($key, bool $must_not_null = false): bool
	{
		if (!isset(static::$data[$key])) {
			return false;
		}
		$config = static::$data[$key];
		if ($must_not_null === false) {
			return true;
		}
		return !empty($config);
	}

}
