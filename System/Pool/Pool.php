<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Exception;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Snowflake;

/**
 * Class Pool
 * @package Snowflake\Pool
 * @property Redis $redis
 * @property Connection $db
 * @property $memcached
 */
class Pool extends BaseObject
{

	/**
	 * @return Redis
	 * @throws Exception
	 */
	public function getRedis(): Redis
	{
		return Snowflake::app()->get('redis_connections');
	}

	/**
	 * @return Connection
	 * @throws Exception
	 */
	public function getDb(): Connection
	{
		return Snowflake::app()->get('connections');
	}

}
