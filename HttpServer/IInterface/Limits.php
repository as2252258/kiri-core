<?php
declare(strict_types=1);


namespace HttpServer\IInterface;


use HttpServer\Http\Request;
use Closure;

interface Limits
{

	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function next(Request $request, Closure $closure): mixed;

}
