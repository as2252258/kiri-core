<?php
declare(strict_types=1);

namespace HttpServer\Abstracts;



use Swoole\Coroutine;

abstract class BaseContext
{
	protected static array $pool = [];
}
