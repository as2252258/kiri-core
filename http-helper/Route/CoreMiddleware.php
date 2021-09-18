<?php
declare(strict_types=1);


namespace Http\Route;


use Closure;
use Exception;
use Http\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Server\Constrict\RequestInterface;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class CoreMiddleware implements MiddlewareInterface
{


	/**
	 * @param ServerRequest $request
	 * @param RequestHandlerInterface $handler
	 * @return mixed
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = \response();
		$response->withAccessControlAllowOrigin('*')
			->withAccessControlRequestMethod($request->getAccessControlRequestMethod())
			->withAccessControlAllowHeaders($request->getAccessControlAllowHeaders());
		return $handler->handle($request);
	}

}
