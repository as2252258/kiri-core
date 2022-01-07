<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */
declare(strict_types=1);

namespace Kiri\Error;

use Exception;
use Kiri\Abstracts\Component;
use Kiri\Core\Json;
use Kiri\Kiri;
use Note\Inject;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class Logger
 * @package Kiri\Kiri\Error
 * @mixin \Kiri\Abstracts\Logger
 */
class Logger extends Component
{

	private array $logs = [];


	/**
	 * inject logger
	 *
	 * @var LoggerInterface
	 */
	#[Inject(LoggerInterface::class)]
	public LoggerInterface $logger;


	private array $sources = [];


	/**
	 * @param string $application
	 * @return string
	 */
	public function getLastError(string $application = 'app'): string
	{
		return $this->logs[$application] ?? 'Unknown error.';
	}


	/**
	 * @param $message
	 * @param $method
	 * @return void
	 */
	public function fail($message, $method)
	{
		$this->logs[$method] = $message;
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
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call(string $name, array $arguments): mixed
	{
		if (!method_exists($this, $name)) {
			return $this->logger->{$name}(...$arguments);
		} else {
			return $this->{$name}(...$arguments);
		}
	}


}
