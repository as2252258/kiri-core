<?php
declare(strict_types=1);


namespace Http\IInterface;


use Http\Http\Request;
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
