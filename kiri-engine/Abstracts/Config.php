<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */
declare(strict_types=1);

namespace Kiri\Abstracts;

use Kiri\Exception\ConfigException;


/**
 * @param $key
 * @param $try
 * @return void
 * @throws ConfigException
 */
function ConfigTry($key, $try): void
{
	if ($try) {
		throw new ConfigException(sprintf(Config::ERROR_MESSAGE, $key));
	}
}


/**
 * Class Config
 * @package Kiri\Base
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
	 * @param mixed $default
	 * @return mixed
	 * @throws
	 */
	public static function get($key, mixed $default = null, bool $try = FALSE): mixed
	{
		if (!str_contains($key, '.')) {
			return static::$data[$key] ?? $default;
		}
		$array = explode('.', $key);
		$data = static::$data[array_shift($array)] ?? null;
		if ($data === null) {
			return $default;
		} else if (count($array) === 0) {
			return $data;
		} else if (!is_array($data)) {
			return $default;
		}
		foreach ($array as $value) {
			$data = $data[$value] ?? null;
			if ($data === null) {
				ConfigTry($key, $try);
				return $default;
			}
		}
		return $data;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public static function set($key, $value): mixed
	{
		$explode = explode('.', $key);
		$parent = &static::$data;
		foreach ($explode as $item) {
			if (!isset($parent[$item])) {
				$parent[$item] = [];
			}
			$parent = &$parent[$item];
		}
		$parent = $value;

		unset($parent);

		return static::$data;
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
