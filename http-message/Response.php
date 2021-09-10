<?php

namespace Http\Message;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;


class Response implements ResponseInterface
{


    use Message;


    protected int $statusCode = 200;


    protected string $reasonPhrase = '';


    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }


    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return $this|\Protocol\Message\Response
     */
    public function withStatus($code, $reasonPhrase = ''): static
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }


    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }


    /**
     * @return string|null
     */
    #[Pure] public function getAccessControlAllowOrigin(): ?string
    {
        return $this->getHeaderLine('Access-Control-Allow-Origin');
    }


    /**
     * @return string|null
     */
    #[Pure] public function getAccessControlAllowHeaders(): ?string
    {
        return $this->getHeaderLine('Access-Control-Allow-Headers');
    }


    /**
     * @return string|null
     */
    #[Pure] public function getAccessControlRequestMethod(): ?string
    {
        return $this->getHeaderLine('Access-Control-Request-Method');
    }
}
