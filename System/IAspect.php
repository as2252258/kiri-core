<?php


namespace Snowflake;


interface IAspect
{


	/**
	 * @param mixed $handler
	 * @param array $params
	 * @return mixed
	 */
    public function invoke(mixed $handler, array $params = []): mixed;

}
