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

        error("\033[31m" . $lastError['message'] . "\033[0m" . $lastError['file'] . " at line " . $lastError['line'] . PHP_EOL);

        event(new Kiri\Events\OnSystemError());
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

        Kiri::getLogger()->error($exception, []);

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

        error("\033[31m" . $error[1] . "\033[0m" . $error[2] . " at line " . $error[3] . PHP_EOL);

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
        return "";
    }
}
