<?php
declare(strict_types=1);


namespace HttpServer\IInterface;


use Closure;
use HttpServer\Http\Request;

interface After
{


	/**
	 * @param Request $request
	 * @param $params
	 * @return mixed
	 */
	public function onHandler(Request $request, $params): mixed;

}
