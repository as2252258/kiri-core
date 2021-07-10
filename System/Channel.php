<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\Component;
use SplQueue;


/**
 * Class Channel
 * @package Snowflake
 */
class Channel extends Component
{


	private static ?array $_channels = [];


	private static ?array $_waitRecover = [];


	public function init()
	{
		Event::on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'recover']);
		Event::on(Event::SERVER_WORKER_EXIT, [$this, 'cleanAll']);
	}


	/**
	 * 回收对象
	 */
	public function recover()
	{
		foreach (Channel::$_waitRecover as $key => $value) {
			if (empty($value)) {
				continue;
			}
			$channel = $this->channelInit($key);
			if ($channel->count() >= 100) {
				continue;
			}
			foreach ($value as $item) {
				$channel->enqueue($item);
			}
		}
		Channel::$_waitRecover = [];
	}


	/**
	 * @param mixed $value
	 * @param string $name
	 * @throws Exception
	 */
	public function push(mixed $value, string $name = ''): void
	{
		if (!isset(Channel::$_waitRecover[$name])) {
			Channel::$_waitRecover[$name] = [];
		}
		Channel::$_waitRecover[$name][] = $value;
	}


	/**
	 * @param string $name
	 * @return bool|SplQueue
	 */
	private function channelInit(string $name = ''): bool|SplQueue
	{
		if (!isset(static::$_channels[$name]) || !(static::$_channels[$name] instanceof SplQueue)) {
			static::$_channels[$name] = new SplQueue();
		}
		return static::$_channels[$name];
	}


	/**
	 *
	 * 清空缓存
	 */
	public function cleanAll()
	{
        static::$_channels = null;
		static::$_channels = [];
	}


	/**
	 * @param string $name
	 * @param Closure $closure
	 * @return mixed
	 */
	public function pop(string $name, Closure $closure): mixed
	{
		$channel = $this->channelInit($name);
		if ($channel->isEmpty()) {
			return call_user_func($closure);
		}
		return $channel->dequeue();
	}


}
