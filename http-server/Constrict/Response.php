<?php


namespace Server\Constrict;


use Http\Context\Context;
use Server\Message\Response as Psr7Response;
use Server\ResponseInterface;


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
		return $this->__call__()->{$name}(...$args);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->__call__()->{$name};
	}


	/**
	 * @return Psr7Response
	 */
	public function __call__(): Psr7Response
	{
		return Context::getContext(ResponseInterface::class, new Psr7Response());
	}
}
