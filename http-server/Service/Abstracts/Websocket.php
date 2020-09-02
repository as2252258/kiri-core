<?php


namespace HttpServer\Service\Abstracts;


use HttpServer\IInterface\Service;
use Swoole\WebSocket\Server;

abstract class Websocket extends Server implements Service
{

	use \HttpServer\Service\Abstracts\Server;


}
