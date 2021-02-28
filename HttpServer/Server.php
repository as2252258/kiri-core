<?php


namespace HttpServer;

use Annotation\IAnnotation;
use HttpServer\Abstracts\HttpService;
use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Events\OnRequest;
use HttpServer\Service\Http;
use HttpServer\Service\Receive;
use HttpServer\Service\Packet;
use HttpServer\Service\Websocket;
use Exception;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Process\Biomonitoring;
use Snowflake\Process\ServerInotify;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Runtime;
use co;

/**
 * Class Server
 * @package HttpServer
 *
 *
 * @example [
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_TCP],
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_TCP],
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_TCP],
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_TCP],
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_UDP],
 *    ['host'=> '127.0.0.1', 'port'=> 5775, 'mode'=> SWOOLE_TCP]
 * ]
 */
class Server extends HttpService
{
    use Action;

    const HTTP = 'HTTP';
    const TCP = 'TCP';
    const PACKAGE = 'PACKAGE';
    const WEBSOCKET = 'WEBSOCKET';

    private array $listening = [];
    private array $server = [
        'HTTP'      => [SWOOLE_TCP, Http::class],
        'TCP'       => [SWOOLE_TCP, Receive::class],
        'PACKAGE'   => [SWOOLE_UDP, Packet::class],
        'WEBSOCKET' => [SWOOLE_SOCK_TCP, Websocket::class],
    ];

    private Packet|Websocket|Receive|null|Http $baseServer = null;

    public int $daemon = 0;


    private array $listenTypes = [];


