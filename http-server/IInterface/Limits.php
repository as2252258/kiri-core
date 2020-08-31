<?php


namespace HttpServer\IInterface;


use HttpServer\Http\Request;
use Closure;

interface Limits
{

	public function next(Request $request, Closure $closure);

}
