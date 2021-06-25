<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class OnMessage
 * @package HttpServer\Events
 */
class OnMessage extends Callback
{

	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @throws
	 */
	public function onHandler(Server $server, Frame $frame)
	{
		try {
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
			if ($frame->opcode === 0x08) {
				return;
			}

			$clientInfo = $this->getName($server->getClientInfo($frame->fd), Event::SERVER_MESSAGE);

			Event::trigger($clientInfo, [$frame, $server]);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'websocket');
			if (!swoole()->isEstablished($frame->fd)) {
				return;
			}
			$server->send($frame->fd, $exception->getMessage());
		}
	}

}