    private array $process = [
        'biomonitoring' => Biomonitoring::class
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
     * @param array $configs
     * @return Packet|Websocket|Receive|Http|null
     * @throws ConfigException
     * @throws Exception
     */
    public function initCore(array $configs): Packet|Websocket|Receive|Http|null
    {
        $this->enableCoroutine((bool)Config::get('settings.enable_coroutine'));

        $this->orders($configs);
        $this->onProcessListener();
        return $this->getServer();
    }


    /**
     * @param $configs
     * @return Packet|Websocket|Receive|Http|null
     * @throws Exception
     */
    private function orders($configs): Packet|Websocket|Receive|Http|null
    {
        $servers = $this->sortServers($configs);
        foreach ($servers as $server) {
            $this->create($server);
            if (!$this->baseServer) {
                return null;
            }
        }
        return $this->baseServer;
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
        $configs = Config::get('servers', true);

        $baseServer = $this->initCore($configs);
        if (!$baseServer) {
            return 'ok';
        }
        return $baseServer->start();
    }


    /**
     * @param $host
     * @param $Port
     * @return Packet|Websocket|Receive|Http|null
     */
    public function error_stop($host, $Port): Packet|Websocket|Receive|Http|null
    {
        $this->error(sprintf('Port %s::%d is already.', $host, $Port));
        if ($this->baseServer) {
            $this->baseServer->shutdown();
        } else {
            $this->shutdown();
        }
        return $this->baseServer;
    }


    /**
     * @return bool
     * @throws ConfigException
     */
    public function isRunner(): bool
    {
        $port = $this->sortServers(Config::get('servers'));
        if (empty($port)) {
            return false;
        }
        if (Snowflake::isLinux()) {
            exec('netstat -tunlp | grep ' . $port[0]['port'], $output);
        } else {
            exec('lsof -i :' . $port[0]['port'] . ' | grep -i "LISTEN"', $output);
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
        $this->stop($this);
    }

    private int $_types = SWOOLE_HOOK_TCP |
    SWOOLE_HOOK_UNIX |
    SWOOLE_HOOK_UDP |
    SWOOLE_HOOK_UDG |
    SWOOLE_HOOK_SSL |
    SWOOLE_HOOK_TLS |
    SWOOLE_HOOK_SLEEP |
    SWOOLE_HOOK_STREAM_FUNCTION |
    SWOOLE_HOOK_PROC;


    /**
     * @param bool $isEnable
     */
    private function enableCoroutine($isEnable = true)
    {
        if (!$isEnable) {
            return;
        }
        Runtime::enableCoroutine(true, $this->_types);
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function onProcessListener(): void
    {
        if (!($this->baseServer instanceof \Swoole\Server)) {
            return;
        }

        $processes = Config::get('processes');
        if (!empty($processes) && is_array($processes)) {
            $this->deliveryProcess(merge($processes, $this->process));
        } else {
            $this->deliveryProcess($this->process);
        }
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
            $system = new $process(Snowflake::app(), $name, true);
            if (isset($this->params[$name])) {
                $system->write(Json::encode($this->params[$name]));
            }
            $this->baseServer->addProcess($system);
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
        return $this->baseServer;
    }


    /**
     * @param $config
     * @return mixed
     * @throws Exception
     */
    private function create($config): mixed
    {
        $settings = Config::get('settings', false, []);
        if (!isset($this->server[$config['type']])) {
            throw new Exception('Unknown server type(' . $config['type'] . ').');
        }
        $server = $this->dispatchCreate($config, $settings);
        if (isset($config['events'])) {
            $this->createEventListen($config);
        }
        return $server;
    }


    /**
     * @param $config
     * @throws Exception
     */
    protected function createEventListen($config)
    {
        if (!is_array($config['events'])) {
            return;
        }
        $event = Snowflake::app()->getEvent();
        foreach ($config['events'] as $name => $_event) {
            $event->on($name, $_event);
        }
    }

    /**
     * @param $config
     * @param $settings
     * @return \Swoole\Server|Packet|Receive|Http|Websocket|null
     * @throws NotFindClassException
     * @throws ReflectionException
     * @throws Exception
     */
    private function dispatchCreate($config, $settings): \Swoole\Server|Packet|Receive|Http|Websocket|null
    {
        if (!($this->baseServer instanceof \Swoole\Server)) {
            return $this->parseServer($config, $settings);
        }
        return $this->addListener($config);
    }


    private function addListener($config)
    {
        if ($this->isUse($config['port'])) {
            return $this->error_stop($config['host'], $config['port']);
        }
        $newListener = $this->baseServer->addlistener($config['host'], $config['port'], $config['mode']);
        if (isset($config['settings']) && is_array($config['settings'])) {
            $newListener->set($config['settings']);
        }
        $this->onListenerBind($config, $this->baseServer);

        return $this->baseServer;
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
        if ($this->isUse($config['port'])) {
            return $this->error_stop($config['host'], $config['port']);
        }
        if (isset($config['settings']) && !empty($config['settings'])) {
            $settings = array_merge($settings, $config['settings']);
        }
        $this->baseServer = new $class($config['host'], $config['port'], SWOOLE_PROCESS, $config['mode']);
        $settings['daemonize'] = $this->daemon;
        if (!isset($settings['pid_file'])) {
            $settings['pid_file'] = APP_PATH . 'storage/server.pid';
        }
        if ($this->baseServer instanceof Websocket) {
            $this->onLoadWebsocketHandler();
        } else if ($this->baseServer instanceof Http) {
            $this->onLoadHttpHandler();
        }
        $this->baseServer->set($settings);

        return $this->baseServer;
    }


    /**
     * @param $config
     * @param $newListener
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     * @throws NotFindClassException
     */
    private function onListenerBind($config, $newListener): mixed
    {
        $this->debug(sprintf('Listener %s::%d -> %s', $config['host'], $config['port'], $config['mode']));
        if ($config['type'] == self::WEBSOCKET) {
            throw new Exception('Base server must instanceof \Swoole\Websocket\Server::class.');
        }
        if (!in_array($config['type'], [self::HTTP, self::TCP, self::PACKAGE])) {
            throw new Exception('Unknown server type(' . $config['type'] . ').');
        }
        if (in_array($config['type'], $this->listenTypes)) {
            return $this->baseServer;
        }
        if ($config['type'] == self::HTTP) {
            if (in_array($config['type'], $this->listenTypes)) {
                throw new Exception('Base server must instanceof \Swoole\Websocket\Server::class.');
            }
            $this->onBind($newListener, 'request', [Snowflake::createObject(OnRequest::class), 'onHandler']);
        } else {
            $this->noHttp($newListener, $config);
        }
        $this->listenTypes[] = $config['type'];
        return $this->baseServer;
    }


    /**
     * @param $newListener
     * @param $config
     * @throws NotFindClassException
     * @throws ReflectionException
     * @throws Exception
     */
    private function noHttp($newListener, $config)
    {
        $this->onBind($newListener, 'connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
        $this->onBind($newListener, 'close', [Snowflake::createObject(OnClose::class), 'onHandler']);
        if ($config['type'] == self::TCP) {
            $this->onBind($newListener, 'receive', [$class = new OnReceive(), 'onHandler']);
        } else {
            $this->onBind($newListener, 'packet', [$class = new OnPacket(), 'onHandler']);
        }
        $class->pack = $config['resolve']['pack'] ?? null;
        $class->unpack = $config['resolve']['unpack'] ?? null;
    }


    /**
     * @param $server
     * @param $name
     * @param $callback
     * @throws Exception
     */
    private function onBind($server, $name, $callback)
    {
        if (in_array($name, $this->listening)) {
            return;
        }
        if ($name === 'request') {
            $this->onLoadHttpHandler();
        }
        array_push($this->listening, $name);
        $server->on($name, $callback);
    }


    /**
     * Load router handler
     * @throws Exception
     */
    public function onLoadHttpHandler()
    {
        $event = Snowflake::app()->getEvent();
        $event->on(Event::SERVER_WORKER_START, function () {
            $router = Snowflake::app()->getRouter();
            $router->loadRouterSetting();

            $attributes = Snowflake::app()->getAttributes();
            $attributes->read(CONTROLLER_PATH, 'App\Http\Controllers', 'controllers');
        });
    }


    /**
     * @throws Exception
     */
    public function onLoadWebsocketHandler()
    {
        $event = Snowflake::app()->getEvent();
        $event->on(Event::SERVER_WORKER_START, function () {
            $attributes = Snowflake::app()->getAttributes();
            $attributes->read(SOCKET_PATH, 'App\Websocket', 'sockets');
        });
    }


    /**
     * @param $type
     * @return string
     */
    private function dispatch($type): string
    {
        $default = [
            self::HTTP      => Http::class,
            self::WEBSOCKET => Websocket::class,
            self::TCP       => Receive::class,
            self::PACKAGE   => Packet::class
        ];
        return $default[$type] ?? Receive::class;
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
