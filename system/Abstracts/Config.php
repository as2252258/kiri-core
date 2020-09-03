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
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param $key
	 * @param bool $try
	 * @param mixed $default
	 * @return mixed
	 * @throws
	 */
	public static function get($key, $try = FALSE, $default = null)
	{
		$explode = explode('.', $key);

		$instance = Snowflake::app()->getConfig()->getData();
		foreach ($explode as $index => $value) {
			if (!isset($instance[$value]) && $try) {
				throw new ConfigException(str_replace(':key', $key, self::ERROR_MESSAGE));
			}
			$instance = $instance[$value];
			if (!is_array($instance) && $index + 1 < count($explode)) {
				throw new ConfigException(str_replace(':key', $key, self::ERROR_MESSAGE));
			}
		}
		if (empty($instance)) {
			return $default;
		}
		return $instance;
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
