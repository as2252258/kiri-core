<?php

namespace Kiri\Actor;

use Swoole\Coroutine\Channel;

abstract class Actor implements ActorInterface
{


	/**
	 * @var Channel
	 */
	private Channel $channel;


	/**
	 * @var bool
	 */
	private bool $isShutdown = false;


	/**
	 * @var ActorState
	 */
	private ActorState $state;


	/**
	 * @var float
	 */
	private float $startTime = 0;


	/**
	 * @return ActorState
	 */
	public function getState(): ActorState
	{
		return $this->state;
	}


	/**
	 * @param ActorState $state
	 */
	public function setState(ActorState $state): void
	{
		$this->state = $state;
	}


	/**
	 * @return float
	 */
	public function getRunTime(): float
	{
		return microtime(true) - $this->startTime;
	}


	/**
	 * @param string $uniqueId
	 */
	private function __construct(readonly public string $uniqueId)
	{
		$this->channel = new Channel(1000);
		$this->startTime = microtime(true);
	}


	/**
	 * @param $id
	 * @return static
	 */
	public static function newActor($id): static
	{
		return new static($id);
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->uniqueId;
	}


	/**
	 * @param mixed $response
	 * @return bool
	 */
	public function write(mixed $response): bool
	{
		return $this->channel->push($response);
	}


	/**
	 * @return void
	 */
	public function shutdown(): void
	{
		$this->isShutdown = true;
		$this->channel->close();
	}


	/**
	 * @return void
	 */
	public function run(): void
	{
		if ($this->channel->errCode == SWOOLE_CHANNEL_CLOSED) {
			if ($this->isShutdown) {
				return;
			}
			$this->channel = new Channel(1000);
		}
		$this->setState(ActorState::BUSY);
		while (!$this->isShutdown) {
			$message = $this->channel->pop();
			$this->process($message);
			unset($message);
		}
		$this->setState(ActorState::IDLE);
		if ($this->isShutdown) {
			$this->channel->close();
			return;
		}
		$this->run();
	}

}
