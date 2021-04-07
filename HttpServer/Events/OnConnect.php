<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnConnect
 * @package HttpServer\Events
 */
class OnConnect extends Callback
{


    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     * @throws Exception
     */
    public function onHandler(Server $server, int $fd, int $reactorId)
    {
        $event = Snowflake::app()->getEvent();

        $clientInfo = $server->getClientInfo($fd);

        try {
            $name = 'listen ' . $clientInfo['server_port'] . ' ' . Event::SERVER_CONNECT;

            $event->trigger($name, [$server, $fd, $reactorId]);
        } catch (\Throwable $throwable) {
            $this->addError($throwable, 'connect');
        }
        fire(Event::SYSTEM_RESOURCE_RELEASES);
	    logger_insert();
    }


}
