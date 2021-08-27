<?php

namespace Server\Message;

use Psr\Http\Message\ResponseInterface;


/**
 *
 */
class Response implements ResponseInterface
{
	use Message;

	public int $statusCode = 200;


	public string $reasonPhrase = '';


	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		// TODO: Implement getStatusCode() method.
		return $this->statusCode;
	}

	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return ResponseInterface
	 */
	public function withStatus($code, $reasonPhrase = ''): ResponseInterface
	{
		// TODO: Implement withStatus() method.
		$class = clone $this;
		$class->statusCode = $code;
		$class->reasonPhrase = $reasonPhrase;
		return $class;
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		// TODO: Implement getReasonPhrase() method.
		return $this->reasonPhrase;
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Allow-Headers', $value);
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Request-Method', $value);
	}


	/**
	 * @param $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin($value): ResponseInterface
	{
		return $this->withHeader('Access-Control-Allow-Origin', $value);
	}

}
