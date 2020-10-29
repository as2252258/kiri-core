<?php
declare(strict_types=1);


namespace HttpServer\IInterface;


use HttpServer\Http\Request;
use Closure;

interface Limits
{

	public function next(Request $request, Closure $closure);

}
