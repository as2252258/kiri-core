<?php


namespace Snowflake;


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
	 * @param int $timeout
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function pop($timeout = 1, string $name = ''): mixed
	{
		if ($channel = $this->channelInit(0, $name)) {
			return $this->addError('Channel is full.');
		}
		if (!$channel->isEmpty()) {
			return $channel->pop($timeout);
		}
		return null;
	}


}
