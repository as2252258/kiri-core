<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-22
 * Time: 14:36
 */
declare(strict_types=1);

namespace Snowflake\Error;

use Exception;
use HttpServer\Http\Context;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Process;
use Throwable;

/**
 * Class Logger
 * @package Snowflake\Snowflake\Error
 */
class Logger extends Component
{

    private array $logs = [];


    public function init()
    {
        Event::on(Event::SYSTEM_RESOURCE_CLEAN, [$this, 'insert']);
        Event::on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'insert']);
    }


    /**
     * @param $message
     * @param string $method
     * @param null $file
     * @throws Exception
     */
    public function debug(mixed $message, string $method = 'app', $file = null)
    {
        $this->writer($message, $method);
    }


    /**
     * @param $message
     * @param string $method
     * @throws Exception
     */
    public function trance($message, $method = 'app')
    {
        $this->writer($message, $method);
    }


    /**
     * @param $message
     * @param string $method
     * @param null $file
     * @throws Exception
     */
    public function error(mixed $message, $method = 'error', $file = null)
    {
        $this->writer($message, $method);
    }

    /**
     * @param $message
     * @param string $method
     * @param null $file
     * @throws Exception
     */
    public function success(mixed $message, $method = 'app', $file = null)
    {
        $this->writer($message, $method);
    }

    /**
     * @param $message
     * @param string $method
     * @return string
     * @throws Exception
     */
    private function writer($message, $method = 'app'): string
    {
        $this->print_r($message, $method);
        if ($message instanceof Throwable) {
            $message = $message->getMessage();
        } else {
            if (is_array($message) || is_object($message)) {
                $message = $this->arrayFormat($message);
            }
        }
        if (is_array($message)) {
            $message = $this->arrayFormat($message);
        }
        if (!empty($message)) {
            if (!is_array($this->logs)) {
                $this->logs = [];
            }
            $this->logs[] = [$method, $message];
        }
        return $message;
    }


    /**
     * @param $message
     * @param $method
     * @throws Exception
     */
    public function print_r($message, $method = '')
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
     */
    public function output($message)
    {
        if (str_contains($message, 'Event::rshutdown(): Event::wait()')) {
            return;
        }
        echo $message;
    }


    /**
     * @param string $application
     * @return mixed
     */
    public function getLastError($application = 'app'): mixed
    {
        $filetype = [];
        foreach ($this->logs as $key => $val) {
            if ($val[0] != $application) {
                continue;
            }
            $filetype[] = $val[1];
        }
        if (empty($filetype)) {
            return 'Unknown error.';
        }
        return end($filetype);
    }

    /**
     * @param $messages
     * @param string $method
     * @throws
     */
    public function write(string $messages, $method = 'app')
    {
        if (empty($messages)) {
            return;
        }

        $fileName = 'server-' . date('Y-m-d') . '.log';
        $dirName = 'log/' . (empty($method) ? 'app' : $method);
        $logFile = '[' . date('Y-m-d H:i:s') . ']:' . PHP_EOL . $messages . PHP_EOL;
        Snowflake::writeFile(storage($fileName, $dirName), $logFile, FILE_APPEND);

        $files = glob(storage(null, $dirName) . '/*');
        if (count($files) >= 15) {
            $command = 'find ' . storage(null, $dirName) . '/ -mtime +15 -name "*.log" -exec rm -rf {} \;';
            if (Context::inCoroutine()) {
                Coroutine\System::exec($command);
            } else {
                \shell_exec($command);
            }
        }
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
        foreach ($this->logs as $log) {
            [$method, $message] = $log;
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

        $filetype = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $filetype[] = $this->arrayFormat($val);
            } else {
                $filetype[] = (is_string($key) ? $key . ' : ' : '') . $val;
            }
        }
        return implode(PHP_EOL, $filetype);
    }


    /**
     * @param Throwable $exception
     * @return mixed
     * @throws Exception
     */
    public function exception(Throwable $exception): mixed
    {
        $errorInfo = [
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine()
        ];
        $this->error(var_export($errorInfo, true));

        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();

        $logger = Snowflake::app()->getLogger();

        $string = 'Exception: ' . PHP_EOL;
        $string .= '#.  message: ' . $errorInfo['message'] . PHP_EOL;
        $string .= '#.  file: ' . $errorInfo['file'] . PHP_EOL;
        $string .= '#.  line: ' . $errorInfo['line'] . PHP_EOL;

        $logger->write($string . $exception->getTraceAsString(), 'trace');
        $logger->write(jTraceEx($exception), 'exception');

        return Json::to($code, $errorInfo['message'], [
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
