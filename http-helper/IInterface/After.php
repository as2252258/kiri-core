<?php
declare(strict_types=1);


namespace Http\IInterface;


use Closure;
use Http\Http\Request;

interface After
{


	/**
	 * @param Request $request
	 * @param $params
	 * @return mixed
	 */
	public function onHandler(Request $request, $params): void;

}
