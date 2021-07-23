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
use Snowflake\Error\Logger;
use Snowflake\Snowflake;
use Swoole\Coroutine;

/**
 * Class BaseObject
 * @package Snowflake\Snowflake\Base
 * @method afterInit
 */
class BaseObject implements Configure
{

	/**
	 * BaseAbstract constructor.
	 *
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(array $config = [])
	{
		if (!empty($config) && is_array($config)) {
			Snowflake::configure($this, $config);
		}
		$this->init();
	}


	/**
	 * @throws Exception
	 */
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
		return static::class;
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
			$this->error('set ' . $name . ' not exists ' . static::class);
			throw new Exception('The set name ' . $name . ' not find in class ' . static::class);
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
			throw new Exception('The get name ' . $name . ' not find in class ' . static::class);
		}
	}


	/**
	 * @param $message
	 * @param string $model
	 * @return bool
	 * @throws Exception
	 */
	public function addError($message, string $model = 'app'): bool
	{
		if ($message instanceof \Throwable) {
			$Throwable = $message;
			$this->error(jTraceEx($message));

			$message = 'Error: ' . $Throwable->getMessage() . PHP_EOL;
			$message .= 'File: ' . $Throwable->getFile() . PHP_EOL;
			$message .= 'Line: ' . $Throwable->getLine();
		} else {
			if (!is_string($message)) {
				$message = json_encode($message, JSON_UNESCAPED_UNICODE);
			}
			$this->error($message);
		}
		$this->logger()->error($message, $model);
		return FALSE;
	}


	/**
	 * @return Logger
	 * @throws Exception
	 */
	private function logger(): Logger
	{
		return Snowflake::app()->getLogger();
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

		$this->logger()->output($message);
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

		$this->logger()->output($message);
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

		$this->logger()->output($message);
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

		$this->logger()->output($message);
	}


	/**
	 * @param mixed $message
	 * @param null $method
	 * @param null $file
	 * @throws Exception
	 */
	public function error(mixed $message, $method = null, $file = null)
	{
		if ($message instanceof \Throwable) {
			$message = $message->getMessage() . " on line " . $message->getLine() . " at file " . $message->getFile();
		}
		$content = (empty($method) ? '' : $method . ': ') . $message;

		$message = "\033[41;37m[" . date('Y-m-d H:i:s') . '][ERROR]: ' . $content . "\033[0m";

		if (!empty($file)) {
			$message .= PHP_EOL . "\033[41;37m[" . date('Y-m-d H:i:s') . '][ERROR]: ' . $file . "\033[0m";
		}
		$this->logger()->output($message . PHP_EOL);
	}

}
