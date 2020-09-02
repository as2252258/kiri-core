<?php


namespace HttpServer\Service\Abstracts;


use HttpServer\IInterface\Service;
use Swoole\Http\Server;

abstract class Http extends Server implements Service
{

	use \HttpServer\Service\Abstracts\Server;



}
