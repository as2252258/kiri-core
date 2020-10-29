<?php

declare(strict_types=1);

namespace HttpServer\IInterface;


use HttpServer\Http\Request;

/**
 * Interface IMiddleware
 * @package Snowflake\Snowflake\Route
 */
interface Middleware
{

	public function handler(Request $request,\Closure $next);

}
