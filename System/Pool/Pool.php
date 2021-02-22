<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Snowflake\Exception\ComponentException;
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
	 * @throws ComponentException
	 */
	public function getRedis(): Redis
	{
		return Snowflake::app()->get('redis_connections');
	}

	/**
	 * @return Connection
	 * @throws ComponentException
	 */
	public function getDb(): Connection
	{
		return Snowflake::app()->get('connections');
	}


	public function createClient(string $name, array $config): mixed
	{
		// TODO: Implement createClient() method.
		return null;
	}
}
