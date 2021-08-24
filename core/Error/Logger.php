<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */
declare(strict_types=1);

namespace Kiri\Error;

use Annotation\Inject;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Events\OnAfterRequest;
use Swoole\Coroutine;
use Throwable;

/**
 * Class Logger
 * @package Kiri\Kiri\Error
 */
class Logger extends Component
{

	private array $logs = [];


	/** @var EventProvider */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	private array $sources = [];


	/**
	 *
	 */
	public function init()
	{
		$this->eventProvider->on(OnAfterRequest::class, [$this, 'insert']);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param null $file
	 * @throws Exception
	 */
	public function debug(mixed $message, string $method = 'app', $file = null)
	{
		if (Config::get('environment', 'pro') == 'pro') {
			return;
		}
		$this->output($message);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @throws Exception
	 */
	public function trance(mixed $message, string $method = 'app')
	{
		if (Config::get('environment', 'pro') == 'pro') {
			return;
		}
		$this->output($message);
	}


	/**
	 * @param mixed $message
	 * @param string $method
	 * @param null $file
	 * @throws Exception
	 */
	public function error(mixed $message, $method = 'error', $file = null)
	{
		$this->writer($message, $method);
	}

	/**
	 * @param mixed $message
	 * @param string $method
	 * @param null $file
	 * @throws Exception
	 */
	public function success(mixed $message, string $method = 'app', $file = null)
	{
		if (Config::get('environment', 'pro') == 'pro') {
			return;
		}
		$this->output($message);
	}

	/**
	 * @param $message
	 * @param string $method
	 * @return void
	 * @throws Exception
	 */
	private function writer($message, string $method = 'app'): void
	{
		if (empty($message)) {
			return;
		}
		$message = print_r($message, true);
		$this->print_r($message, $method);
		if (!is_array($this->logs)) {
			$this->logs = [];
		}
		$this->logs[$method] = $message;
	}


	/**
	 * @param $message
	 * @param string $method
	 * @throws Exception
	 */
	public function print_r($message, string $method = '')
	{
		$debug = Config::get('debug', ['enable' => false]);
		if ((bool)$debug['enable'] === true) {
			if (!is_callable($debug['callback'] ?? null, true)) {
				return;
			}
			call_user_func($debug['callback'], $message, $method);
		}
	}


	/**
	 * @param $message
	 * @param string $method
	 * @throws ConfigException
	 */
	public function output($message, string $method = 'default')
	{
		if (Config::get('environment', 'dev') == 'pro') {
			if ($method === 'error') {
				echo $message;
			}
			return;
		}
		if (str_contains($message, 'Event::rshutdown(): Event::wait()')) {
			return;
		}
		echo $message;
	}


	/**
	 * @param string $application
	 * @return mixed
	 */
	public function getLastError(string $application = 'app'): mixed
	{
		return $this->logs[$application] ?? 'Unknown error.';
	}

	/**
	 * @param string $messages
	 * @param string $method
	 * @throws Exception
	 */
	public function write(string $messages, string $method = 'app')
	{
		if (empty($messages)) {
			return;
		}

		$to_day = date('Y-m-d');

		$fileName = storage('server-' . $to_day . '.log', $dirName = 'log/' . ($method ?? 'app'));

		file_put_contents($fileName, '[' . date('Y-m-d H:i:s') . ']:' . PHP_EOL . $messages . PHP_EOL);
	}


	/**
	 * @param $logFile
	 * @return string
	 */
	private function getSource($logFile): string
	{
		if (!file_exists($logFile)) {
			Coroutine\System::exec('echo 3 > /proc/sys/vm/drop_caches');
			touch($logFile);
		}
		if (is_writeable($logFile)) {
			$logFile = realpath($logFile);
		}
		return $logFile;
	}


	/**
	 * @throws Exception
	 * 写入日志
	 */
	public function insert()
	{
		if (empty($this->logs)) {
			return;
		}
		foreach ($this->logs as $method => $message) {
			$this->write($message, $method);
		}
		$this->logs = [];
	}

	/**
	 * @return array
	 */
	public function clear(): array
	{
		return $this->logs = [];
	}

	/**
	 * @param $data
	 * @return string
	 */
	private function arrayFormat($data): string
	{
		if (is_string($data)) {
			return $data;
		}
		if ($data instanceof Throwable) {
			$data = $this->getException($data);
		} else if (is_object($data)) {
			$data = get_object_vars($data);
		}
		return Json::encode($data);
	}


	/**
	 * @param Throwable $exception
	 * @return mixed
	 * @throws Exception
	 */
	public function exception(Throwable $exception): mixed
	{
		$code = $exception->getCode() == 0 ? 500 : $exception->getCode();

		$logger = Kiri::app()->getLogger();
		$logger->write(jTraceEx($exception), 'exception');

		return Json::to($code, $exception->getMessage(), [
			'file' => $exception->getFile(),
			'line' => $exception->getLine()
		]);
	}


	/**
	 * @param Throwable $exception
	 * @return array
	 */
	private function getException(Throwable $exception): array
	{
		$filetype = [$exception->getMessage()];
		$filetype[] = $exception->getFile() . ' on line ' . $exception->getLine();
		$filetype[] = $exception->getTrace();
		return $filetype;
	}

}