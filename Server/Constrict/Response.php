<?php


namespace Server\Constrict;


use HttpServer\Http\Context;
use HttpServer\Http\Response as HttpResponse;
use Server\ResponseInterface;


/**
 * Class Response
 * @package Server
 * @mixin HttpResponse
 */
class Response implements ResponseInterface
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';

	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return Context::getContext(HttpResponse::class)->{$name}(...$args);
	}

}
