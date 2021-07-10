<?php


namespace HttpServer;

use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Events\OnRequest;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use Snowflake\Abstracts\Config;
use Snowflake\Error\LoggerProcess;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Process\Biomonitoring;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Runtime;


defined('PID_PATH') or define('PID_PATH', APP_PATH . 'storage/server.pid');

/**
 * Class Server
 * @package HttpServer
 */
class Server extends HttpService
{

    const HTTP = 'HTTP';
    const TCP = 'TCP';
    const PACKAGE = 'PACKAGE';
    const WEBSOCKET = 'WEBSOCKET';

    private array $server = [
        'HTTP'      => [SWOOLE_TCP, Http::class],
        'TCP'       => [SWOOLE_TCP, Receive::class],
        'PACKAGE'   => [SWOOLE_UDP, Packet::class],
        'WEBSOCKET' => [SWOOLE_SOCK_TCP, Websocket::class],
    ];

    private Packet|Websocket|Receive|null|Http $swoole = null;

    public int $daemon = 0;


    private array $process = [
        'biomonitoring'  => Biomonitoring::class,
        'logger_process' => LoggerProcess::class
    ];

    private array $params = [];


    /**
     * @param $name
     * @param $process
     * @param array $params
     */
    public function addProcess($name, $process, $params = [])
    {
        $this->process[$name] = $process;
        $this->params[$name] = $params;
    }


    /**
     * @return array
     */
    public function getProcesses(): array
    {
        return $this->process ?? [];
    }


    /**
     * @param $configs
     * @return Packet|Websocket|Receive|Http|null
     * @throws Exception
     */
    private function initCore($configs): Packet|Websocket|Receive|Http|null
    {
        $servers = $this->sortServers($configs);
        foreach ($servers as $server) {
            $this->create($server);
            if (!$this->swoole) {
                throw new Exception('Base service create fail.');
            }
        }
        return $this->startRpcService();
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
        $configs = Config::get('servers', [], true);

        $baseServer = $this->initCore($configs);
        if (!$baseServer) {
            return 'ok';
        }

        Runtime::enableCoroutine(true, SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_BLOCKING_FUNCTION);

        $settings['enable_deadlock_check'] = false;
        $settings['exit_condition'] = function () {
            return Coroutine::stats()['coroutine_num'] === 0;
        };
        Coroutine::set($settings);

        return $this->execute($baseServer);
    }


