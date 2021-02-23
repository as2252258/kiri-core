<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:10
 */
declare(strict_types=1);

namespace Snowflake\Abstracts;

use Exception;

use JetBrains\PhpStorm\Pure;
use Snowflake\Error\Logger;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class BaseObject
 * @method defer()
 * @package Snowflake\Snowflake\Base
 * @method afterInit
 * @method initialization
 */
class BaseObject implements Configure
{

    /**
     * BaseAbstract constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (!empty($config) && is_array($config)) {
            Snowflake::configure($this, $config);
        }
        $this->init();
    }

    public function init()
    {

    }

    /**
     * @return string
     */
    #[Pure] public static function className(): string
    {
        return get_called_class();
    }

    /**
     * @param $name
     * @param $value
     *
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            $this->error('set ' . $name . ' not exists ' . get_called_class());
            throw new Exception('The set name ' . $name . ' not find in class ' . get_class($this));
        }
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws Exception
     */
    public function __get($name): mixed
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new Exception('The get name ' . $name . ' not find in class ' . get_class($this));
        }
    }


    /**
     * @param $message
     * @param string $model
     * @return bool
     * @throws Exception
     */
    public function addError($message, $model = 'app'): bool
    {
        if ($message instanceof \Throwable) {
            $format = 'Error: ' . $message->getMessage() . PHP_EOL;
            $format .= 'File: ' . $message->getFile() . PHP_EOL;
            $format .= 'Line: ' . $message->getLine();
            $this->error($format);
        } else {
            if (!is_string($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }
            $this->error($message);
        }
        $logger = Snowflake::app()->getLogger();
        $logger->error($message, $model);
        return FALSE;
    }


    /**
     * @param mixed $message
     * @param string $method
     * @param string $file
     * @throws ComponentException
     */
    public function debug(mixed $message, string $method = __METHOD__, string $file = __FILE__)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }
        $message = "\033[35m[DEBUG][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
        $message .= PHP_EOL;

        $socket = Snowflake::app()->getLogger();
        $socket->output($message);
    }


    /**
     * @param mixed $message
     * @param string $method
     * @param string $file
     * @throws ComponentException
     */
    public function info(mixed $message, string $method = __METHOD__, string $file = __FILE__)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }
        $message = "\033[34m[INFO][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
        $message .= PHP_EOL;

        $socket = Snowflake::app()->getLogger();
        $socket->output($message);
    }

    /**
     * @param mixed $message
     * @param string $method
     * @param string $file
     * @throws ComponentException
     */
    public function success(mixed $message, string $method = __METHOD__, string $file = __FILE__)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $message = "\033[36m[SUCCESS][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
        $message .= PHP_EOL;

        $socket = Snowflake::app()->getLogger();
        $socket->output($message);
    }


    /**
     * @param mixed $message
     * @param string $method
     * @param string $file
     * @throws ComponentException
     */
    public function warning(mixed $message, string $method = __METHOD__, string $file = __FILE__)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $message = "\033[33m[WARNING][" . date('Y-m-d H:i:s') . ']: ' . $message . "\033[0m";
        $message .= PHP_EOL;


        $socket = Snowflake::app()->getLogger();
        $socket->output($message);
    }


    /**
     * @param mixed $message
     * @param null $method
     * @param null $file
     * @throws ComponentException
     */
    public function error(mixed $message, $method = null, $file = null)
    {
        if (!empty($file)) {
            echo "\033[41;37m[ERROR][" . date('Y-m-d H:i:s') . ']: ' . $file . "\033[0m";
            echo PHP_EOL;
        }
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $content = (empty($method) ? '' : $method . ': ') . $message;

        $length = strlen('[ERROR][2021-02-20 08:32:02]:');

        $message = "\033[41;37m" . PHP_EOL . "[ERROR][" . date('Y-m-d H:i:s') . ']: ' . PHP_EOL . "\033[0m";
        $message .= "\033[41;37m" . str_pad($content, $length, ' ', STR_PAD_LEFT) . "\033[0m";
        $message .= PHP_EOL;

        $socket = Snowflake::app()->getLogger();
        $socket->output($message);
    }

}
