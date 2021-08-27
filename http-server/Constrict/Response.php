<?php


namespace Server\Constrict;


use Http\Context\Context;
use Server\ResponseInterface;
use Server\Message\Response as Psr7Response;


/**
 * Class Response
 * @package Server
 * @mixin Psr7Response
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
		if (!Context::hasContext(Psr7Response::class)) {
			$context = Context::setContext(Psr7Response::class, new Psr7Response());
		} else {
			$context = Context::getContext(Psr7Response::class);
		}
		return $context->{$name}(...$args);
	}

}
