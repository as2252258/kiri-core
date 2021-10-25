<?php

namespace Http\Message;

use Psr\Http\Message\UriInterface;
use Swoole\Http\Request;


class Uri implements UriInterface
{


    protected string $scheme = '';
    protected string $host = '';
    protected string $path = '';
    protected string $fragment = '';
    protected int $port = 80;


    protected string $queryString = '';


    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }


    /**
     * @return string
     */
    public function getAuthority(): string
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @return string
     */
    public function getUserInfo(): string
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }


    /**
     * @return int|null
     */
    public function getPort(): ?int
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
        return $this->queryString;
    }


    /**
     * @return string
     */
    public function getFragment(): string
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @param string $scheme
     * @return $this|Uri
     */
    public function withScheme($scheme): UriInterface
    {
        $this->scheme = $scheme;
        return $this;
    }


    /**
     * @param string $user
     * @param null $password
     * @return Uri
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @param string $host
     * @return $this|Uri
     */
    public function withHost($host): UriInterface
    {
        $this->host = $host;
        return $this;
    }


    /**
     * @param int|null $port
     * @return $this|Uri
     */
    public function withPort($port): UriInterface
    {
        $this->port = $port;
        return $this;
    }


    /**
     * @param string $path
     * @return $this|Uri
     */
    public function withPath($path): UriInterface
    {
        $this->path = $path;
        return $this;
    }


    /**
     * @param string $query
     * @return $this|Uri
     */
    public function withQuery($query): UriInterface
    {
        $this->queryString = $query;
        return $this;
    }


    /**
     * @param string $fragment
     * @return Uri
     */
    public function withFragment($fragment): UriInterface
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
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
            $this->queryString, $this->fragment);
    }


    /**
     * @return int
     */
    public function getDefaultPort(): int
    {
        return $this->scheme == 'https' ? 443 : 80;
    }


    /**
     * @param Request $request
     * @return UriInterface
     */
    public static function parseUri(Request $request): UriInterface
    {
        $server = $request->server;
        $header = $request->header;
        $uri = new static();
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
