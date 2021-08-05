<?php

namespace Server\Constrict;

use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request as HttpResponse;
use HttpServer\Http\Response;
use ReflectionException;
use Server\RequestInterface;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * @mixin HttpResponse
 */
class Request implements RequestInterface
{


	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return Context::getContext(HttpResponse::class)->{$name}(...$args);
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return Request
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public static function create(\Swoole\Http\Request $request): RequestInterface
	{
		$sRequest = new HttpResponse();
		$sRequest->setUri($request->header['request_uri']);
		$sRequest->setClientId($request->fd);

		$sRequest->headers = new HttpHeaders();
		$sRequest->headers->setHeaders(array_merge($request->header, $request->server));

		$sRequest->params = new HttpParams();
		$sRequest->params->setRawContent($request->rawContent(), $sRequest->headers->getContentType());
		$sRequest->params->setFiles($request->files);
		$sRequest->params->setPosts($request->post);
		$sRequest->params->setGets($request->get);

		Context::setContext(Request::class, $sRequest);

		Context::setContext(Response::class, new Response());

		return Snowflake::getDi()->get(Request::class);
	}
}
