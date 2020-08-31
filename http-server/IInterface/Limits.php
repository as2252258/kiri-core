<?php


namespace HttpServer\IInterface;


use HttpServer\Http\Request;
use Closure;

interface Limits
{

	public function request(Request $request, Closure $closure);

}
