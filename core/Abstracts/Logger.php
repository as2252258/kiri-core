<?php

namespace Kiri\Abstracts;

use Annotation\Inject;
use Exception;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Psr\Log\LoggerInterface;
use Server\Events\OnAfterRequest;
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


	/**
	 * @var EventProvider
	 */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;

	private array $_loggers = [];


	const LOGGER_LEVELS = [Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE, Logger::INFO, Logger::DEBUG];


	/**
	 * 监听事件
	 */
	public function init()
	{
		$this->eventProvider->on(OnAfterRequest::class, [$this, 'onAfterRequest']);
		$this->eventProvider->on(OnWorkerStop::class, [$this, 'onAfterRequest']);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 *
	 * 紧急情况
	 */
	public function emergency($message, array $context = array())
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
	public function alert($message, array $context = array())
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
	public function critical($message, array $context = array())
	{
		// TODO: Implement critical() method.
		$this->log(Logger::CRITICAL, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function error($message, array $context = array())
	{
		// TODO: Implement error() method.
		$this->log(Logger::ERROR, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function warning($message, array $context = array())
	{
		// TODO: Implement warning() method.
		$this->log(Logger::WARNING, $message, $context);
	}

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function notice($message, array $context = array())
	{
		// TODO: Implement notice() method.
		$this->log(Logger::NOTICE, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function info($message, array $context = array())
	{
		// TODO: Implement info() method.
		$this->log(Logger::INFO, $message, $context);
	}


	/**
	 * @param string $message
	 * @param array $context
	 * @throws ConfigException
	 */
	public function debug($message, array $context = array())
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
	public function log($level, $message, array $context = array())
	{
		// TODO: Implement log() method.
		$levels = Config::get('log.level', Logger::LOGGER_LEVELS);
		if (!in_array($level, $levels)) {
			return;
		}

		$_string = '[' . now() . '] production.' . $level . ': ' . $this->_string($message, $context);

		echo $_string;

		$this->_loggers[] = $_string;
	}


	/**
	 * @param OnAfterRequest $afterRequest
	 * @throws Exception
	 */
	public function onAfterRequest(OnAfterRequest $afterRequest)
	{
		$loggers = implode(PHP_EOL, $this->_loggers);
		$this->_loggers = [];
		if (!empty($loggers)) {
			$filename = storage('log-' . date('Y-m-d') . '.log', 'logs/');

			file_put_contents($filename, $loggers);
		}
	}


	/**
	 * @param $message
	 * @param $context
	 * @return string
	 */
	private function _string($message, $context): string
	{
		return $message . ' ' . print_r($context, true) . PHP_EOL;
	}
}
