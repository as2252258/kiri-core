<?php


namespace Snowflake\Pool;


use Exception;
use Swoole\Timer;

trait Timeout
{

	public int $creates = -1;


	public int $lastTime = 0;


	/**
	 * @throws Exception
	 */
	public function Heartbeat_detection()
	{
		if ($this->lastTime == 0) {
			return;
		}
		if ($this->lastTime + 60 < time()) {
			$this->flush(0);
		} else if ($this->lastTime + 30 < time()) {
			$this->flush(2);
		}
	}


	/**
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function flush($retain_number)
	{
		$channels = $this->getChannels();
		foreach ($channels as $name => $channel) {
			$this->pop($channel, $name, $retain_number);
		}
		if ($retain_number == 0) {
			$this->debug('release Timer::tick');
			Timer::clear($this->creates);
			$this->creates = 0;
		}
	}


	/**
	 * @param $channel
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop($channel, $name, $retain_number)
	{
		while ($channel->length() > $retain_number) {
			[$timer, $connection] = $channel->pop();
			if ($connection) {
				unset($connection);
			}
			$this->desc($name);
		}
	}
}
