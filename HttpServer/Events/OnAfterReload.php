<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kiri\Event;
use Kiri\Exception\ComponentException;
use Kiri\Kiri;
use Swoole\Server;

/**
 * Class OnAfterReload
 * @package HttpServer\Events
 */
class OnAfterReload extends Callback
{


	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		Event::trigger(Event::SERVER_AFTER_RELOAD, [$server]);
	}

}
