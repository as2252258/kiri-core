<?php
declare(strict_types=1);


namespace Queue\Abstracts;


use Exception;
use Queue\Consumer;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Queue
 * @package Queue\Abstracts
 */
abstract class Queue extends Component implements Relyon
{


	/**
	 * @param string $key
	 * @param Consumer $consumer
	 * @param int $score
	 * @return false|int
	 * @throws ComponentException
	 * @throws Exception
	 */
	protected function push(string $key, Consumer $consumer, $score = 0)
	{
		$redis = Snowflake::app()->getRedis();
		try {
			$serialize = serialize($consumer);
			if (!$redis->lock($hash = md5($serialize))) {
				return false;
			}
			$isExists = $redis->zRevRank($key, $serialize);
			if ($isExists !== null) {
				$redis->zAdd($key, $score, $serialize);
			}
			return $redis->unlink($hash);
		} finally {
			$redis->release();
		}
	}


	/**
	 * @param $key
	 * @param string $consumer
	 * @return false|int
	 * @throws ComponentException
	 * @throws Exception
	 */
	protected function pop($key, string $consumer)
	{
		$redis = Snowflake::app()->getRedis();
		try {
			if (!$redis->lock($hash = md5($consumer))) {
				return false;
			}
			$isExists = $redis->zRevRank($key, $consumer);
			if ($isExists === null) {
				return $redis->unlink($hash);
			}
			$redis->zRem($key, $consumer);
			return $redis->unlink($hash);
		} finally {
			$redis->release();
		}
	}


}
