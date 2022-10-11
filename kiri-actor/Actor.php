<?php

namespace Kiri\Actor;

use Exception;
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
	private int $messageId = -1;


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
	 * @var int
	 */
	private int $refreshInterval = 0;


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
	 * @return bool
	 */
	public function isShutdown(): bool
	{
		return $this->isShutdown;
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
		Coroutine::cancel($this->coroutineId);
		if ($this->messageId > -1) {
			Coroutine::cancel($this->messageId);
		}
		$this->channel->close();
	}


	/**
	 * @return void
	 */
	public function onUpdate(): void
	{
	}


	/**
	 * @return void
	 * @throws Exception
	 */
	public function run(): void
	{
		if ($this->refreshInterval < 1) {
			throw new Exception('Refresh interval must be greater than 1');
		}
		$this->setState(ActorState::BUSY);
		$this->init();
		$this->messageId = Coroutine::create(fn() => $this->loop());
		$this->interval();
		$this->setState(ActorState::IDLE);
	}


	/**
	 * @return void
	 */
	private function interval(): void
	{
		if ($this->isShutdown()) {
			return;
		}

		try {
			$this->onUpdate();
		} catch (\Throwable $exception) {
			\Kiri::getLogger()->error(throwable($exception));
		}

		Coroutine::sleep($this->refreshInterval / 1000);

		$this->interval();
	}


	/**
	 * @return bool
	 */
	private function loop(): bool
	{
		if ($this->messageId == -1) {
			$this->messageId = Coroutine::getCid();
		}
		if ($this->channel->errCode == SWOOLE_CHANNEL_CLOSED) {
			$this->channel = new Channel(99);
		}
		$message = $this->channel->pop();
		$this->process($message);
		if ($this->isShutdown()) {
			return true;
		}
		return $this->loop();
	}

}
