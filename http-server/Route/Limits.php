<?php


namespace HttpServer\Route;


use Exception;
use HttpServer\Application;

/**
 * Class Limits
 * @package BeReborn\Route
 */
class Limits extends Application
{

	public $route = [];

	/**
	 * @param string $path
	 * @param int $limit
	 * @param int $duration
	 * @param bool $isBindConsumer
	 * @return $this
	 * 设置限流
	 */
	public function addLimits(string $path, int $limit, int $duration = 60, bool $isBindConsumer = false)
	{
		if ($limit < 0) {
			$limit = 0;
		}
		$this->route[$path] = [$limit, $duration, $isBindConsumer];
		return $this;
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws Exception
	 *
	 * 判断有没有被限流
	 */
	public function isRestrictedCurrent(int $userId = 0)
	{
		$path = \request()->getUri();
		if (!isset($this->route[$path])) {
			return false;
		}
		$redis = \BeReborn::getRedis();
		[$limit, $duration, $isBindConsumer] = $this->route[$path];
		if ($limit < 1) {
			return false;
		}
		if ($isBindConsumer && $userId < 1) {
			return true;
		}

		$uri = md5($path) . '_' . $userId;
		if ($redis->incr($uri) > $limit) {
			return true;
		}
		if ($redis->ttl($uri) == -1) {
			$redis->expire($uri, $duration);
		}
		return false;
	}


}
