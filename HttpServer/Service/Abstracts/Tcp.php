<?php
declare(strict_types=1);

namespace HttpServer\Service\Abstracts;


use Closure;
use HttpServer\IInterface\Service;
use Swoole\Server;

abstract class Tcp extends Server implements Service
{

	use \HttpServer\Service\Abstracts\Server;

}
