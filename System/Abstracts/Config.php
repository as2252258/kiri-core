<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:50
 */

namespace Snowflake\Abstracts;

use Exception;
use Snowflake\Exception\ConfigException;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * Class Config
 * @package Snowflake\Snowflake\Base
 */
class Config extends Component
{

	const ERROR_MESSAGE = 'The not find %s in app configs.';

	protected $data;


	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}


	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public function setData($key, $value)
	{
		return $this->data[$key] = $value;
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
		$instance = Snowflake::app()->config->getData();
		foreach ($explode as $index => $value) {
			if (empty($value)) {
				continue;
			}
			if (!isset($instance[$value])) {
				if ($try) {
					throw new ConfigException(sprintf(self::ERROR_MESSAGE, $key));
				}
				return $default;
			}
			$instance = $instance[$value];
			if (!is_array($instance) && $index + 1 < count($explode)) {
				throw new ConfigException(sprintf(self::ERROR_MESSAGE, $key));
			}
		}
		return empty($instance) ? $default : $instance;
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
		return $config->setData($key, $value);
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
