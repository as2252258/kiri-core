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


	private array $_channels = [];


	/**
	 * @param mixed $value
	 * @param string $name
	 * @throws Exception
	 */
	public function push(mixed $value, string $name = ''): void
	{
		$channel = $this->channelInit($name);
		$channel->enqueue($value);
	}


	/**
	 * @param string $name
	 * @return bool|SplQueue
	 */
	private function channelInit(string $name = ''): bool|SplQueue
	{
		if (!isset($this->_channels[$name]) || !($this->_channels[$name] instanceof SplQueue)) {
			$this->_channels[$name] = new SplQueue();
		}
		return $this->_channels[$name];
	}


	/**
	 *
	 * 清空缓存
	 */
	public function cleanAll()
	{
		/** @var SplQueue $channel */
		foreach ($this->_channels as $channel) {
			while ($channel->count() > 0) {
				$channel->dequeue();
			}
		}
		$this->_channels = [];
	}

	/**
	 * @param $timeout
	 * @param Closure $closure
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function pop(string $name, Closure $closure, int|float $timeout = null): mixed
	{
		if (($channel = $this->channelInit($name)) == false) {
			return $this->addError('Channel is full.');
		}
		if (!$channel->isEmpty()) {
			return $channel->pop();
		}
		if ($timeout !== null) {
			$data = $channel->dequeue();
		}
		if (empty($data)) {
			$data = call_user_func($closure);
		}
		return $data;
	}


}
