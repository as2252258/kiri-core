<?php
declare(strict_types=1);


namespace Kiri\Jwt;


use Annotation\Inject;
use Exception;
use Http\Message\ServerRequest;
use Kiri\Kiri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Http\Constrict\ResponseInterface;

/**
 * Class CoreMiddleware
 * @package Kiri\Kiri\Route
 * 跨域中间件
 */
class JWTAuthMiddleware implements MiddlewareInterface
{


	/** @var int */
	public int $zOrder = 0;


	#[Inject(ResponseInterface::class)]
	public ResponseInterface $response;


	/**
	 * @param ServerRequest $request
	 * @param RequestHandlerInterface $handler
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
	{
		$authorization = $request->getHeaderLine('Authorization');
		if (empty($authorization)) {
			return $this->response->json(['code' => 401, 'JWT voucher cannot be empty.']);
		}
		if (!str_starts_with($authorization, 'Bearer ')) {
			return $this->response->json(['code' => 401, 'JWT Voucher Format Error.']);
		}
		$authorization = str_replace('Bearer ', '', $authorization);
		$jwt = Kiri::app()->getJwt();
		if (!$jwt->validator($authorization)) {
			return $this->response->json(['code' => 401, 'JWT Validator fail.']);
		}
		return $handler->handle($request);
	}

}
