<?php
declare(strict_types=1);


namespace Http\Route;


use Closure;
use Exception;
use Http\Context\Response;
use Server\RequestInterface;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class CoreMiddleware extends MiddlewareAbstracts
{


	/**
	 * @param RequestInterface $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(RequestInterface $request, Closure $next): mixed
	{
		/** @var Response $response */
		$response = \response();
		$response->withAccessControlAllowOrigin('*')
			->withAccessControlRequestMethod($request->getAccessControlRequestMethod())
			->withAccessControlAllowHeaders($request->getAccessControlAllowHeaders());

		return $next($request);
	}

}
