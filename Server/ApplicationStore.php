<?php


namespace Server;


use JetBrains\PhpStorm\Pure;
use Swoole\Coroutine\Channel;

class ApplicationStore
{


	private static ?ApplicationStore $applicationStore = null;


	private Channel $lock;


	private function __construct()
	{
		$this->lock = new Channel(99999);
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
	public function add(): static
	{
		$this->lock->push(1);
		return $this;
	}


	/**
	 *
	 */
	public function waite(): void
	{
		if ($this->lock->isEmpty()) {
			return;
		}
		$this->lock->pop(-1);
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
