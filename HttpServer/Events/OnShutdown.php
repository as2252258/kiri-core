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
 * Class OnShutdown
 * @package HttpServer\Events
 */
class OnShutdown extends Callback
{

	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		$this->debug('server shutdown~');

		$this->system_mail('server shutdown~');

		fire(Event::SERVER_SHUTDOWN);
	}

}
