<?php

namespace Server;

use HttpServer\Route\Router;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class TCPServerListener
 * @package HttpServer\Service
 */
class TCPServerListener
{

    use ListenerHelper;

    protected static mixed $_tcp;
    

    /**
     * UDPServerListener constructor.
     * @param Server $server
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array|null $settings
     * @throws \ReflectionException
     */
    public static function instance(Server $server, string $host, int $port, int $mode, ?array $settings = [])
    {
        if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
            trigger_error('Port mode ' . $host . '::' . $port . ' must is tcp listener type.');
        }
        $reflect = Snowflake::getDi()->getReflect(static::class)?->newInstance();
        static::$_tcp = $server->addlistener($host, $port, $mode);
        static::$_tcp->set($settings['settings'] ?? []);
        static::$_tcp->on('receive', self::callback(Constant::RECEIVE, $settings['events'], [$reflect, 'onReceive']));
        static::$_tcp->on('connect', static::callback(Constant::CONNECT, $settings['events'], [$reflect, 'onConnect']));
        static::$_tcp->on('close', static::callback(Constant::CLOSE, $settings['events'], [$reflect, 'onClose']));
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onConnect(Server $server, int $fd)
    {
        var_dump(__FILE__ . ':' . __LINE__);
    }


    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
    {
        var_dump($data);
        $server->send($fd, $data);
    }


    /**
     * @param Server $server
     * @param int $fd
     */
    public function onClose(Server $server, int $fd)
    {
    }

}
