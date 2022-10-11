<?php

namespace Kiri\Actor;

class ActorMessage implements \JsonSerializable
{

	/**
	 * @var int
	 */
	private int $userId;


	/**
	 * @var string
	 */
	private string $event;


	/**
	 * @var array
	 */
	private array $body;

	/**
	 * @param int $userId
	 * @param string $event
	 * @param array $body
	 */
	public function __construct(int $userId, string $event, array $body)
	{
		$this->userId = $userId;
		$this->event = $event;
		$this->body = $body;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function getEvent(): string
	{
		return $this->event;
	}

	/**
	 * @return array
	 */
	public function getBody(): array
	{
		return $this->body;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return [
			'userId' => $this->userId,
			'event'  => $this->event,
			'body'   => $this->body
		];
	}

}
