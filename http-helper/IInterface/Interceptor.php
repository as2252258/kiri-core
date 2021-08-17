<?php
declare(strict_types=1);


namespace Http\IInterface;


use Http\Context\Request;
use Closure;

interface Interceptor
{


	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function Interceptor(Request $request, Closure $closure): mixed;

}
