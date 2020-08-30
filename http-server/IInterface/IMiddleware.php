<?php


namespace HttpServer\IInterface;


use HttpServer\Http\Request;

/**
 * Interface IMiddleware
 * @package BeReborn\Route
 */
interface IMiddleware
{

	public function handler(Request $request,\Closure $next);

}
