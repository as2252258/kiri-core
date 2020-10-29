<?php
declare(strict_types=1);

namespace Kafka;


use Psr\Log\LoggerInterface;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Logger
 * @package Kafka
 */
class Logger implements LoggerInterface
{


	/**
	 * @param string $message
	 * @param array $context
	 */
    public function emergency($message, array $context = array())
    {
        // TODO: Implement emergency() method.
        var_dump(func_get_args());
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function alert($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->debug($message);
    }

    public function critical($message, array $context = array())
    {
        // TODO: Implement critical() method.
        var_dump(func_get_args());
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function error($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->error($message);
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function warning($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->warning($message);
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function notice($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->info($message);
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function info($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->info($message);
    }

	/**
	 * @param string $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function debug($message, array $context = array())
    {
	    $logger = Snowflake::app()->getLogger();
	    $logger->debug($message);
    }

	/**
	 * @param $level
	 * @param $message
	 * @param array $context
	 * @throws ComponentException
	 */
    public function log($level, $message, array $context = array())
    {
        $logger = Snowflake::app()->getLogger();
        $logger->debug($message);
    }


}
