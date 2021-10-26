<?php

namespace Http\Message;

use Kiri\Context;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;


/**
 *
 */
class ServerRequest extends Request implements ServerRequestInterface
{


    const PARSE_BODY = 'with.parsed.body.callback';


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
     * @return static|ServerRequestInterface
     * @throws \Exception
     */
    public static function createServerRequest(\Swoole\Http\Request $request): static|ServerRequestInterface
    {
        $serverRequest = new ServerRequest();
        $serverRequest->withData($request->getData());
        $serverRequest->withServerParams($request->server);
        $serverRequest->withServerTarget($request);
        $serverRequest->withCookieParams($request->cookie);
        $serverRequest->withUri(Uri::parseUri($request));
        $serverRequest->withQueryParams($request->get ?? []);
        $serverRequest->withUploadedFiles($request->files ?? []);
        $serverRequest->withMethod($request->getMethod());
        $serverRequest->withParsedBody($request->post);
        return $serverRequest;
    }


    /**
     * @return null|array
     */
    public function getServerParams(): ?array
    {
        return $this->serverParams;
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
     * @return ServerRequestInterface
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
     * @return ServerRequestInterface
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
            $callback = Context::getContext(self::PARSE_BODY);

            $this->parsedBody = $callback($this->getBody(), $this->serverTarget->post);
        }
        return $this->parsedBody;
    }


    /**
     * @param array|object|null $data
     * @return ServerRequestInterface
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        $functions = function (StreamInterface $stream) use ($data) {
            $content = Parse::data($stream->getContents());
            if (!empty($content)) {
                return $content;
            }
            return $data;
        };
        Context::setContext(self::PARSE_BODY, $functions);
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
