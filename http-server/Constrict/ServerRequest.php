<?php

namespace Server\Constrict;

use Psr\Http\Message\ServerRequestInterface;


/**
 *
 */
class ServerRequest extends TRequest implements ServerRequestInterface
{


	/**
	 * @return array
	 */
	public function getServerParams(): array
	{
		return [];
	}


	/**
	 * @return array
	 */
	public function getCookieParams(): array
	{
		return $this->cookies;
	}


	/**
	 * @param array $cookies
	 * @return $this|ServerRequest
	 */
	public function withCookieParams(array $cookies): ServerRequestInterface
	{
		$this->cookies = $cookies;
		return $this;
	}


	/**
	 * @return array
	 */
	public function getQueryParams(): array
	{
		return [];
	}

	public function withQueryParams(array $query): ServerRequestInterface
	{
		return $this;
	}

	public function getUploadedFiles()
	{
		// TODO: Implement getUploadedFiles() method.
	}

	public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
	{
		return $this;
	}


	/**
	 * @return array|object|null
	 */
	public function getParsedBody(): object|array|null
	{
		return null;
	}


	/**
	 * @param array|object|null $data
	 * @return ServerRequestInterface
	 */
	public function withParsedBody($data): ServerRequestInterface
	{
		return $this;
	}


	/**
	 * @return array
	 */
	public function getAttributes(): array
	{
		return [];
	}


	/**
	 * @param string $name
	 * @param null $default
	 * @return mixed|null
	 */
	public function getAttribute($name, $default = null)
	{
		return $default;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return ServerRequestInterface
	 */
	public function withAttribute($name, $value): ServerRequestInterface
	{
		return $this;
	}


	/**
	 * @param string $name
	 * @return ServerRequestInterface
	 */
	public function withoutAttribute($name): ServerRequestInterface
	{
		// TODO: Implement withoutAttribute() method.
		return $this;
	}
}
