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

use JetBrains\PhpStorm\Pure;
use Snowflake\Snowflake;
use Swoole\Coroutine;

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
	 * @param array|callable $callback
	 * @param object $scope
	 */
	public function async_create(array|callable $callback, object $scope)
	{
		Coroutine::create($callback, $scope);
	}


	/**
	 * @return string
	 */
	#[Pure] public static function className(): string
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
	public function __get($name): mixed
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
	public function addError($message, $model = 'app'): bool
	{
		if ($message instanceof \Throwable) {
			$format = 'Error: ' . $message->getMessage() . PHP_EOL;
			$format .= 'File: ' . $message->getFile() . PHP_EOL;
			$format .= 'Line: ' . $message->getLine();
			$this->error($format);
		} else {
			if (!is_string($message)) {
				$message = json_encode($message, JSON_UNESCAPED_UNICODE);
			}
			$this->error($message);
		}
		$logger = Snowflake::app()->getLogger();
		$logger->error($message, $model);
		return FALSE;
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function debug(mixed $message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$message = "\033[35m[" . date('Y-m-d H:i:s') . '][DEBUG]: ' . $message . "\033[0m";
		$message .= PHP_EOL;

		$socket = Snowflake::app()->getLogger();
		$socket->output($message);
	}


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function __call(string $name, array $arguments): mixed
	{
		var_dump(get_called_class() . '::' . $name);
		if (!Snowflake::app()->has('aop')) {
			return call_user_func([$this, $name], $arguments);
		}
		return \aop([$this, $name], $arguments);
	}


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public static function __callStatic(string $name, array $arguments): mixed
	{
		if (!Snowflake::app()->has('aop')) {
			return call_user_func([get_called_class(), $name], $arguments);
		}
		return \aop([get_called_class(), $name], $arguments);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function info(mixed $message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$message = "\033[34m[" . date('Y-m-d H:i:s') . '][INFO]: ' . $message . "\033[0m";
		$message .= PHP_EOL;

		$socket = Snowflake::app()->getLogger();
		$socket->output($message);
	}

	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function success(mixed $message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		$message = "\033[36m[" . date('Y-m-d H:i:s') . '][SUCCESS]: ' . $message . "\033[0m";
		$message .= PHP_EOL;

		$socket = Snowflake::app()->getLogger();
		$socket->output($message);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function warning(mixed $message, string $method = __METHOD__, string $file = __FILE__)
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		$message = "\033[33m[" . date('Y-m-d H:i:s') . '][WARNING]: ' . $message . "\033[0m";
		$message .= PHP_EOL;


		$socket = Snowflake::app()->getLogger();
		$socket->output($message);
	}


	/**
	 * @param mixed $message
	 * @param null $method
	 * @param null $file
	 * @throws Exception
	 */
	public function error(mixed $message, $method = null, $file = null)
	{
		$socket = Snowflake::app()->getLogger();
		if ($message instanceof \Throwable) {
			$message = $message->getMessage() . " on line " . $message->getLine() . " at file " . $message->getFile();
		}
		$content = (empty($method) ? '' : $method . ': ') . $message;

		$message = "\033[41;37m" . PHP_EOL . "[" . date('Y-m-d H:i:s') . '][ERROR]: ' . $content . PHP_EOL . "\033[0m";

		if (!empty($file)) {
			$message .= "\033[41;37m[" . date('Y-m-d H:i:s') . '][ERROR]: ' . $file . "\033[0m";
		}
		$socket->output($message . PHP_EOL);
	}

}
