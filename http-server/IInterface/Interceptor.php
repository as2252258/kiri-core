<?php


namespace HttpServer\IInterface;


use HttpServer\Http\Request;
use Closure;

interface Interceptor
{


	public function Interceptor(Request $request, Closure $closure);

}
