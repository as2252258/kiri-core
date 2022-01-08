<?php

namespace Kiri\Abstracts;

use DirectoryIterator;
use Exception;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Server\Events\OnWorkerStop;


/**
 *
 */
class Logger implements LoggerInterface
{

	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';


	private array $_loggers = [];


	const LOGGER_LEVELS = [Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE, Logger::INFO, Logger::DEBUG];


	/**
	 * @return void
	 * @throws ReflectionException
	 */
	public function init()
	{
		Kiri::getDi()->get(EventProvider::class)->on(OnWorkerStop::class, [$this, 'onAfterRequest']);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 *
	 * 紧急情况
	 */
	public function emergency($message, array $context = [])
	{
		// TODO: Implement emergency() method.
		$this->log(Logger::EMERGENCY, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 *
	 * 应该警惕的
	 */
	public function alert($message, array $context = [])
	{
		// TODO: Implement alert() method.
		$this->log(Logger::ALERT, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 *
	 * 关键性的日志
	 */
	public function critical($message, array $context = [])
	{
		// TODO: Implement critical() method.
		$this->log(Logger::CRITICAL, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function error($message, array $context = [])
	{
		// TODO: Implement error() method.
		$this->log(Logger::ERROR, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function warning($message, array $context = [])
	{
		// TODO: Implement warning() method.
		$this->log(Logger::WARNING, $message, $context);
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function notice($message, array $context = [])
	{
		// TODO: Implement notice() method.
		$this->log(Logger::NOTICE, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function info($message, array $context = [])
	{
		// TODO: Implement info() method.
		$this->log(Logger::INFO, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function debug($message, array $context = [])
	{
		// TODO: Implement debug() method.
		$this->log(Logger::DEBUG, $message, $context);
	}


	/**
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function log($level, $message, array $context = [])
	{
		// TODO: Implement log() method.
		$levels = Config::get('log.level', Logger::LOGGER_LEVELS);
		if (!in_array($level, $levels) || str_contains($message, 'Event::rshutdown')) {
			return;
		}

		$_string = '[' . now() . '] production.' . $level . ': ' . $this->_string($message, $context);

		file_put_contents('php://output', $_string);

		$this->_loggers[] = $_string;
	}


	/**
	 * @param OnWorkerStop $param
	 * @throws Exception
	 */
	public function onAfterRequest(OnWorkerStop $param)
	{
		$loggers = implode(PHP_EOL, $this->_loggers);
		$this->_loggers = [];
		if (!empty($loggers)) {
			$filename = storage('log-' . date('Y-m-d') . '.log', 'log/');

			file_put_contents($filename, $loggers);
		}
	}


	/**
	 * @return void
	 * @throws Exception
	 */
	public function flush()
	{
		$this->removeFile(storage());
	}


	/**
	 * @param string $dirname
	 * @return void
	 */
	private function removeFile(string $dirname)
	{
		$paths = new DirectoryIterator($dirname);
		/** @var DirectoryIterator $path */
		foreach ($paths as $path) {
			if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
				continue;
			}
			if ($path->isDir()) {
				$directory = rtrim($path->getRealPath(), '/');
				$this->removeFile($directory);
			}
			@unlink($path->getRealPath());
		}
	}


	/**
	 * @param $message
	 * @param $context
	 * @return string
	 */
	private function _string($message, $context): string
	{
		if (!empty($context)) {
			return $message . ' ' . PHP_EOL . print_r($context, TRUE) . PHP_EOL;
		}
		return $message . PHP_EOL;
	}
}
