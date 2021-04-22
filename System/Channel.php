<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\Component;
use Swoole\Coroutine\Channel as CChannel;


/**
 * Class Channel
 * @package Snowflake
 */
class Channel extends Component
{


	private ?CChannel $_channel = null;


	private array $_channels = [];


	/**
	 * @param mixed $value
	 * @param string $name
	 * @param int $length
	 * @return mixed
	 * @throws Exception
	 */
	public function push(mixed $value, string $name = '', $length = 999): mixed
	{
		return true;

		$channel = $this->channelInit($length, $name);
		if ($channel->isFull()) {
			return $this->addError('Channel is full.');
		}

		return true;
		return $channel->push($value);
	}


	/**
	 * @param int $length
	 * @param string $name
	 * @return bool|CChannel
	 */
	private function channelInit(int $length, string $name = ''): bool|CChannel
	{
		if ($length < 1) {
			return false;
		}
		if (empty($name)) {
			if (!($this->_channel instanceof CChannel)
				|| $this->_channel->close()) {
				$this->_channel = new CChannel($length);
			}
			return $this->_channel;
		} else {
			if (!isset($this->_channels[$name]) || !($this->_channels[$name] instanceof CChannel)) {
				$this->_channels[$name] = new CChannel($length);
			} else if ($this->_channels[$name]->close()) {
				$this->_channels[$name] = new CChannel($length);
			}
			return $this->_channels[$name];
		}
	}


	/**
	 *
	 * 清空缓存
	 */
	public function cleanAll()
	{
		return;

		/** @var CChannel $channel */
		foreach ($this->_channels as $channel) {
			$channel->close();
		}
		$this->_channels = [];
	}

	/**
	 * @param $timeout
	 * @param Closure $closure
	 * @param string $name
	 * @param int $length
	 * @return mixed
	 * @throws Exception
	 */
	public function pop(string $name, Closure $closure, int|float $timeout = null, int $length = 999): mixed
	{
		return call_user_func($closure);

		if (($channel = $this->channelInit($length, $name)) == false) {
			return $this->addError('Channel is full.');
		}
		if (!$channel->isEmpty()) {
			return $channel->pop();
		}
		$data = null;
//		if ($timeout !== null) {
//			$data = $channel->pop($timeout);
//		}
		if (empty($data)) {
			$data = call_user_func($closure);
		}
		return $data;
	}


}
