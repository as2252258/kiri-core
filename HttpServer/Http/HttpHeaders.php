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
class HttpHeaders
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
		return $this->__handler__();
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name): ?string
	{
		return $this->__handler__($name);
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function get($name, $default = null): mixed
	{
		return $this->__handler__($name, $default);
	}


	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->__handler__('content-type');
	}


	/**
	 * @return string|null
	 */
	public function getRequestUri(): ?string
	{
		$uri = $this->__handler__('request_uri', '/');
		if (empty($uri)) {
			return '/';
		}
		return $uri;
	}


	/**
	 * @return string|null
	 */
	public function getRequestMethod(): ?string
	{
		return $this->__handler__('request_method');
	}


	/**
	 * @return mixed
	 */
	public function getAgent(): mixed
	{
		return $this->__handler__('user-agent');
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function exists($name): bool
	{
		return $this->__handler__($name) === null;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->__handler__();
	}


	/**
	 * @param null $name
	 * @param null $default
	 * @return mixed
	 */
	private function __handler__($name = null, $default = null): mixed
	{
		$headers = Context::getContext(Request::class);
		if (!empty($name)) {
			return $headers->_headers[$name] ?? $default;
		}
		return $headers->_headers;
	}

}
