<?php


namespace Snowflake\Error;


use JetBrains\PhpStorm\Pure;
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
    #[Pure] public function __construct(public array $handler)
    {
    }


	/**
	 * @return mixed
	 */
    public function invoke(): mixed
    {
        $startTime = microtime(true);

        $data = call_user_func($this->handler, func_get_args());

        $this->print_runtime($startTime);

        return $data;
    }


    private function print_runtime($startTime)
    {
        $className = get_class($this->handler[0]);
        $methodName = $this->handler[1];

        $runTime = round(microtime(true) - $startTime, 6);
        echo sprintf('run %s::%s use time %6f', $className, $methodName, $runTime);
        echo PHP_EOL;
    }


}
