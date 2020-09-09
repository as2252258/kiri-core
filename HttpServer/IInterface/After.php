<?php


namespace HttpServer\IInterface;


use Closure;

interface After
{


	/**
	 * @param $params
	 * @param Closure|null $next
	 * @return mixed
	 */
	public function onHandler($params, $next);

}
