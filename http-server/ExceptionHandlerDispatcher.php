<?php

namespace Server;


use Server\Constrict\Response;
use Protocol\Message\Response as CResponse;
use Throwable;
use Protocol\Message\Stream;
use Server\Constrict\ResponseInterface;


/**
 *
 */
class ExceptionHandlerDispatcher implements ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param Response $response
	 * @return ResponseInterface
	 */
    public function emit(Throwable $exception, Response $response): ResponseInterface
    {
        if ($exception->getCode() == 404) {
            return $response->withBody(new Stream($exception->getMessage()))
                ->withContentType(CResponse::CONTENT_TYPE_HTML)
                ->withStatus(404);
        }
        $code = $exception->getCode() == 0 ? 500 : $exception->getCode();
        return $response->withBody(new Stream(jTraceEx($exception, null, true)))
            ->withContentType(CResponse::CONTENT_TYPE_HTML)
            ->withStatus($code);
    }

}
