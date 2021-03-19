<?php


namespace Snowflake\Abstracts;


use Snowflake\Snowflake;
use Exception;


/**
 * Class Crontab
 * @package Snowflake\Abstracts
 */
class Crontab extends Component
{


	/**
	 * @param \Snowflake\Crontab $crontab
	 * @param $executeTime
	 * @throws Exception
	 */
	public function dispatch(\Snowflake\Crontab $crontab, int $executeTime)
	{
		$redis = Snowflake::app()->getRedis();

		$redis->zAdd('system:crontab', (string)$executeTime, serialize($crontab));
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clear(string $name)
	{
		$redis = Snowflake::app()->getRedis();

		$data = $redis->zRevRange('system:crontab', 0, -1);
		if (empty($data)) {
			return;
		}
		foreach ($data as $datum) {
			/** @var \Snowflake\Crontab $crontab */
			$crontab = unserialize($datum);
			if ($crontab->getName() == $name) {
				$redis->zRem('system:crontab', $datum);
			}
		}

	}


	/**
	 * @throws Exception
	 */
	public function clearAll()
	{
		$redis = Snowflake::app()->getRedis();

		$redis->del('system:crontab');
	}


}
