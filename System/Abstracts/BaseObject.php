<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:10
 */
declare(strict_types=1);
namespace Snowflake\Abstracts;

use Exception;
use Snowflake\Error\Logger;
use Snowflake\Snowflake;

/**
 * Class BaseObject
 * @method defer()
 * @package Snowflake\Snowflake\Base
 * @method afterInit
 * @method initialization
 */
class BaseObject implements Configure
{

	/**
	 * BaseAbstract constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		if (!empty($config) && is_array($config)) {
			Snowflake::configure($this, $config);
		}
		$this->init();
	}

	public function init()
	{

	}

	/**
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		$method = 'set' . ucfirst($name);
		if (method_exists($this, $method)) {
			$this->{$method}($value);
		} else {
			$this->error('set ' . $name . ' not exists ' . get_called_class());
			throw new Exception('The set name ' . $name . ' not find in class ' . get_class($this));
		}
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();
		} else {
			throw new Exception('The get name ' . $name . ' not find in class ' . get_class($this));
		}
	}


	/**
	 * @param $message
	 * @param string $model
	 * @return bool
	 * @throws Exception
	 */
	public function addError($message, $model = 'app')
	{
		if ($message instanceof Exception) {
			$this->error($message->getMessage(), $message->getFile(), $message->getLine());
		} else {
			if (!is_string($message)) {
				$message = json_encode($message, JSON_UNESCAPED_UNICODE);
			}
			$this->error($message);
		}
		return FALSE;
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws
	 */
	public function debug($message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		echo "\033[35m[DEBUG][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
		echo PHP_EOL;
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws
	 */
	public function info($message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		echo "\033[34m[INFO][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
		echo PHP_EOL;
	}

	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws
	 */
	public function success($message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		echo "\033[36m[SUCCESS][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
		echo PHP_EOL;
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 */
	public function warning($message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		echo "\033[33m[WARNING][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
		echo PHP_EOL;
	}


	/**
	 * @param mixed $message
	 * @param null $method
	 * @param null $file
	 */
	public function error($message, $method = null, $file = null)
	{
		if (!empty($file)) {
			echo "\033[41;37m[ERROR][" . date('Y-m-d H:i:s') . ']: ' . $file . "\033[0m";
			echo PHP_EOL;
		}
		var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		echo "\033[41;37m[ERROR][" . date('Y-m-d H:i:s') . ']: ' . (empty($method) ? '' : $method . ': ') . $message . "\033[0m";
		echo PHP_EOL;
	}

}
