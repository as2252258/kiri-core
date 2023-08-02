<?php

namespace Kiri\Abstracts;

use DirectoryIterator;
use Exception;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri;
use Kiri\Server\Events\OnWorkerStop;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @method static _emergency(string $message, array $context = [])
 * @method static _alert(string $message, array $context = [])
 * @method static _critical(string $message, array $context = [])
 * @method static _error(string $message, array $context = [])
 * @method static _warning(string $message, array $context = [])
 * @method static _notice(string $message, array $context = [])
 * @method static _info(string $message, array $context = [])
 * @method static _debug(string $message, array $context = [])
 * @method static _log(mixed $level, string $message, array $context = [])
 * @method static _flush()
 */
class Logger implements LoggerInterface
{

    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';


    const LOGGER_LEVELS = [Logger::EMERGENCY, Logger::ALERT, Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE, Logger::INFO, Logger::DEBUG];


    private array $levels = [];


    /**
     *
     */
    public function __construct()
    {
        $this->levels = \config('log.level', Logger::LOGGER_LEVELS);
    }


    /**
     * @param string $message
     * @param array $context
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
     *
     * 应该警惕的
     */
    public function alert($message, array $context = [])
    {
        // TODO: Implement alert() method.
        $this->log(Logger::ALERT, $message, $context);
    }


    /**
     * @param string $name
     * @param array $arguments
     * @return void
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        // TODO: Implement __callStatic() method.
        $name = str_replace('_', '', $name);
        Kiri::getLogger()->{$name}(...$arguments);
    }


    /**
     * @param string $message
     * @param array $context
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
     */
    public function error($message, array $context = [])
    {
        // TODO: Implement error() method.
        $this->log(Logger::ERROR, $message, $context);
    }


    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        // TODO: Implement warning() method.
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        // TODO: Implement notice() method.
        $this->log(Logger::NOTICE, $message, $context);
    }


    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        // TODO: Implement info() method.
        $this->log(Logger::INFO, $message, $context);
    }


    /**
     * @param string $message
     * @param array $context
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
     * @throws
     */
    public function log($level, $message, array $context = []): void
    {
        if (!in_array($level, $this->levels)) return;
        $context = $this->_string($context);
        $_string = "[" . now() . ']: ' . $message . PHP_EOL . $context;
        if (str_contains($_string, 'Event::rshutdown')) {
            return;
        }

        file_put_contents('php://output', $message . PHP_EOL);

        $this->write($level, $message);
    }


    /**
     * @param string $level
     * @param string $message
     * @return void
     * @throws Exception
     */
    public function write(string $level, string $message): void
    {
        $filename = storage('/log-' . date('Y-m-d') . '.log', 'log/' . $level . '/');

        $file = fopen($filename, 'a');
        fwrite($file, $message);
        fclose($file);
    }


    /**
     * @return void
     * @throws Exception
     */
    public function flush(): void
    {
        $this->removeFile(storage());
    }


    /**
     * @param string $dirname
     * @return void
     */
    private function removeFile(string $dirname): void
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
     * @param $context
     * @return string
     */
    private function _string($context): string
    {
        var_dump($context);
        if (is_string($context)) {
            return $context . PHP_EOL;
        }
        if ($context instanceof \Throwable) {
            return 'file -> ' . $context->getFile() . PHP_EOL . 'line -> ' . $context->getLine() . PHP_EOL;
        }
        if (!is_array($context)) {
            return "unknown";
        }

        $data = [];
        foreach ($context as $value) {
            $data[] = $this->_string($value);
        }
        return implode(PHP_EOL, $data);
    }
}
