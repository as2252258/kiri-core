<?php

namespace Server;

use Exception;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;


/**
 * Class WebSocketServerListener
 * @package HttpServer\Service
 */
class WebSocketServerListener
{

    protected static Server\Port $_http;

    use ListenerHelper;

    /**
     * UDPServerListener constructor.
     * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array|null $settings
     */
    public static function instance(mixed $server, string $host, int $port, int $mode, ?array $settings = [])
    {
        if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
            trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
        }
        static::$_http = $server->addlistener($host, $port, $mode);
        static::$_http->set($settings['settings'] ?? []);
        static::$_http->on('connect', static::callback(Constant::CONNECT, $settings['events'], [new static(), 'onConnect']));
        static::$_http->on('handshake', static::callback(Constant::HANDSHAKE, $settings['events'], [new static(), 'onHandshake']));
        static::$_http->on('message', static::callback(Constant::MESSAGE, $settings['events'], [new static(), 'onMessage']));
        static::$_http->on('close', static::callback(Constant::CLOSE, $settings['events'], [new static(), 'onClose']));
    }


    /**
     * @param Request $request
     * @param Response $response
     * @throws Exception
     */
    public function onHandshake(Request $request, Response $response)
    {
        /** @var \Swoole\WebSocket\Server $server */
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            throw new Exception('protocol error.', 500);
        }
        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
        $headers = [
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-websocket-Accept'  => $key,
            'Sec-websocket-Version' => '13',
        ];
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-websocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }
        foreach ($headers as $key => $val) {
            $response->setHeader($key, $val);
        }
        $response->setStatusCode(101);
        $response->end();
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onConnect(Server $server, int $fd)
    {
        var_dump(__FILE__ . ':' . __LINE__);
        $server->confirm($fd);
    }


    /**
     * @param \Swoole\WebSocket\Server|Server $server
     * @param Frame $frame
     */
    public function onMessage(\Swoole\WebSocket\Server|Server $server, Frame $frame)
    {
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onClose(Server $server, int $fd)
    {
        var_dump($server->getClientInfo($fd));
    }

}
