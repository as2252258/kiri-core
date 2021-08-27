<?php

namespace Server;


use Server\Constrict\Response;
use Server\Constrict\Response as CResponse;
use Server\Message\Stream;
use Throwable;

/**
 *
 */
class ExceptionHandlerDispatcher implements ExceptionHandlerInterface
{


    /**
     * @param Throwable $exception
     * @param CResponse $response
     * @return ResponseInterface
     */
    public function emit(Throwable $exception, Response $response): ResponseInterface
    {
        if ($exception->getCode() == 404) {
            return $response->withBody(new Stream($exception->getMessage()))
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(404);
        }
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();
        return $response->withBody(new Stream(jTraceEx($exception, null, true)))
            ->withHeader('Content-Type', 'text/html')
            ->withStatus($code);
    }

}
