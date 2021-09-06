<?php

namespace Server\Constrict;

use Http\Context\Context;
use Kiri\Kiri;
use Server\Message\Request as RequestMessage;
use Server\RequestInterface;
use Server\Message\Response;
use Server\ResponseInterface;


/**
 * @mixin RequestMessage
 */
class Request implements RequestInterface
{


	/**
	 * @return RequestMessage
	 */
	private function __call__(): RequestMessage
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
	 * @return array<Request, Response>
	 */
	public static function create(\Swoole\Http\Request $request): array
	{
		Context::setContext(ResponseInterface::class, $response = new Response());

		Context::setContext(RequestMessage::class, RequestMessage::parseRequest($request));

		return [Kiri::getDi()->get(Request::class), $response];
	}
}
