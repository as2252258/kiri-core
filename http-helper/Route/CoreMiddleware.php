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
		$response->addHeader('Access-Control-Allow-Origin', '*');
		$response->addHeader('Access-Control-Allow-Headers', $request->getHeaderLine('Access-Control-Request-Headers'));
		$response->addHeader('Access-Control-Request-Method', $request->getHeaderLine('Access-Control-Request-Method'));

		return $next($request);
	}

}
