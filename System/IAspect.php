<?php


namespace Snowflake;


interface IAspect
{



    /**
     * @return mixed|void
     */
    public function invoke(mixed $handler);

}
