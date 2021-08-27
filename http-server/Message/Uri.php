<?php

namespace Server\Message;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\UriInterface;


class Uri implements UriInterface
{


	public string $scheme = '';


	public string $host = '';

	public int $port = 80;


	public string $path = '';


	public string $query = '';


	public string $fragment = '';


	public string $username = '';


	public string $password = '';


	private array $_explode = [];


	/**
	 * @return string[]
	 */
	public function getExplode(): array
	{
		if ($this->path == '/' || $this->path == '') {
			return [''];
		}
		if (empty($this->_explode)) {
			$this->_explode = array_filter(explode('/', $this->path));
		}
		return $this->_explode;
	}


	/**
	 * @return string
	 */
	public function getScheme(): string
	{
		return $this->scheme;
	}

	public function getAuthority()
	{
		// TODO: Implement getAuthority() method.
	}

	public function getUserInfo()
	{
		// TODO: Implement getUserInfo() method.
	}


	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}


	/**
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port;
	}


	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getQuery(): string
	{
		return $this->query;
	}


	/**
	 * @return string
	 */
	public function getFragment(): string
	{
		return $this->fragment;
	}


	/**
	 * @param string $scheme
	 * @return UriInterface
	 */
	public function withScheme($scheme): UriInterface
	{
		$class = clone $this;
		$class->scheme = $scheme;
		return $class;
	}

	/**
	 * @param string $user
	 * @param null $password
	 * @return $this
	 */
	public function withUserInfo($user, $password = null): UriInterface
	{
		$class = clone $this;
		$class->username = $user;
		$class->password = $password;
		return $class;
	}


	/**
	 * @param string $host
	 * @return UriInterface
	 */
	public function withHost($host): UriInterface
	{
		$class = clone $this;
		$class->host = $host;
		return $class;
	}


	/**
	 * @return int
	 */
	public function getDefaultPort(): int
	{
		return 80;
	}


	/**
	 * @param int|null $port
	 * @return UriInterface
	 */
	public function withPort($port): UriInterface
	{
		$class = clone $this;
		$class->port = $port;
		return $class;
	}


	/**
	 * @param string $path
	 * @return UriInterface
	 */
	public function withPath($path): UriInterface
	{
		$class = clone $this;
		$class->path = $path;
		return $class;
	}


	/**
	 * @param string $query
	 * @return UriInterface
	 */
	public function withQuery($query): UriInterface
	{
		$class = clone $this;
		$class->query = $query;
		return $class;
	}


	/**
	 * @param string $fragment
	 * @return UriInterface
	 */
	public function withFragment($fragment): UriInterface
	{
		$class = clone $this;
		$class->fragment = $fragment;
		return $class;
	}


	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('%s://%s:%d%s?%s#%s', $this->scheme, $this->host, $this->port,
			$this->path, $this->query, $this->fragment);
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return UriInterface
	 */
	#[Pure] public static function parseUri(\Swoole\Http\Request $request): UriInterface
	{
		$server = $request->server;
		$header = $request->header;
		$uri = new Uri();
		$uri = $uri->withScheme(!empty($server['https']) && $server['https'] !== 'off' ? 'https' : 'http');

		$hasPort = false;
		if (isset($server['http_host'])) {
			$hostHeaderParts = explode(':', $server['http_host']);
			$uri = $uri->withHost($hostHeaderParts[0]);
			if (isset($hostHeaderParts[1])) {
				$hasPort = true;
				$uri = $uri->withPort($hostHeaderParts[1]);
			}
		} elseif (isset($server['server_name'])) {
			$uri = $uri->withHost($server['server_name']);
		} elseif (isset($server['server_addr'])) {
			$uri = $uri->withHost($server['server_addr']);
		} elseif (isset($header['host'])) {
			$hasPort = true;
			if (strpos($header['host'], ':')) {
				[$host, $port] = explode(':', $header['host'], 2);
				if ($port != $uri->getDefaultPort()) {
					$uri = $uri->withPort($port);
				}
			} else {
				$host = $header['host'];
			}

			$uri = $uri->withHost($host);
		}

		if (!$hasPort && isset($server['server_port'])) {
			$uri = $uri->withPort($server['server_port']);
		}

		$hasQuery = false;
		if (isset($server['request_uri'])) {
			$requestUriParts = explode('?', $server['request_uri']);
			$uri = $uri->withPath($requestUriParts[0]);
			if (isset($requestUriParts[1])) {
				$hasQuery = true;
				$uri = $uri->withQuery($requestUriParts[1]);
			}
		}

		if (!$hasQuery && isset($server['query_string'])) {
			$uri = $uri->withQuery($server['query_string']);
		}

		return $uri;
	}
}
