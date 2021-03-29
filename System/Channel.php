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
		$channel = $this->channelInit($length, $name);
		if ($channel->isFull()) {
			return $this->addError('Channel is full.');
		}
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
	 * @param $timeout
	 * @param Closure $closure
	 * @param string $name
	 * @param int $length
	 * @return mixed
	 * @throws Exception
	 */
	public function pop(int|float $timeout, Closure $closure, string $name = '', int $length = 9999): mixed
	{
		if (($channel = $this->channelInit($length, $name)) == false) {
			return $this->addError('Channel is full.');
		}

		$data = null;
		if (!$channel->isEmpty()) {
			$data = $channel->pop($timeout);
		}
		if (empty($data)) {
			$data = call_user_func($closure);
		}
		return $data;
	}


}
