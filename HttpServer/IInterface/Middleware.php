<?php

declare(strict_types=1);

namespace HttpServer\IInterface;


use Closure;
use HttpServer\Http\Request;

/**
 * Interface IMiddleware
 * @package Snowflake\Snowflake\Route
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
