<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnClose
 * @package HttpServer\Events
 *
 */
class OnClose extends Callback
{


    /**
     * @param Server $server
     * @param int $fd
     * @throws Exception
     */
    public function onHandler(Server $server, int $fd)
    {
        try {
            defer(function () {
                fire(Event::SYSTEM_RESOURCE_RELEASES);
            });
			$clientInfo = $server->getClientInfo($fd);
			if (!Event::exists(($name = $this->getName($clientInfo)))) {
                return;
            }
			Event::trigger($name, [$server, $fd]);
		} catch (\Throwable $exception) {
            $this->addError($exception, 'throwable');
        }
    }


    /**
     * @param $server_port
     * @return string
     */
    private function getName($server_port): string
    {
        return 'listen ' . $server_port['server_port'] . ' ' . Event::SERVER_CLIENT_CLOSE;
    }

}
