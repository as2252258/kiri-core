<?php

namespace Kiri\Actor;

use JsonSerializable;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

abstract class Actor implements ActorInterface, JsonSerializable
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
	 * @var int
	 */
	private int $coroutineId = -1;


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
		$this->channel = new Channel(99);
		$this->startTime = microtime(true);
	}



	/**
	 * @return void
	 */
	public function init(): void
	{

	}


	/**
	 * @param $id
	 * @return static
	 */
	public static function newActor($id): static
	{
		$actor = new static($id);
		$actor->listen();
		return $actor;
	}


	/**
	 * @return void
	 */
	private function listen(): void
	{
		Coroutine::create(function (Actor $actor) {
			$actor->coroutineId = Coroutine::getCid();
			$this->run();
		}, $this);
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
		$this->setState(ActorState::BUSY);
		$this->init();
		$this->loop();
		$this->setState(ActorState::IDLE);
	}


	/**
	 * @return mixed
	 */
	private function loop(): mixed
	{
		if ($this->channel->errCode == SWOOLE_CHANNEL_CLOSED) {
			$this->channel = new Channel(99);
		}
		$message = $this->channel->pop();
		$this->process($message);
		return $this->loop();
	}

}
