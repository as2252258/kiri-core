<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */
declare(strict_types=1);

namespace HttpServer\Http;

/**
 * Class HttpHeaders
 * @package Snowflake\Snowflake\Http
 */
trait HttpHeaders
{

	private array $_headers = [];


	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers): void
	{
		$this->_headers = $headers;
	}


	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->_headers;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name): mixed
	{
		return $this->_headers[$name];
	}


	/**
	 * @return string
	 */
	public function getContentType(): string
	{
        return $this->_headers['content-type'] ?? '';
	}


	/**
	 * @return string|null
	 */
	public function getRequestUri(): ?string
	{
        return $this->_headers['request_uri'];
	}


	/**
	 * @return string|null
	 */
	public function getRequestMethod(): ?string
	{
        return $this->_headers['request_method'];
	}


	/**
	 * @return mixed
	 */
	public function getAgent(): mixed
	{
        return $this->_headers['user-agent'];
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function exists($name): bool
	{
        return $this->_headers[$name] ??  null === null;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->_headers;
	}

}
