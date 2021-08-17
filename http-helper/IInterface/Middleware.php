<?php

declare(strict_types=1);

namespace Http\IInterface;


use Closure;
use Http\Context\Request;

/**
 * Interface IMiddleware
 * @package Kiri\Kiri\Route
 */
interface Middleware
{


	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function onHandler(Request $request, Closure $next): mixed;

}
