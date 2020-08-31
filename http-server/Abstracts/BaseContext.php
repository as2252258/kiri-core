<?php


namespace HttpServer\Abstracts;



use Swoole\Coroutine;

abstract class BaseContext
{
	protected static $pool = [];
}
