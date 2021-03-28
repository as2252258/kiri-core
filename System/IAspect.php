<?php


namespace Snowflake;


interface IAspect
{


    /**
     * IAspect constructor.
     * @param array $handler
     */
    public function __construct(array $handler, bool $needRetruen);


    /**
     * @return mixed|void
     */
    public function invoke();

}
