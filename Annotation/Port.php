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
	public function execute(array $handler): mixed
	{
		$router = Snowflake::app()->getRouter();
		if (!($handler[0] instanceof Porters)) {
			return true;
		}
		$router->addPortListen($this->port, [$handler[0], 'process']);
		return parent::execute($handler);
	}

}
