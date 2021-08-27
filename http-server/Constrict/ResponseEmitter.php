<?php

namespace Server\Constrict;

use Annotation\Inject;
use Exception;
use Kiri\Exception\NotFindClassException;
use ReflectionException;
use Psr\Http\Message\ResponseInterface;
use Server\RequestInterface;
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
     * @var \Server\RequestInterface
     */
    #[Inject(RequestInterface::class)]
    public RequestInterface $request;


    /**
     * @param \Swoole\Http\Response|\Swoole\Http2\Response $response
     * @param ResponseInterface|\Server\Message\Response $emitter
     * @throws NotFindClassException
     * @throws ReflectionException
     * @throws Exception
     */
    public function sender(mixed $response, ResponseInterface $emitter): void
    {
        if (!empty($emitter->getHeaders()) && is_array($emitter->getHeaders())) {
            foreach ($emitter->getHeaders() as $name => $values) {
                $response->header($name, implode(';', $values));
            }
        }
        if (!empty($emitter->getCookies()) && is_array($emitter->getCookies())) {
            foreach ($emitter->getCookies() as $name => $cookie) {
                $response->cookie($name, ...$cookie);
            }
        }
        $response->setStatusCode($emitter->getStatusCode());
        $response->header('Run-Time', sprintf('%.5f', $this->getRunTime()));
        $response->end($emitter->getBody());
    }


    /**
     * @return float
     */
    private function getRunTime(): float
    {
        return microtime(true) - $this->request->getStartTime();
    }

}
