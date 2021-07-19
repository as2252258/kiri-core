<?php


namespace HttpServer;

use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use Rpc\Service;
use Server\Constant;
use Server\ServerManager;
use Snowflake\Abstracts\Config;
use Snowflake\Error\LoggerProcess;
use Snowflake\Exception\ConfigException;
use Snowflake\Process\Biomonitoring;
use Snowflake\Snowflake;
use Swoole\Runtime;


defined('PID_PATH') or define('PID_PATH', APP_PATH . 'storage/server.pid');

/**
 * Class Server
 * @package HttpServer
 */
class Server extends HttpService
{

    private array $process = [
        'biomonitoring'  => Biomonitoring::class,
        'logger_process' => LoggerProcess::class
    ];


    private ServerManager $manager;


    /**
     *
     */
    public function init()
    {
        $this->manager = ServerManager::getContext();
    }


    /**
     * @param $name
     * @param $process
     * @param array $params
     */
    public function addProcess($process)
    {
        $this->manager->addProcess($process);
    }


    /**
     * @return string start server
     *
     * start server
     * @throws ConfigException
     * @throws Exception
     */
    public function start(): string
    {
        $this->manager->initBaseServer(Config::get('servers', [], true));

        $rpcService = Config::get('rpc', []);
        if (!empty($rpcService)) {
            $this->rpcListener($rpcService);
        }
        foreach ($this->process as $process) {
            $this->manager->addProcess($process);
        }
        Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_BLOCKING_FUNCTION);

        return $this->manager->getServer()->start();
    }


    /**
     * @param $rpcService
     * @throws \ReflectionException
     * @throws \Snowflake\Exception\NotFindClassException
     */
    private function rpcListener($rpcService)
    {
        $rpcService['events'][Constant::CONNECT] = [Service::class, 'onConnect'];
        $rpcService['events'][Constant::DISCONNECT] = [Service::class, 'onClose'];
        $rpcService['events'][Constant::CLOSE] = [Service::class, 'onClose'];
        $rpcService['events'][Constant::RECEIVE] = [Service::class, 'onReceive'];
        $rpcService['events'][Constant::PACKET] = [Service::class, 'onPacket'];
        $this->manager->addListener($rpcService['type'], $rpcService['host'], $rpcService['port'], $rpcService['mode'], $rpcService);
    }


    /**
     * @param $host
     * @param $Port
     * @return Packet|Websocket|Receive|Http|null
     * @throws Exception
     */
    public function error_stop($host, $Port): Packet|Websocket|Receive|Http|null
    {
        $this->error(sprintf('Port %s::%d is already.', $host, $Port));
        if ($this->swoole) {
            $this->swoole->shutdown();
        } else {
            $this->shutdown();
        }
        return $this->swoole;
    }


    /**
     * @return bool
     * @throws ConfigException
     * @throws Exception
     */
    public function isRunner(): bool
    {
        $port = Config::get('servers');
        if (empty($port)) {
            return false;
        }
        foreach ($port as $value) {
            if ($this->checkPort($value['port'])) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $port
     * @return bool
     * @throws Exception
     */
    private function checkPort($port): bool
    {
        if (Snowflake::getPlatform()->isLinux()) {
            exec('netstat -tunlp | grep ' . $port, $output);
        } else {
            exec('lsof -i :' . $port . ' | grep -i "LISTEN"', $output);
        }
        return !empty($output);
    }


    /**
     * @return void
     *
     * start server
     * @throws Exception
     */
    public function shutdown()
    {
        /** @var Shutdown $shutdown */
        $shutdown = Snowflake::app()->get('shutdown');
        $shutdown->shutdown();
    }


    /**
     * @param $daemon
     * @return Server
     */
    public function setDaemon($daemon): static
    {
        if (!in_array($daemon, [0, 1])) {
            return $this;
        }
        $this->daemon = $daemon;
        return $this;
    }


    /**
     * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
     */
    public function getServer(): \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server|null
    {
        return $this->manager->getServer();
    }


    /**
     * @param $name
     * @param $callback
     * @throws Exception
     */
    public function onBindCallback($server, $name, $callback)
    {
//        if ($server->getCallback($name) !== null) {
//            return;
//        }
        $server->on($name, $callback);
    }

}
