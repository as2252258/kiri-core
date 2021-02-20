<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 10:00
 */
declare(strict_types=1);

namespace Snowflake\Error;

use Exception;
use HttpServer\IInterface\IFormatter;
use Snowflake\Abstracts\Component;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class ErrorHandler
 *
 * @package Snowflake\Snowflake\Base
 * @property-read $asError
 */
class ErrorHandler extends Component implements ErrorInterface
{

	/** @var ?IFormatter $message */
	private ?IFormatter $message = NULL;

	public string $category = 'app';

	/**
	 * 错误处理注册
	 */
	public function register()
	{
		ini_set('display_errors', '0');
		set_exception_handler([$this, 'exceptionHandler']);
		if (defined('HHVM_VERSION')) {
			set_error_handler([$this, 'errorHandler']);
		} else {
			set_error_handler([$this, 'errorHandler']);
		}
		register_shutdown_function([$this, 'shutdown']);
	}

	/**
	 * @throws Exception
	 */
	public function shutdown()
	{
		$lastError = error_get_last();
		if (empty($lastError) || $lastError['type'] !== E_ERROR) {
			return;
		}

		$this->category = 'shutdown';

		$messages = explode(PHP_EOL, $lastError['message']);

		$message = array_shift($messages);

		$this->sendError($message, $lastError['file'], $lastError['line']);
	}


	/**
	 * @param \Throwable $exception
	 *
	 * @throws Exception
	 */
	public function exceptionHandler(\Throwable $exception)
	{
		$this->category = 'exception';

		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::RELEASE_ALL);

		$this->sendError($exception->getMessage(), $exception->getFile(), $exception->getLine());
	}

	/**
	 * @throws Exception
	 *
	 * 以异常形式抛出错误，防止执行后续程序
	 */
	public function errorHandler()
	{
		$error = func_get_args();
		if (str_contains($error[2], 'vendor/Reboot.php')) {
			return;
		}

		$path = ['file' => $error[2], 'line' => $error[3]];

		if ($error[0] === 0) {
			$error[0] = 500;
		}

		$data = Json::to(500, $error[1], $path);

		logger()->error($data, 'error');

		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::RELEASE_ALL);

		throw new \ErrorException($error[1], $error[0], 1, $error[2], $error[3]);
	}

	/**
	 * @param $message
	 * @param $file
	 * @param $line
	 * @param int $code
	 * @return false|string
	 * @throws Exception
	 */
	public function sendError($message, $file, $line, $code = 500): bool|string
	{
		$path = ['file' => $file, 'line' => $line];

		$data = Json::to($code, $this->category . ': ' . $message, $path);

		logger()->trance($data, $this->category);

		return response()->send($data);
	}

	/**
	 * @return mixed
	 */
	public function getErrorMessage(): mixed
	{
		$message = $this->message;
		$this->message = NULL;
		return $message->getData();
	}

	/**
	 * @return bool
	 */
	public function getAsError(): bool
	{
		return $this->message !== NULL;
	}

	/**
	 * @param $message
	 * @param $category
	 *
	 * @throws Exception
	 */
	public function writer($message, $category = 'app')
	{
		logger()->debug($message, $category);
	}
}
