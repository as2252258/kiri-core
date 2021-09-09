<?php

namespace Server\Message;

use BadMethodCallException;
use Exception;
use Http\IInterface\AuthIdentity;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\UriInterface;
use Server\RequestInterface;


/**
 *
 */
class Request implements RequestInterface
{

	use Message;


	public string $requestTarget;


	public string $method;


	/**
	 * @var Uri|UriInterface
	 */
	private Uri|UriInterface $uri;


	private \Swoole\Http\Request $serverRequest;


	private array $parseBody;

	private array $files = [];


	/**
	 * @var AuthIdentity|null
	 */
	public ?AuthIdentity $authority = null;


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->serverRequest->fd;
	}


	/**
	 * @param AuthIdentity $authority
	 */
	public function setAuthority(AuthIdentity $authority): void
	{
		$this->authority = $authority;
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return RequestInterface
	 */
	public static function parseRequest(\Swoole\Http\Request $request): RequestInterface
	{
		$message = new Request();
		$message->uri = Uri::parseUri($request);
		$message->method = $request->getMethod();
		$message->requestTarget = '';
		$message->serverRequest = $request;
		$message->version = $request->server['server_protocol'];
		$message->stream = new Stream($request->getContent());
		$message->servers = $request->server;
		$message->parseRequestHeaders($request);
		return $message;
	}


	/**
	 * @return float
	 */
	#[Pure] public function getStartTime(): float
	{
		return $this->servers['request_time_float'];
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function input($name, $default = null): mixed
	{
		if (empty($this->parseBody)) {
			$this->parseBody = $this->parseBody($this->stream);
		}
		if (!is_array($this->parseBody)) {
			return $default;
		}
		return $this->parseBody[$name] ?? $default;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function post($name, $default = null): mixed
	{
		return $this->serverRequest->post[$name] ?? $default;
	}


	/**
	 * @param string $field
	 * @param int $max
	 * @return int
	 */
	public function size(string $field = 'size', int $max = 100): int
	{
		$size = (int)$this->query($field);
		if ($size < 1) {
			$size = 1;
		} else if ($size > $max) {
			$size = $max;
		}
		return $size;
	}


	/**
	 * @param string $field
	 * @param string $sizeField
	 * @param int $max
	 * @return float|int
	 */
	public function offset(string $field = 'page', string $sizeField = 'size', int $max = 100): float|int
	{
		$page = (int)$this->query($field);
		if ($page < 1) {
			$page = 1;
		}
		return ($page - 1) * $this->size($sizeField, $max);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return string|null
	 * @throws Exception
	 */
	public function string(string $name, bool $required = false): ?string
	{
		if (is_null($data = $this->post($name))) {
			if ($required) {
				throw new Exception('Parameter is required and cannot be empty.');
			}
			return null;
		}
		return (string)$data;
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return int|null
	 * @throws Exception
	 */
	public function int(string $name, bool $required = false): ?int
	{
		if (is_null($data = $this->post($name))) {
			if ($required) {
				throw new Exception('Parameter is required and cannot be empty.');
			}
			return null;
		}
		return (string)$data;
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return float|null
	 * @throws Exception
	 */
	public function float(string $name, bool $required = false): ?float
	{
		if (is_null($data = $this->post($name))) {
			if ($required) {
				throw new Exception('Parameter is required and cannot be empty.');
			}
			return null;
		}
		return (float)$data;
	}


	/**
	 * @param string $name
	 * @param array $default
	 * @return mixed
	 */
	public function array(string $name, array $default = []): array
	{
		$data = $this->post($name);
		if (!is_array($data)) {
			return $default;
		}
		return $data;
	}


	/**
	 * @return array|null
	 */
	public function gets(): ?array
	{
		return $this->serverRequest->get;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function query($name, $default = null): mixed
	{
		return $this->serverRequest->get[$name] ?? $default;
	}


	/**
	 * @param $name
	 * @return Uploaded|null
	 */
	public function file($name): ?Uploaded
	{
		if (isset($this->serverRequest->files[$name])) {
			return new Uploaded($this->serverRequest->files[$name]);
		}
		return null;
	}


	/**
	 * @return array
	 */
	public function all(): array
	{
		if (empty($this->parseBody)) {
			$this->parseBody = $this->parseBody($this->stream);
		}
		return array_merge($this->serverRequest->post ?? [],
			$this->serverRequest->get ?? [],
			is_array($this->parseBody) ? $this->parseBody : []
		);
	}


	/**
	 * @return string
	 */
	public function getRequestTarget(): string
	{
		throw new BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @param mixed $requestTarget
	 * @return RequestInterface
	 */
	public function withRequestTarget($requestTarget): RequestInterface
	{
		$this->requestTarget = $requestTarget;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}


	/**
	 * @param string $method
	 * @return bool
	 */
	public function isMethod(string $method): bool
	{
		return $this->method === $method;
	}


	/**
	 * @param string $method
	 * @return RequestInterface
	 */
	public function withMethod($method): RequestInterface
	{
		$class = clone $this;
		$class->method = $method;
		return $class;
	}


	/**
	 * @return Uri|UriInterface
	 */
	public function getUri(): Uri|UriInterface
	{
		return $this->uri;
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return RequestInterface
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
	{
		$class = clone $this;
		$class->uri = $uri;
		return $class;
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return string|null
	 * @throws Exception
	 */
	public function date(string $name, bool $required = false): ?string
	{
		$param = $this->post($name, null);
		if (empty($param)) {
			if ($required) {
				throw new Exception('Required ' . $name . ' is must.');
			}
			return $param;
		}
		return $param;
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return int|null
	 * @throws Exception
	 */
	public function timestamp(string $name, bool $required = false): ?int
	{
		$param = $this->post($name, null);
		if (empty($param)) {
			if ($required) {
				throw new Exception('Required ' . $name . ' is must.');
			}
			return $param;
		}
		return (int)$param;
	}
}
