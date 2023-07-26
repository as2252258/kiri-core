<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/26 0026
 * Time: 10:00
 */
declare(strict_types=1);

namespace Kiri\Error;

use Closure;
use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Core\Json;
use Kiri\Events\EventDispatch;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Kiri\Di\Inject\Container;

/**
 * Class ErrorHandler
 *
 * @package Kiri\Base
 * @property-read $asError
 */
class ErrorHandler extends Component implements ErrorInterface
{
	
	/**
	 * @var string
	 */
	public string $category = 'app';


	/**
	 * @param array|Closure|null $callback
	 * @return void
	 * @throws ReflectionException
	 */
	public function registerExceptionHandler(null|array|Closure $callback): void
	{
		if (empty($callback)) {
			$callback = [$this, 'exceptionHandler'];
		} else if (is_array($callback) && is_string($callback[0])) {
			$callback[0] = Kiri::getDi()->get($callback[0]);
		}
		set_exception_handler($callback);
	}


	/**
	 * @param array|Closure|null $callback
	 * @return void
	 * @throws ReflectionException
	 */
	public function registerErrorHandler(null|array|Closure $callback): void
	{
		if (empty($callback)) {
			$callback = [$this, 'errorHandler'];
		} else if (is_array($callback) && is_string($callback[0])) {
			$callback[0] = Kiri::getDi()->get($callback[0]);
		}
		set_error_handler($callback);
	}


	/**
	 * @param array|Closure|null $callback
	 * @return void
	 * @throws ReflectionException
	 */
	public function registerShutdownHandler(null|array|Closure $callback): void
	{
		if (empty($callback)) {
			$callback = [$this, 'shutdown'];
		} else if (is_array($callback) && is_string($callback[0])) {
			$callback[0] = Kiri::getDi()->get($callback[0]);
		}
		register_shutdown_function($callback);
	}
	
	
	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function shutdown(): void
	{
		$lastError = error_get_last();
		if (empty($lastError) || $lastError['type'] !== E_ERROR) {
			return;
		}
		
		$this->category = 'shutdown';
		
		$messages = explode(PHP_EOL, $lastError['message']);
		
		$message = array_shift($messages);
		
		event(new Kiri\Events\OnSystemError());
		
		$this->sendError($message, $lastError['file'], $lastError['line']);
	}
	
	
	/**
	 * @param \Throwable $exception
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	public function exceptionHandler(\Throwable $exception): void
	{
		$this->category = 'exception';

		event(new Kiri\Events\OnSystemError());
		
		$this->sendError($exception->getMessage(), $exception->getFile(), $exception->getLine());
	}
	
	
	/**
	 * @throws \ErrorException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function errorHandler()
	{
		$error = func_get_args();
		
		$path = ['file' => $error[2], 'line' => $error[3]];
		
		if ($error[0] === 0) {
			$error[0] = 500;
		}
		
		$data = Json::jsonFail($error[1], 500, $path);

        if (!empty($data)) {
            error($data, []);
        }
		event(new Kiri\Events\OnSystemError());
		
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
	public function sendError($message, $file, $line, int $code = 500): bool|string
	{
		$path = ['file' => $file, 'line' => $line];
		
		$data = Json::jsonFail($this->category . ': ' . $message, $code, $path);
		
		file_put_contents('php://output', $data . PHP_EOL, FILE_APPEND);
		
		return $data;
	}

	
	/**
	 * @param $message
	 * @param string $category
	 *
	 * @throws Exception
	 */
	public function writer($message, string $category = 'app')
	{
		Kiri::getLogger()->debug($category, [$message]);
	}
}
