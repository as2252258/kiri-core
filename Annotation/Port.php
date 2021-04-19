<?php


namespace Annotation;


use Exception;
use Snowflake\Snowflake;


/**
 * Class Port
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Port extends Attribute
{


	/**
	 * Port constructor.
	 * @param int $port
	 */
	public function __construct(public int $port)
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
		$router->addPortListen($this->port, $handler);
		return parent::execute($handler);
	}

}
