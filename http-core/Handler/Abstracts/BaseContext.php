<?php
declare(strict_types=1);

namespace Http\Handler\Abstracts;



use Swoole\Coroutine;

abstract class BaseContext
{
	protected static array $pool = [];
}
