<?php

namespace Server\Message;

use Psr\Http\Message\StreamInterface;


/**
 *
 */
class Stream implements StreamInterface
{


	private string $body;


	private int $size;


	private int $offset = 0;


	private bool $writable = true;


	/**
	 * @param string $body
	 */
	public function __construct(string $body)
	{
		$this->body = $body;
	}


	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->body;
	}


	/**
	 *
	 */
	public function close(): void
	{
		$this->detach();
	}


	/**
	 *
	 */
	public function detach(): void
	{
		$this->body = '';
		$this->size = 0;
		$this->writable = false;
	}


	/**
	 * @return int
	 */
	public function getSize(): int
	{
		if ($this->size == 0) {
			$this->size = strlen($this->body);
		}
		return $this->size;
	}


	/**
	 * @return int
	 */
	public function tell(): int
	{
		return $this->offset;
	}


	/**
	 * @return bool
	 */
	public function eof(): bool
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isSeekable(): bool
	{
		return $this->offset == 0;
	}


	/**
	 * @param int $offset
	 * @param int $whence
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		$this->offset = $offset;
	}


	/**
	 *
	 */
	public function rewind(): void
	{
		$this->offset = 0;
	}

	/**
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return $this->writable;
	}


	/**
	 * @param string $string
	 * @return int
	 */
	public function write($string): int
	{
		$this->body = $string;
		$this->size = strlen($this->body);
		return $this->size;
	}


	/**
	 * @param string $string
	 * @return int
	 */
	public function append(string $string): int
	{
		$this->body .= $string;
		$this->size = strlen($this->body);
		return $this->size;
	}


	/**
	 * @return bool
	 */
	public function isReadable(): bool
	{
		return true;
	}


	/**
	 * @param int $length
	 * @return string
	 */
	public function read($length = -1): string
	{
		if ($length > 0) {
			return substr($this->body, 0, $length);
		}
		return $this->body;
	}


	/**
	 * @return string
	 */
	public function getContents(): string
	{
		return $this->body;
	}


	/**
	 * @param null $key
	 * @return mixed
	 */
	public function getMetadata($key = null): mixed
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}
}
