<?php

declare(strict_types=1);

namespace Kiri\Error;

use Kiri\Abstracts\BaseApplication;
use Kiri\Application;
use Kiri\Di\Inject\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionException;


/**
 * @see LoggerInterface
 */
class StdoutLogger
{


    /**
     * @var array
     */
    private array $errors = [];


    #[Container(Logger::class)]
    public Logger $logger;


    /**
     * @param $message
     * @param string $model
     * @return bool
     * @throws ReflectionException
     */
    public function failure($message, string $model = 'app'): bool
    {
        if ($message instanceof \Exception) {
            $this->errors[$model] = $message->getMessage();
        } else {
            $this->errors[$model] = $message;
        }
        $logger = \Kiri::getDi()->get(LoggerInterface::class);
        $logger->error(throwable($message), []);
        return false;
    }


    /**
     * @param string $name
     * @param array $arguments
     * @return void
     * @throws ReflectionException
     */
    public function __call(string $name, array $arguments)
    {
        // TODO: Implement __call() method.
        if (!isset($arguments[2])) {
            $arguments[2] = [];
        }
        [$level, $message, $context] = $arguments;

        $levels = \config('log.level', BaseApplication::LOGGER_LEVELS);
        if (!in_array($level, $levels)) {
            return;
        }
        if (!$this->logger->isHandling($level)) {
            $path = APP_PATH . 'storage/logs/' . strtolower(Logger::getLevelName($level)) . '/' . date('Y-m-d') . '.log';
            $this->logger->pushHandler(new StreamHandler($path, $level));
        }
        $this->logger->{$name}($level, $message, $context);
    }


    /**
     * @param string $model
     * @return mixed
     */
    public function getLastError(string $model = 'app'): mixed
    {
        return $this->errors[$model] ?? 'Unknown error.';
    }

}