    /**
     * @param $baseServer
     * @return mixed
     * @throws Exception
     */
    private function execute($baseServer): mixed
    {
        $app = Snowflake::app();
        $app->set('base-server', $baseServer);
        return $baseServer->start();
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
        $port = $this->sortServers(Config::get('servers'));
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
     * @throws ConfigException
     * @throws Exception
     */
    public function onProcessListener(): \Swoole\Server|null|Packet|Receive|Http|Websocket
    {
        if (!($this->swoole instanceof \Swoole\Server)) {
            return $this->swoole;
        }

        $processes = Config::get('processes');
        if (!empty($processes) && is_array($processes)) {
            $this->deliveryProcess(merge($processes, $this->process));
        } else {
            $this->deliveryProcess($this->process);
        }
        return $this->swoole;
    }


    /**
     * @param $processes
     * @throws Exception
     */
    private function deliveryProcess($processes)
    {
        $application = Snowflake::app();
        if (empty($processes) || !is_array($processes)) {
            return;
        }
        foreach ($processes as $name => $process) {
            $this->debug(sprintf('Process %s', $process));
            if (!is_string($process)) {
                continue;
            }
            $system = Snowflake::createObject($process, [Snowflake::app(), $name, true]);
            if (isset($this->params[$name]) && !empty($this->params[$name])) {
                $system->write(swoole_serialize($this->params[$name]));
            }
            $this->swoole->addProcess($system);
            $application->set($process, $system);
        }
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
     * @return Packet|Websocket|Receive|Http|null
     */
    public function getServer(): Packet|Websocket|Receive|Http|null
    {
        return $this->swoole;
    }


    /**
     * @param $config
     * @return mixed
     * @throws ConfigException
     * @throws Exception
     */
    private function create($config): \Swoole\Server|null|Packet|Receive|Http|Websocket
    {
        $settings = Config::get('settings', []);
        if (!isset($this->server[$config['type']])) {
            throw new Exception('Unknown server type(' . $config['type'] . ').');
        }
        $server = $this->dispatchCreate($config, $settings);
        if (isset($config['events'])) {
            $this->createEventListen($server, $config);
        }
        return $server;
    }


    /**
     * @param $config
     * @throws Exception
     */
    protected function createEventListen($server, $config)
    {
        if (!is_array($config['events'])) {
            return;
        }
        foreach ($config['events'] as $name => $_event) {
            $server->on($name, $_event);
        }
    }

    /**
     * @param $config
     * @param $settings
     * @return mixed
     * @throws Exception
     */
    private function dispatchCreate($config, $settings): mixed
    {
        if (!($this->swoole instanceof \Swoole\Server)) {
            $this->parseServer($config, $settings);
        }
        return $this->addListener($config);
    }


    /**
     * @param $config
     * @return Http|Packet|Receive|Websocket|null
     * @throws Exception
     */
    private function addListener($config): Packet|Websocket|Receive|Http|null
    {
        $newListener = $this->swoole->addlistener($config['host'], $config['port'], $config['mode']);
        if (!$newListener) {
            exit($this->addError(sprintf('Listen %s::%d fail.', $config['host'], $config['port'])));
        }

        $newListener->set($config['settings'] ?? []);
        $this->onListenerBind($newListener, $config);

        return $this->swoole;
    }


    /**
     * @return Packet|Websocket|Receive|Http|null
     * @throws ConfigException
     * @throws Exception
     */
    private function startRpcService(): Packet|Websocket|Receive|Http|null
    {
        $rpcService = Config::get('rpc', []);
        if (empty($rpcService)) {
            return $this->swoole;
        }
        $this->addListener($rpcService);
        return $this->swoole;
    }


    /**
     * @param $config
     * @param $settings
     * @return Packet|Websocket|Receive|Http|null
     * @throws Exception
     */
    private function parseServer($config, $settings): Packet|Websocket|Receive|Http|null
    {
        $class = $this->dispatch($config['type']);
        if (is_array($config['settings'] ?? null)) {
            $settings = array_merge($settings, $config['settings']);
        }
        $this->debug(Snowflake::listen($config));
        $this->swoole = $this->createServer($class, $config);
        $this->swoole->set(array_merge($settings, [
            'daemonize' => $this->daemon,
            'pid_file'  => $settings['pid_file'] ?? PID_PATH
        ]));
        return $this->onProcessListener();
    }


    /**
     * @param $class
     * @param $config
     * @return mixed
     */
    private function createServer($class, $config): mixed
    {
        return new $class($config['host'], $config['port'], SWOOLE_PROCESS, $config['mode']);
    }


    /**
     * @param $config
     * @return Packet|Websocket|Receive|Http|null
     * @throws Exception
     */
    private function onListenerBind($server, $config): Packet|Websocket|Receive|Http|null
    {
        if (self::PACKAGE == $config['type']) {
            $this->onBindCallback($server, 'packet', $config['events'][Event::SERVER_ON_PACKET] ?? [make(OnPacket::class), 'onHandler']);
        } else if ($config['type'] == self::TCP) {
            $this->onBindCallback($server, 'connect', $config['events'][Event::SERVER_ON_CONNECT] ?? [make(OnConnect::class), 'onHandler']);
            $this->onBindCallback($server, 'close', $config['events'][Event::SERVER_ON_CLOSE] ?? [make(OnClose::class), 'onHandler']);
            $this->onBindCallback($server, 'receive', $config['events'][Event::SERVER_ON_RECEIVE] ?? [make(OnReceive::class), 'onHandler']);
        } else if ($config['type'] === self::HTTP) {
            $this->onBindCallback($server, 'request', $config['events'][Event::SERVER_ON_REQUEST] ?? [make(OnRequest::class), 'onHandler']);
        } else {
            throw new Exception('Unknown server type(' . $config['type'] . ').');
        }

        $this->debug(sprintf('Check listen %s::%d -> ok', $config['host'], $config['port']));

        return $this->swoole;
    }


    /**
     * @param $name
     * @param $callback
     * @throws Exception
     */
    public function onBindCallback($server, $name, $callback)
    {
        if ($server->getCallback($name) !== null) {
            return;
        }
        $server->on($name, $callback);
    }


    /**
     * @param $type
     * @return string
     */
    private function dispatch($type): string
    {
        return match ($type) {
            self::HTTP => Http::class,
            self::WEBSOCKET => Websocket::class,
            self::PACKAGE => Packet::class,
            default => Receive::class
        };
    }

    /**
     * @param $servers
     * @return array
     */
    private function sortServers($servers): array
    {
        $array = [];
        foreach ($servers as $server) {
            switch ($server['type']) {
                case self::WEBSOCKET:
                    array_unshift($array, $server);
                    break;
                case self::HTTP:
                case self::PACKAGE | self::TCP:
                    $array[] = $server;
                    break;
                default:
                    $array[] = $server;
            }
        }
        return $array;
    }


}
