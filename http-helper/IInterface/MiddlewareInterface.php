<?php

declare(strict_types=1);

namespace Http\IInterface;


use Closure;
use Server\RequestInterface;

/**
 * Interface IMiddleware
 * @package Kiri\Kiri\Route
 */
interface MiddlewareInterface
{


	/**
	 * @param RequestInterface $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function onHandler(RequestInterface $request, Closure $next): mixed;

}
