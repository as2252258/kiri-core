<?php

namespace Server\Constrict;

use Http\Context\Context;
use Http\Context\Response;
use Kiri\Kiri;
use Server\Message\Request as RequestMessage;
use Server\RequestInterface;


/**
 * @mixin RequestMessage
 */
class Request implements RequestInterface
{


	/**
	 * @return RequestInterface
	 */
	private function __call__(): RequestInterface
	{
		return Context::getContext(RequestMessage::class, new RequestMessage());
	}


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
	 * @param $name
	 * @return mixed
	 */
	public function __get($name): mixed
	{
		// TODO: Change the autogenerated stub
		return $this->__call__()->{$name};
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return Request
	 */
	public static function create(\Swoole\Http\Request $request): RequestInterface
	{
		Context::setContext(Response::class, new Response());

		Context::setContext(RequestMessage::class, RequestMessage::parseRequest($request));

		return Kiri::getDi()->get(Request::class);
	}
}
