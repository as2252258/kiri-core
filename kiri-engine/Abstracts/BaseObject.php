<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:10
 */
declare(strict_types=1);

namespace Kiri\Abstracts;

use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Di\Container;
use Kiri\Kiri;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * Class BaseObject
 * @package Kiri\Kiri\Base
 * @property ContainerInterface $container
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
			Kiri::configure($this, $config);
		}
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
	 * @return Container
	 */
	#[Pure] public function getContainer(): ContainerInterface
	{
		return Kiri::getDi();
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
			$this->error(jTraceEx($message));
		} else {
			if (!is_string($message)) {
				$message = json_encode($message, JSON_UNESCAPED_UNICODE);
			}
			$this->error($message);
		}
		return FALSE;
	}


	/**
	 * @return Logger
	 * @throws Exception
	 */
	private function logger(): Logger
	{
		return Kiri::getDi()->get(Logger::class);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function debug(mixed $message, string $method = '', string $file = '')
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$message = "\033[35m" . $message . "\033[0m";


		$context = [];
		if (!empty($method)) $context['method'] = $method;
		if (!empty($file)) $context['file'] = $file;

		$this->logger()->debug($message, $context);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function info(mixed $message, string $method = '', string $file = '')
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$message = "\033[34m" . $message . "\033[0m";


		$context = [];
		if (!empty($method)) $context['method'] = $method;
		if (!empty($file)) $context['file'] = $file;

		$this->logger()->info($message, $context);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function success(mixed $message, string $method = '', string $file = '')
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		$message = "\033[36m" . $message . "\033[0m";


		$context = [];
		if (!empty($method)) $context['method'] = $method;
		if (!empty($file)) $context['file'] = $file;

		$this->logger()->notice($message, $context);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param string $file
	 * @throws Exception
	 */
	public function warning(mixed $message, string $method = '', string $file = '')
	{
		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		$message = "\033[33m" . $message . "\033[0m";


		$context = [];
		if (!empty($method)) $context['method'] = $method;
		if (!empty($file)) $context['file'] = $file;

		$this->logger()->critical($message, $context);
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

		$message = "\033[41;37m" . $content . "\033[0m";

		if (!empty($file)) {
			$message .= PHP_EOL . "\03341;37m[" . $file . "\033[0m";
		}

		$context = [];
		if (!empty($method)) $context['method'] = $method;
		if (!empty($file)) $context['file'] = $file;

		$this->logger()->error($message, $context);
	}

}
