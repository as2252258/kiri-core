<?php


namespace Snowflake;


interface IAspect
{


    /**
     * IAspect constructor.
     * @param array $handler
     */
    public function __construct(array $handler);


    /**
     * @return mixed|void
     */
    public function invoke();

}
