<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use JetBrains\PhpStorm\Pure;
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
	#[Pure] public function getRedis(): Redis
	{
		return Snowflake::app()->redis_connections;
	}

	/**
	 * @return Connection
	 */
	#[Pure] public function getDb(): Connection
	{
		return Snowflake::app()->connections;
	}


}
