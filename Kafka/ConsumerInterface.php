<?php


namespace Kafka;


/**
 * Interface ConsumerInterface
 * @package App\Kafka
 */
interface ConsumerInterface
{


	/**
	 * @param int $offset
	 * @param $part
	 * @param int $crc
	 * @param int $magic
	 * @param int $attr
	 * @param int $timestamp
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function onHandler(int $offset, $part, int $crc, int $magic, int $attr, int $timestamp, string $key, string $value);


}
