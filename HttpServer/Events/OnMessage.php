<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
            Event::trigger($this->getName($server, $frame), [$frame, $server]);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'websocket');
            if (!swoole()->isEstablished($frame->fd)) {
                return;
            }
            $server->send($frame->fd, $exception->getMessage());
        }
    }


    /**
     * @param $clientInfo
     * @return string
     */
    private function getName(Server $server, Frame $frame): string
    {
        $clientInfo = $server->getClientInfo($frame->fd);

        return 'listen ' . $clientInfo['server_port'] . ' ' . Event::SERVER_MESSAGE;
    }

}
