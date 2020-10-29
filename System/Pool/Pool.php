<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Snowflake\Cache\Memcached;
use Snowflake\Snowflake;

/**
 * Class Pool
 * @package Snowflake\Pool
 * @property $redis
 * @property $db
 * @property $memcached
 */
class Pool extends \Snowflake\Abstracts\Pool
{

	/**
	 * @return Redis
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


	/**
	 * @return Memcached
	 */
	public function getMemcached()
	{
		return Snowflake::app()->memcached;
	}

}
