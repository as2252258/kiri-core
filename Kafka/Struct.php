<?php


namespace Kafka;


use RdKafka\Message;

class Struct
{

	public int $offset;

	public Message $message;
	public string $topic;

	public string $value;
	public string $part;

	/**
	 * Struct constructor.
	 * @param $topic
	 * @param $part
	 * @param $message
	 */
	public function __construct($topic, $part,Message $message)
	{
		$this->topic = $topic;
		$this->offset = $message->offset;
		$this->part = $message->partition;
		$this->message = $message;
		$this->value = $message->payload;
	}

}
