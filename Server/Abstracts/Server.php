<?php


namespace Server\Abstracts;


use Closure;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Event;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Server\Port;


/**
 * Class Server
 * @package Server\Abstracts
 */
abstract class Server
{


	/**
	 * Server constructor.
	 * @throws Exception
	 */
	public function __construct(protected \Swoole\Server|Port $server)
	{
	}

}
