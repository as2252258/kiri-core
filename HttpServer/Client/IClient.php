<?php


namespace HttpServer\Client;


use Closure;

interface IClient
{


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function get(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function post(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function delete(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function options(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function upload(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function put(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function head(string $path, array $params = []): Result|int|array|string;


	/**
	 * @param string $method
	 * @param string $path
	 * @param array $params
	 * @return array|Result|int|string
	 */
	public function request(string $method, string $path, array $params = []): Result|array|int|string;


	/**
	 * @param string $host
	 * @return mixed
	 */
	public function setHost(string $host): void;


	/**
	 * @param array $header
	 * @return mixed
	 */
	public function setHeader(array $header): void;


	/**
	 * @param array $header
	 * @return mixed
	 */
	public function setHeaders(array $header): array;


	/**
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function addHeader(string $key, string $value): void;


	/**
	 * @param int $value
	 * @return mixed
	 */
	public function setTimeout(int $value): void;


	/**
	 * @param Closure|null $value
	 * @return mixed
	 */
	public function setCallback(?Closure $value): void;


	/**
	 * @param string $value
	 * @return static
	 */
	public function setMethod(string $value): static;


	/**
	 * @param bool $isSSL
	 * @return mixed
	 */
	public function setIsSSL(bool $isSSL): void;


	/**
	 * @param string $agent
	 * @return mixed
	 */
	public function setAgent(string $agent): void;


	/**
	 * @param string $errorCodeField
	 * @return mixed
	 */
	public function setErrorCodeField(string $errorCodeField): void;


	/**
	 * @param string $errorMsgField
	 * @return mixed
	 */
	public function setErrorMsgField(string $errorMsgField): void;


	/**
	 * @param bool $use_swoole
	 * @return mixed
	 */
	public function setUseSwoole(bool $use_swoole): void;


	/**
	 * @param string $ssl_cert_file
	 * @return mixed
	 */
	public function setSslCertFile(string $ssl_cert_file): void;


	/**
	 * @param string $ssl_key_file
	 * @return mixed
	 */
	public function setSslKeyFile(string $ssl_key_file): void;


	/**
	 * @param string $ssl_key_file
	 * @return mixed
	 */
	public function setCa(string $ssl_key_file): void;


	/**
	 * @param int $port
	 * @return mixed
	 */
	public function setPort(int $port): void;


	/**
	 * @param string $message
	 * @return mixed
	 */
	public function setMessage(string $message): void;


	/**
	 * @param string $data
	 * @return mixed
	 */
	public function setData(string $data): void;


	/**
	 * @param int $connect_timeout
	 * @return mixed
	 */
	public function setConnectTimeout(int $connect_timeout): void;


	/**
	 * @param string $contentType
	 * @return mixed
	 */
	public function setContentType(string $contentType): void;
}
