<?php


namespace Snowflake;


class Proxy
{

	/**
	 * Proxy constructor.
	 * @param IProxy $IProxy
	 */
	public function __construct(public IProxy $IProxy)
	{
	}


	/**
	 * @return mixed
	 */
	public function execute(): mixed
	{
		return $this->IProxy->execute();
	}

}
