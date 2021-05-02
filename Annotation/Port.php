<?php


namespace Annotation;


use Exception;
use Snowflake\Snowflake;


/**
 * Class Port
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)] class Port extends Attribute
{


	/**
	 * Port constructor.
	 * @param int $port
	 * @param int $mode
	 */
	public function __construct(public int $port, public int $mode = SWOOLE_SOCK_TCP)
	{
	}


	/**
	 * @param array $handler
	 * @return mixed
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = null): mixed
	{
		$router = Snowflake::app()->getRouter();
		if (!($class instanceof Porters)) {
			return true;
		}
		$router->addPortListen($this->port, [$class, 'process']);
		return true;
	}

}
