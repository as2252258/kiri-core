<?php

namespace Server\Constrict;

use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request as HttpResponse;
use HttpServer\Http\Response;
use ReflectionException;
use Server\RequestInterface;
use Snowflake\Abstracts\BaseObject;
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
	    $request = Context::getContext(HttpResponse::class);
	    if (property_exists($request, $name)){
	        return $request->{$name};
        }
		return $request->{$name}(...$args);
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return Request
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public static function create(\Swoole\Http\Request $request): RequestInterface
	{
        Context::setContext(Response::class, new Response());

        $sRequest = new HttpResponse();

		$sRequest->headers = new HttpHeaders();
		$sRequest->headers->setHeaders(array_merge($request->header, $request->server));

        $sRequest->setUri($sRequest->headers->getRequestUri());
        $sRequest->setClientId($request->fd);

        $sRequest->params = new HttpParams();
		$sRequest->params->setRawContent($request->rawContent(), $sRequest->headers->getContentType());
		$sRequest->params->setFiles($request->files);
		$sRequest->params->setPosts($request->post);
		$sRequest->params->setGets($request->get);

		Context::setContext(HttpResponse::class, $sRequest);

		return Snowflake::getDi()->get(Request::class);
	}
}
