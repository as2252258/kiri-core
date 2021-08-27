<?php

namespace Server\Message;

class Headers
{


	private array $headers = [];


	/**
	 * @param array $headers
	 * @return $this
	 */
	public function withHeader(array $headers): static
	{
		$class = clone $this;
		$class->headers = $headers;
		return $class;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return string|array|float|int|null
	 */
	public function get($name, $default = null): string|array|float|int|null
	{
		return $this->headers[$name] ?? $default;
	}


	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->headers;
	}


}
