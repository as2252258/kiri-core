<?php

declare(strict_types=1);

namespace Kiri\Error;

use Kiri\Abstracts\Component;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionException;


/**
 * @see LoggerInterface
 * @method error(string $message, array $context)
 * @method log($level, $message, array $context = array())
 * @method debug($message, array $context = array())
 * @method info($message, array $context = array())
 * @method notice($message, array $context = array())
 * @method warning($message, array $context = array())
 * @method critical($message, array $context = array())
 * @method alert($message, array $context = array())
 * @method emergency($message, array $context = array())
 */
class StdoutLogger extends Component
{


    /**
     * @var array
     */
    private array $errors = [];


    /**
     * @var Logger
     */
    protected Logger $logger;

    protected array $levels;


    /**
     * StdoutLogger construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->logger = new Logger(\config('id'));
        $this->levels = [
            'debug'     => $this->logger::DEBUG,
            'info'      => $this->logger::INFO,
            'notice'    => $this->logger::NOTICE,
            'warning'   => $this->logger::WARNING,
            'error'     => $this->logger::ERROR,
            'critical'  => $this->logger::CRITICAL,
            'alert'     => $this->logger::ALERT,
            'emergency' => $this->logger::EMERGENCY,
        ];
    }


    /**
     * @param $message
     * @param string $model
     * @return bool
     */
    public function failure($message, string $model = 'app'): bool
    {
        if ($message instanceof \Exception) {
            $this->errors[$model] = $message->getMessage();
        } else {
            $this->errors[$model] = $message;
        }
        file_put_contents('php"//output', '[' . date('Y-m-d H:i:s') . '] ' . throwable($message) . PHP_EOL, FILE_APPEND);
        $this->error(throwable($message), []);
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
        try {
            if (method_exists($this->logger, $name)) {
                $this->createHandler($name)->$name(...$arguments);
            } else if (method_exists($this, $name)) {
                $this->{$name}(...$arguments);
            }
        } catch (\Throwable $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }
    }


    /**
     * @param string $name
     * @return Logger
     */
    protected function createHandler(string $name): Logger
    {
        if (!$this->logger->isHandling($this->levels[$name])) {
            $this->logger->pushHandler(new RotatingFileHandler(APP_PATH . 'storage/logs/' . $name . '/' . date('Y-m-d') . '.log', $this->levels[$name]));
        }
        return $this->logger;
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
