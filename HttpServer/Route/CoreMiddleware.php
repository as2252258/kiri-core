<?php


namespace HttpServer\Route;


use Closure;
use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Http\Response;

/**
 * Class CoreMiddleware
 * @package Snowflake\Snowflake\Route
 * 跨域中间件
 */
class CoreMiddleware implements \HttpServer\IInterface\Middleware
{

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function handler(Request $request, Closure $next)
	{
		$headers = $request->headers;

		/** @var Response $response */
		$response = Context::getContext('response');
		$response->addHeader('Access-Control-Allow-Origin', '*');
		$response->addHeader('Access-Control-Allow-Headers', $headers->get('access-control-request-headers'));
		$response->addHeader('Access-Control-Request-Method', $headers->get('access-control-request-method'));

		return $next($request);
	}

}
