<?php
declare(strict_types=1);

namespace Kafka;


use RdKafka\Message;

class Struct
{

	public int $offset;

	public Message $message;
	public string $topic;

	public mixed $value;
	public int $part;

	/**
	 * Struct constructor.
	 * @param $topic
	 * @param $message
	 */
	public function __construct($topic, Message $message)
	{
		var_dump($message->payload);
		$message->payload = swoole_unserialize($message->payload);

		$this->topic = $topic;
		$this->offset = $message->offset;
		$this->part = $message->partition;
		$this->message = $message;
		$this->value = $message->payload;
	}

}
