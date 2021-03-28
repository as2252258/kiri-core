<?php


namespace Snowflake\Error;


use Snowflake\IAspect;


/**
 * Class LoggerAspect
 * @package Snowflake\Error
 */
class LoggerAspect implements IAspect
{

    private string $className = '';
    private string $methodName = '';


    /**
     * LoggerAspect constructor.
     * @param array $handler
     */
    public function __construct(public array $handler, $needReturn)
    {
        $this->className = get_class($this->handler[0]);
        $this->methodName = $this->handler[1];
    }


    /**
     * @return mixed|void
     */
    public function invoke()
    {
        $startTime = microtime(true);

        $data = call_user_func($this->handler, func_get_args());

        $this->print_runtime($startTime);

        return $data;
    }


    private function print_runtime($startTime)
    {
        $runTime = round(microtime(true) - $startTime, 6);
        echo sprintf('run %s::%s use time %6f', $this->className, $this->methodName, $runTime);
        echo PHP_EOL;
    }


}
