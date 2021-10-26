<?php
declare(strict_types=1);

namespace Kiri\Abstracts;



use Swoole\Coroutine;

abstract class BaseContext
{
	protected static array $pool = [];
}
