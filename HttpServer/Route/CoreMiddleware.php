<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Closure;
use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\IInterface\Middleware;
use Server\RequestInterface;
use Kiri\Kiri;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class CoreMiddleware implements Middleware
{


	/** @var int  */
	public int $zOrder = 0;


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
