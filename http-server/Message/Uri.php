<?php

namespace Server\Message;

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


	private mixed $authority;


	/**
	 * @return string[]
	 */
	public function getExplode(): array
	{
		if ($this->path == '/' || $this->path == '') {
			return ['/'];
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


	/**
	 * @return mixed
	 */
	public function getAuthority(): mixed
	{
		return $this->authority;
	}


	/**
	 * @param $authority
	 */
	public function setAuthority($authority)
	{
		$this->authority = $authority;
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
		$this->scheme = $scheme;
		return $this;
	}

	/**
	 * @param string $user
	 * @param null $password
	 * @return $this
	 */
	public function withUserInfo($user, $password = null): UriInterface
	{
		$this->username = $user;
		$this->password = $password;
		return $this;
	}


	/**
	 * @param string $host
	 * @return UriInterface
	 */
	public function withHost($host): UriInterface
	{
		$this->host = $host;
		return $this;
	}


	/**
	 * @return int
	 */
	public function getDefaultPort(): int
	{
		return $this->scheme == 'https' ? 443 : 80;
	}


	/**
	 * @param int|null $port
	 * @return UriInterface
	 */
	public function withPort($port): UriInterface
	{
		$this->port = $port;
		return $this;
	}


	/**
	 * @param string $path
	 * @return UriInterface
	 */
	public function withPath($path): UriInterface
	{
		$this->path = $path;
		return $this;
	}


	/**
	 * @param string $query
	 * @return UriInterface
	 */
	public function withQuery($query): UriInterface
	{
		$this->query = $query;
		return $this;
	}


	/**
	 * @param string $fragment
	 * @return UriInterface
	 */
	public function withFragment($fragment): UriInterface
	{
		$this->fragment = $fragment;
		return $this;
	}


	/**
	 * @return string
	 */
	public function __toString(): string
	{
		$domain = sprintf('%s://%s', $this->scheme, $this->host);
		if (!in_array($this->port, [80, 443])) {
			$domain .= ':' . $this->port;
		}
		if (empty($this->query) && empty($this->fragment)) {
			return $domain . $this->path;
		}
		return sprintf('%s?%s#%s', $domain . $this->path,
			$this->query, $this->fragment);
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return UriInterface
	 */
	public static function parseUri(\Swoole\Http\Request $request): UriInterface
	{
		$server = $request->server;
		$header = $request->header;
		$uri = new Uri();
		$uri = $uri->withScheme(!empty($server['https']) && $server['https'] !== 'off' ? 'https' : 'http');
		if (isset($request->header['x-forwarded-proto'])) {
			$uri->withScheme($request->header['x-forwarded-proto'])->withPort(443);
		}

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
