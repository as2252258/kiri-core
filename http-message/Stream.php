<?php

namespace Http\Message;

use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\StreamInterface;


class Stream implements StreamInterface
{


	/**
	 * @var string|resource
	 */
	private mixed $content = '';


	/**
	 * @var int
	 */
	private int $size = 0;


	/**
	 * @param mixed $stream
	 */
	public function __construct(mixed $stream = '')
	{
		$this->content = $stream;
		if (!is_resource($stream)) {
			$this->size = strlen($stream);
		} else {
			$state = fstat($this->content);
			if ($state) {
				$this->size = $state['size'];
			}
		}
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->content;
	}


	/**
	 *
	 */
	public function close()
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @return resource|null
	 */
	public function detach()
	{
		if (!is_resource($this->content)) {
			throw new \BadMethodCallException('Not Accomplish Method.');
		}
		$steam = stream_context_create();
		stream_copy_to_stream($this->content, $steam);
		return $steam;
	}


	/**
	 * @return int
	 */
	public function getSize(): int
	{
		return $this->size;
	}


	/**
	 * @return bool|int
	 */
	public function tell(): bool|int
	{
		if (!is_resource($this->content)) {
			throw new \BadMethodCallException('Not Accomplish Method.');
		}
		return ftell($this->content);
	}


	/**
	 * @return bool
	 */
	public function eof(): bool
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @return bool
	 */
	public function isSeekable(): bool
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @param int $offset
	 * @param int $whence
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		if (!is_resource($this->content)) {
			throw new \BadMethodCallException('Not Accomplish Method.');
		}
		fseek($this->content, $offset, $whence);
	}


	/**
	 *
	 */
	public function rewind()
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @return bool
	 */
	public function isWritable(): bool
	{
		if (!is_resource($this->content)) {
			return true;
		}
		if (is_writable($this->content)) {
			return true;
		}
		return false;
	}


	/**
	 * @param string $string
	 * @return int
	 */
	public function write($string): int
	{
		if (is_resource($this->content)) {
			fwrite($this->content, $string);
			$state = fstat($this->content);
			if ($state) {
				$this->size = $state['size'];
			}
		} else {
			$this->content = $string;
			$this->size = strlen($string);
		}
		return $this->size;
	}


	/**
	 * @return bool
	 */
	public function isReadable(): bool
	{
		if (!is_resource($this->content)) {
			return true;
		}
		if (is_readable($this->content)) {
			return true;
		}
		return false;
	}


	/**
	 * @param int $length
	 * @return false|string
	 */
	public function read($length): bool|string
	{
		if (is_resource($this->content)) {
			return fread($this->content, $length);
		} else {
			return $this->content;
		}
	}


	/**
	 * @return string|bool
	 */
	public function getContents(): string|bool
	{
		if (is_resource($this->content)) {
			return stream_get_contents($this->content);
		} else {
			return $this->content;
		}
	}


	/**
	 * @param null $key
	 * @return array
	 */
	#[ArrayShape([
		"timed_out"    => "bool",
		"blocked"      => "bool",
		"eof"          => "bool",
		"unread_bytes" => "int",
		"stream_type"  => "string",
		"wrapper_type" => "string",
		"wrapper_data" => "mixed",
		"mode"         => "string",
		"seekable"     => "bool",
		"uri"          => "string",
		"crypto"       => "array",
		"mediatype"    => "string",
	])]
	public function getMetadata($key = null): array
	{
		if (is_resource($this->content)) {
			return stream_get_meta_data($this->content);
		}
		throw new \BadMethodCallException('Not Accomplish Method.');
	}
}
