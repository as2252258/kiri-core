<?php


namespace Server;


use JetBrains\PhpStorm\Pure;
use Swoole\Coroutine\Channel;
use const Grpc\CHANNEL_SHUTDOWN;

class ApplicationStore
{


	private static ?ApplicationStore $applicationStore = null;


	private Channel $lock;


	/**
	 * ApplicationStore constructor.
	 */
	private function __construct()
	{
		$this->lock = new Channel(1);
	}


	/**
	 * @return ApplicationStore|null
	 */
	public static function getStore(): ?ApplicationStore
	{
		if (!(static::$applicationStore instanceof ApplicationStore)) {
			static::$applicationStore = new ApplicationStore();
		}
		return static::$applicationStore;
	}


	/**
	 * @return $this
	 */
	public function instance(): static
	{
		return $this;
	}


	/**
	 *
	 */
	public function waite(): void
	{
		if ($this->lock->errCode == SWOOLE_CHANNEL_CLOSED) {
			return;
		}
		$this->lock->pop(-1);
	}


	public function close()
	{
		$this->lock->push(1);
		$this->lock->close();
	}


	/**
	 *
	 */
	public function done(): void
	{
		$this->lock->pop();
	}


	/**
	 * @return bool
	 */
	public function isBusy(): bool
	{
		return !$this->lock->isEmpty();
	}


	/**
	 * @return string
	 */
	#[Pure] public function getStatus(): string
	{
		return env('state');
	}

}
