<?php

namespace HttpMessage;

use Psr\Http\Message\UriInterface;


/**
 *
 */
class Uri implements UriInterface
{

	/**
	 * @var string
	 */
	public string $path = '';

	/**
	 * @var string
	 */
	public string $host = '';

	/**
	 * @var int
	 */
	public int $port = 0;


	/**
	 * @var string
	 */
	public string $query = '';


	/**
	 * @var string
	 */
	public string $scheme = '';


	/**
	 * @return string
	 */
	public function getScheme(): string
	{
		// TODO: Implement getScheme() method.
		return $this->scheme;
	}


	/**
	 * @return string
	 */
	public function getAuthority(): string
	{
		// TODO: Implement getAuthority() method.

		return '';
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
		// TODO: Implement getHost() method.
		return $this->host;
	}


	/**
	 * @return int
	 */
	public function getPort(): int
	{
		// TODO: Implement getPort() method.
		return $this->port;
	}


	/**
	 * @return string
	 */
	public function getPath(): string
	{
		// TODO: Implement getPath() method.
		return $this->path;
	}


	/**
	 * @return string
	 */
	public function getQuery(): string
	{
		// TODO: Implement getQuery() method.
		return $this->query;
	}


	/**
	 * @return string
	 */
	public function getFragment(): string
	{
		// TODO: Implement getFragment() method.
		return $this->path;
	}


	/**
	 * @param string $scheme
	 * @return Uri|void
	 */
	public function withScheme($scheme): string
	{
		// TODO: Implement withScheme() method.

	}

	public function withUserInfo($user, $password = null)
	{
		// TODO: Implement withUserInfo() method.
	}

	public function withHost($host)
	{
		// TODO: Implement withHost() method.
	}

	public function withPort($port)
	{
		// TODO: Implement withPort() method.
	}

	public function withPath($path)
	{
		// TODO: Implement withPath() method.
	}

	public function withQuery($query)
	{
		// TODO: Implement withQuery() method.
	}

	public function withFragment($fragment)
	{
		// TODO: Implement withFragment() method.
	}

	public function __toString()
	{
		// TODO: Implement __toString() method.
	}
}
