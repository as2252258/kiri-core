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

	protected mixed $data = [];


	/**
	 * @return mixed
	 */
	public function getData(): mixed
	{
		return $this->data;
	}


	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public function setData($key, $value): mixed
	{
		return $this->data[$key] = $value;
	}


	/**
	 * @param array $configs
	 * @throws Exception
	 */
	public static function sets(array $configs)
	{
		$config = Kiri::app()->getConfig();
		if (empty($configs)) {
			return;
		}
        $config->data = $configs;
	}

	/**
	 * @param $key
	 * @param bool $try
	 * @param mixed|null $default
	 * @return mixed
	 * @throws
	 */
	public static function get($key, mixed $default = null, bool $try = FALSE): mixed
	{
		$instance = Kiri::app()->getConfig()->getData();
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
	 * @throws Exception
	 */
	public static function set($key, $value): mixed
	{
		$config = Kiri::app()->getConfig();
		return $config->setData($key, $value);
	}

	/**
	 * @param $key
	 * @param bool $must_not_null
	 * @return bool
	 * @throws Exception
	 */
	public static function has($key, bool $must_not_null = false): bool
	{
		$config = Kiri::app()->getConfig();
		if (!isset($config->data[$key])) {
			return false;
		}
		$config = $config->data[$key];
		if ($must_not_null === false) {
			return true;
		}
		return !empty($config);
	}

}
