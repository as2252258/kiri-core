<?php


namespace Snowflake\Pool;


use Snowflake\Snowflake;

/**
 * Class Pool
 * @package Snowflake\Pool
 * @property $redis
 * @property $db
 */
class Pool extends \Snowflake\Abstracts\Pool
{

	/**
	 * @return RedisClient
	 */
	public function getRedis()
	{
		return Snowflake::app()->redis_connections;
	}

	/**
	 * @return Connection
	 */
	public function getDb()
	{
		return Snowflake::app()->connections;
	}

}
