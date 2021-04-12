<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * Class Emit
 * @package HttpServer
 */
class Emit extends Component
{

	private array $_array = [];


	/**
	 * @param string $name
	 * @param string $message
	 * @throws Exception
	 */
	public function emit(string $name, string $message)
	{
		$redis = Snowflake::app()->getRedis();
		if (!$redis->exists($name) || $redis->sCard($name) < 1) {
			return;
		}

		$socket = Snowflake::app()->getSwoole();
		foreach ($redis->sMembers($name) as $value) {
			$socket->push($value, $message);
		}
	}


	/**
	 * @param string $name
	 * @param int $value
	 * @throws Exception
	 */
	public function register(string $name, int $value)
	{
		redis()->sAdd($name, $value);
	}


	/**
	 * @param string $name
	 * @param int|null $value
	 * @throws Exception
	 */
	public function clear(string $name, ?int $value = null)
	{
		if (!empty($value)) {
			redis()->sRem($name, $value);
		} else {
			redis()->del($name);
		}
	}


}
