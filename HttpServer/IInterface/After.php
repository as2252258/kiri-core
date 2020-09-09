<?php


namespace HttpServer\IInterface;


use Closure;
use HttpServer\Http\Request;

interface After
{


	/**
	 * @param Request $request
	 * @param $params
	 * @param Closure|null $next
	 * @return mixed
	 */
	public function onHandler(Request $request, $params, $next);

}
