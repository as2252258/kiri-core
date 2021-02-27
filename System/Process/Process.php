<?php
declare(strict_types=1);


namespace Snowflake\Process;


use Exception;
use Snowflake\Application;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class Process
 * @package Snowflake\Snowflake\Service
 */
abstract class Process extends \Swoole\Process implements SProcess
{

    /** @var Application $application */
    protected Application $application;


    /**
     * Process constructor.
     * @param $application
     * @param $name
     * @param bool $enable_coroutine
     * @throws Exception
     */
    public function __construct($application, $name, $enable_coroutine = true)
    {
        parent::__construct([$this, '_load'], true, 1, $enable_coroutine);
        $this->application = $application;
        Snowflake::setWorkerId($this->pid);
    }

    /**
     * @param Process $process
     * @throws \Snowflake\Exception\ComponentException
     */
    private function _load(Process $process)
    {
        putenv('environmental=' . Snowflake::PROCESS);

        fire(Event::SERVER_WORKER_START);
        if (Snowflake::isLinux()) {
            $this->name($this->getPrefix());
        }
        $this->onHandler($process);
    }


    /**
     * @return string
     */
    private function getPrefix(): string
    {
        return ucfirst(rtrim(Snowflake::app()->id, ':') . ': ' . get_called_class());
    }


}
