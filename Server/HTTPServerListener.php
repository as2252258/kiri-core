<?php

namespace Server;

use HttpServer\Route\Router;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class HTTPServerListener
{

    protected static mixed $_http;

    use ListenerHelper;

    private Router $router;

    /**
     * UDPServerListener constructor.
     * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array|null $settings
     * @throws \ReflectionException
     */
    public static function instance(mixed $server, string $host, int $port, int $mode, ?array $settings = [])
    {
        if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
            trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
        }
        $reflect = Snowflake::getDi()->getReflect(static::class)?->newInstance();
        static::$_http = $server->addlistener($host, $port, $mode);
        static::$_http->set($settings['settings'] ?? []);
        static::$_http->on('request', self::callback(Constant::REQUEST, $settings['events'], [$reflect, 'onRequest']));
        static::$_http->on('connect', static::callback(Constant::CONNECT, $settings['events'], [$reflect, 'onConnect']));
        static::$_http->on('close', static::callback(Constant::CLOSE, $settings['events'], [$reflect, 'onClose']));
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onConnect(Server $server, int $fd)
    {
        $server->confirm($fd);
    }


    /**
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        if (!$response->isWritable()) {
            return;
        }
        $response->status(200);
        $response->end('');
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onClose(Server $server, int $fd)
    {
    }

}
