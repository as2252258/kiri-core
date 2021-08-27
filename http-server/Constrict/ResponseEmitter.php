<?php

namespace Server\Constrict;

use Annotation\Inject;
use Exception;
use Http\Context\Formatter\FileFormatter;
use Kiri\Exception\NotFindClassException;
use ReflectionException;
use Server\ResponseInterface;
use Swoole\Server;


/**
 *
 */
class ResponseEmitter implements Emitter
{


    /**
     * @var \Server\Constrict\DownloadEmitter
     */
    #[Inject(DownloadEmitter::class)]
    public DownloadEmitter $downloadEmitter;


    /**
     * @param \Swoole\Http\Response|\Swoole\Http2\Response $response
     * @param ResponseInterface $emitter
     * @throws NotFindClassException
     * @throws ReflectionException
     * @throws Exception
     */
    public function sender(mixed $response, ResponseInterface $emitter): void
    {
        if (!empty($this->headers) && is_array($this->headers)) {
            foreach ($this->headers as $name => $values) {
                $response->header($name, implode(';', $values));
            }
            $this->headers = [];
        }
        if (!empty($this->cookies) && is_array($this->cookies)) {
            foreach ($this->cookies as $name => $cookie) {
                $response->cookie($name, ...$cookie);
            }
            $this->cookies = [];
        }
        $response->setStatusCode($emitter->getStatusCode());
        $response->header('Run-Time', time());
        $response->end($emitter->getBody());

    }

}
