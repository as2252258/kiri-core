<?php
declare(strict_types=1);


namespace Kiri\Jwt;


use Closure;
use Exception;
use Http\Route\MiddlewareAbstracts;
use Kiri\Kiri;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class JWTAuthMiddleware extends MiddlewareAbstracts
{


	/** @var int */
	public int $zOrder = 0;


	/**
	 * @param RequestInterface $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(RequestInterface $request, Closure $next): mixed
	{
		$authorization = $request->getHeaderLine('Authorization');
		if (empty($authorization)) {
			throw new JWTAuthTokenException('JWT voucher cannot be empty.');
		}
		if (!str_starts_with($authorization, 'Bearer ')) {
			throw new JWTAuthTokenException('JWT Voucher Format Error.');
		}
		$authorization = str_replace('Bearer ', '', $authorization);
		$jwt = Kiri::app()->getJwt();
		if (!$jwt->validator($authorization)) {
			throw new JWTAuthTokenException('JWT Validator fail.');
		}
		return $next($request);
	}

}
