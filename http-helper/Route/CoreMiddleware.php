<?php
declare(strict_types=1);


namespace Http\Route;


use Closure;
use Exception;
use Http\Context\Context;
use Http\Context\Request;
use Http\Context\Response;
use Http\IInterface\MiddlewareInterface;
use Server\RequestInterface;
use Kiri\Kiri;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class CoreMiddleware extends MiddlewareAbstracts
{




	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(RequestInterface $request, Closure $next): mixed
	{
		/** @var Response $response */
		$response = \response();
		$response->addHeader('Access-Control-Allow-Origin', '*');
		$response->addHeader('Access-Control-Allow-Headers', $request->header('access-control-request-headers'));
		$response->addHeader('Access-Control-Request-Method', $request->header('access-control-request-method'));

		return $next($request);
	}

}
