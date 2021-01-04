<?php
declare(strict_types=1);


namespace Snowflake\Pool;



use Snowflake\Snowflake;

/**
 * Class Pool
 * @package Snowflake\Pool
 * @property Redis $redis
 * @property Connection $db
 * @property $memcached
 */
class Pool extends \Snowflake\Abstracts\Pool
{

	/**
	 * @return Redis
	 */
	public function getRedis(): Redis
	{
		return Snowflake::app()->redis_connections;
	}

	/**
	 * @return Connection
	 */
	public function getDb(): Connection
	{
		return Snowflake::app()->connections;
	}


}
