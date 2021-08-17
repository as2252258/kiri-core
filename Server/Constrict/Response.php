<?php


namespace Server\Constrict;


use Http\Http\Context;
use Http\Http\Response as HttpResponse;
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
	const FILE = 'file';

	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (!Context::hasContext(HttpResponse::class)) {
			$context = Context::setContext(HttpResponse::class, new HttpResponse());
		} else {
			$context = Context::getContext(HttpResponse::class);
		}
		return $context->{$name}(...$args);
	}

}
