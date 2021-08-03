<?php


namespace Server;


use HttpServer\Http\Context;
use HttpServer\Http\Response as HttpResponse;


/**
 * Class Response
 * @package Server
 * @mixin \HttpServer\Http\Response
 */
class Response
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
