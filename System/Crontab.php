<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\Component;

/**
 * Class Async
 * @package Snowflake
 */
class Crontab extends Component
{


	/**
	 * @param array|Closure $handler
	 * @param int $tickTime
	 * @param bool $isLoop
	 * @throws Exception
	 */
	public function dispatch(array|Closure $handler, $tickTime = 1, $isLoop = false)
	{
		$redis = Snowflake::app()->getRedis();

		if (is_array($handler) && is_string($handler[0])) {
			$handler[0] = Snowflake::createObject($handler[0]);
		}

		$executeTime = time() + $tickTime;

		$crontab = ['isLoop' => $isLoop, 'handler' => $handler, 'tick' => $tickTime];

		$redis->sAdd('system:crontab', $executeTime, serialize($crontab));
	}

}
