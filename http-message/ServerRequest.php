<?php

namespace Protocol\Message;

use Http\Context\Context;
use Kiri\Core\Xml;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;


/**
 *
 */
class ServerRequest extends Request implements ServerRequestInterface
{


    /**
     * @var mixed
     */
    protected ?array $parsedBody = null;


    /**
     * @var array|null
     */
    protected ?array $serverParams;


    /**
     * @var array|null
     */
    protected ?array $cookieParams = [];

    /**
     * @var array|null
     */
    protected ?array $queryParams;

    /**
     * @var array|null
     */
    protected ?array $uploadedFiles;


    protected \Swoole\Http\Request $serverTarget;


    /**
     * @param array $server
     * @return static
     */
    public function withServerParams(array $server): static
    {
        $this->serverParams = $server;
        return $this;
    }

    /**
     * @param \Swoole\Http\Request $server
     * @return static
     */
    public function withServerTarget(\Swoole\Http\Request $server): static
    {
        $this->serverTarget = $server;
        return $this;
    }


    /**
     * @param \Swoole\Http\Request $request
     * @return static
     */
    public static function createServerRequest(\Swoole\Http\Request $request): static
    {
        return (new static())->withServerParams($request->server)
            ->withServerTarget($request)
            ->withCookieParams($request->cookie)
            ->withUri(Uri::parseUri($request))
            ->withQueryParams($request->get ?? [])
            ->withUploadedFiles($request->files ?? [])
            ->withMethod($request->getMethod())
            ->parseRequestHeaders($request)
            ->withParsedBody(function (StreamInterface $stream, ?array $posts) {
                try {
                    $content = $stream->getContents();
                    if (!empty($content)) {
                        return Parse::data($content, $this->getContentType());
                    }
                    return $posts;
                } catch (\Throwable $throwable) {
                    return $posts;
                }
            });
    }


    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function post(string $name, mixed $default = null): mixed
    {
        return $this->parsedBody[$name] ?? $default;
    }


    /**
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function query(string $name, ?string $default = null): ?string
    {
        return $this->queryParams[$name] ?? $default;
    }


    /**
     * @param string $name
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    public function file(string $name): ?UploadedFileInterface
    {
        if (isset($this->parsedBody[$name])) {
            $files = $this->parsedBody[$name];
            return new Uploaded($files['tmp_name'], $files['name'], $files['type'], $files['size'], $files['error']);
        }
        return null;
    }


    /**
     * @return null|array
     */
    public function getServerParams(): ?array
    {
        return $this->serverParams;
    }


    /**
     * @return null|array
     */
    public function getCookieParams(): ?array
    {
        return $this->cookieParams;
    }


    /**
     * @param array $cookies
     * @return $this|ServerRequest
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $this->cookieParams = $cookies;
        return $this;
    }


    /**
     * @return array|null
     */
    public function getQueryParams(): ?array
    {
        return $this->queryParams;
    }


    /**
     * @param array $query
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $this->queryParams = $query;
        return $this;
    }


    /**
     * @return array|null
     */
    public function getUploadedFiles(): ?array
    {
        return $this->uploadedFiles;
    }


    /**
     * @param array $uploadedFiles
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }


    /**
     * @return array|object|null
     */
    public function getParsedBody(): object|array|null
    {
        if (empty($this->parsedBody)) {
            $callback = Context::getContext('with.parsed.body.callback');

            $this->parsedBody = $callback($this->getBody());
        }
        return $this->parsedBody;
    }


    /**
     * @param array|object|null $data
     * @return ServerRequestInterface
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        Context::setContext('with.parsed.body.callback', $data);
        return $this;
    }


    /**
     * @return array
     */
    public function getAttributes(): array
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getAttribute($name, $default = null): mixed
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @param string $name
     * @param mixed $value
     * @return ServerRequestInterface
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }


    /**
     * @param string $name
     * @return ServerRequestInterface
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        throw new \BadMethodCallException('Not Accomplish Method.');
    }
}
