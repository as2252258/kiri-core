<?php


namespace Kafka;


class Struct
{

	public $offset;

	public $part;
	public $topic;

	public $crc;
	public $magic;
	public $attr;
	public $timestamp;
	public $key;
	public $value;


	/**
	 * Struct constructor.
	 * @param $topic
	 * @param $part
	 * @param $message
	 */
	public function __construct($topic, $part, $message)
	{
		$this->topic = $topic;
		$this->offset = $message['offset'];
		$this->part = $part;
		$this->crc = $message['message']['crc'];
		$this->magic = $message['message']['magic'];
		$this->attr = $message['message']['attr'];
		$this->timestamp = $message['message']['timestamp'];
		$this->key = $message['message']['key'];
		$this->value = $message['message']['value'];
	}

}
