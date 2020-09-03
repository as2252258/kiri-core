<?php


namespace HttpServer\Service\Abstracts;


use Closure;
use HttpServer\IInterface\Service;
use Swoole\Server;

abstract class Tcp extends Server implements Service
{

	use \HttpServer\Service\Abstracts\Server;

	/** @var Closure|array */
	public $unpack;


	/** @var Closure|array */
	public $pack;


}
