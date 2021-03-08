<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use ReflectionException;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
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
	 * @throws ComponentException
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function getRedis(): Redis
	{
		return Snowflake::app()->get('redis_connections');
	}

	/**
	 * @return Connection
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function getDb(): Connection
	{
		return Snowflake::app()->get('connections');
	}

}
