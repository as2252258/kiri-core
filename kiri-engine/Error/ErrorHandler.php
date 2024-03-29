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
use ErrorException;
use Exception;
use Kiri\Abstracts\Component;
use Psr\Container\ContainerInterface;
use Kiri\Di\Inject\Container;
use ReflectionException;
use Kiri\Events\OnSystemError;
use Throwable;

/**
 * Class ErrorHandler
 * hahahah
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
     * @var ContainerInterface
     */
    #[Container(ContainerInterface::class)]
    public ContainerInterface $container;


    /**
     * @param array|Closure|null $callback
     * @return void
     * @throws
     */
    public function registerExceptionHandler(null|array|Closure $callback): void
    {
        if (empty($callback)) {
            $callback = [$this, 'exceptionHandler'];
        } else if (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }
        set_exception_handler($callback);
    }


    /**
     * @param array|Closure|null $callback
     * @return void
     * @throws
     */
    public function registerErrorHandler(null|array|Closure $callback): void
    {
        if (empty($callback)) {
            $callback = [$this, 'errorHandler'];
        } else if (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }
        set_error_handler($callback);
    }


    /**
     * @param array|Closure|null $callback
     * @return void
     * @throws
     */
    public function registerShutdownHandler(null|array|Closure $callback): void
    {
        if (empty($callback)) {
            $callback = [$this, 'shutdown'];
        } else if (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }
        register_shutdown_function($callback);
    }


    /**
     * @return void
     * @throws
     * @throws
     */
    public function shutdown(): void
    {
        $lastError = error_get_last();
        if (empty($lastError) || $lastError['type'] !== E_ERROR) {
            return;
        }

        $this->getLogger()->failure($lastError['message'] . PHP_EOL);

        event(new OnSystemError());
    }


    /**
     * @param Throwable $exception
     *
     * @throws
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->category = 'exception';

        $this->getLogger()->failure($exception);

        event(new OnSystemError());
    }


    /**
     * @throws
     */
    public function errorHandler()
    {
        $error = func_get_args();

        event(new OnSystemError());

        throw new ErrorException($error[1], $error[0], 1, $error[2], $error[3]);
    }
}
