<?php
declare(strict_types=1);


namespace Snowflake\Jwt;


use Closure;
use Exception;
use HttpServer\Http\Request;
use HttpServer\IInterface\Middleware;
use Server\RequestInterface;
use Snowflake\Snowflake;

/**
 * Class CoreMiddleware
 * @package Snowflake\Snowflake\Route
 * 跨域中间件
 */
class JWTAuthMiddleware implements Middleware
{


	/** @var int */
	public int $zOrder = 0;


	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(RequestInterface $request, Closure $next): mixed
	{
		$authorization = $request->header('Authorization');
		if (empty($authorization)) {
			throw new JWTAuthTokenException('JWT voucher cannot be empty.');
		}
		if (!str_starts_with($authorization, 'Bearer ')) {
			throw new JWTAuthTokenException('JWT Voucher Format Error.');
		}
		$authorization = str_replace('Bearer ', '', $authorization);
		$jwt = Snowflake::app()->getJwt();
		if (!$jwt->validator($authorization)) {
			throw new JWTAuthTokenException('JWT Validator fail.');
		}
		return $next($request);
	}

}
