<?php

namespace Server;

use Server\SInterface\CustomProcess;
use Snowflake\Snowflake;
use Swoole\Http\Server as HServer;
use Swoole\Process;
use Swoole\Server;
use Swoole\WebSocket\Server as WServer;
use Server\Task\ServerTask;


/**
 * Class BASEServerListener
 * @package HttpServer\Service
 */
class BASEServerListener
{

    public string $host = '';

    public int $port = 0;

    public int $mode = SWOOLE_TCP;


    private Server|WServer|HServer|null $server = null;


    private static ?BASEServerListener $BASEServerListener = null;


    /**
     * @return static
     */
    public static function getContext(): static
    {
        if (!(static::$BASEServerListener)) {
            static::$BASEServerListener = new BASEServerListener();
        }
        return static::$BASEServerListener;
    }


    /**
     * @return Server|WServer|HServer|null
     */
    public function getServer(): Server|WServer|HServer|null
    {
        return $this->server;
    }


    /**
     * @param string $type
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array $settings
     */
    public function addListener(string $type, string $host, int $port, int $mode, array $settings = [])
    {
        if (!$this->server) {
            $this->createBaseServer($type, $host, $port, $mode, $settings);
        } else {
            if (!isset($settings['settings'])) {
                $settings['settings'] = [];
            }
            $this->addNewListener($type, $host, $port, $mode, $settings);
        }
    }


    /**
     * startRun
     */
    public function start(): void
    {
        $context = BASEServerListener::getContext();
        $configs = require_once 'server.php';

        foreach ($this->sortService($configs['server']['ports']) as $config) {
            $this->startListenerHandler($context, $config);
        }
        $this->addServerEventCallback($this->getSystemEvents($configs));
        $context->server->start();
    }


    /**
     * @param string|CustomProcess $customProcess
     * @param null $redirect_stdin_and_stdout
     * @param int|null $pipe_type
     * @param bool $enable_coroutine
     */
    public function addProcess(string|CustomProcess $customProcess, $redirect_stdin_and_stdout = null, ?int $pipe_type = SOCK_DGRAM, bool $enable_coroutine = true)
    {
        if (is_string($customProcess)) {
            $implements = class_implements($customProcess);
            if (!in_array(CustomProcess::class, $implements)) {
                trigger_error('custom process must implement ' . CustomProcess::class);
            }
            $customProcess = new $customProcess($this->server);
        }
        /** @var Process $process */
        $this->server->addProcess(new Process(function (Process $soloProcess) use ($customProcess) {
            $soloProcess->name($customProcess->getProcessName($soloProcess));
            $customProcess->onHandler($soloProcess);
        },
            $redirect_stdin_and_stdout, $pipe_type, $enable_coroutine));
    }


    /**
     * @param array $ports
     * @return array
     */
    private function sortService(array $ports): array
    {
        $array = [];
        foreach ($ports as $port) {
            if ($port['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
                array_unshift($array, $port);
            } else if ($port['type'] == Constant::SERVER_TYPE_HTTP) {
                if (!empty($array) && $array[0]['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
                    $array[] = $port;
                } else {
                    array_unshift($array, $port);
                }
            } else {
                $array[] = $port;
            }
        }
        return $array;
    }


    /**
     * @param array $configs
     * @return array
     */
    private function getSystemEvents(array $configs): array
    {
        return array_intersect_key($configs['server']['events'] ?? [], [
            Constant::PIPE_MESSAGE  => '',
            Constant::SHUTDOWN      => '',
            Constant::WORKER_START  => '',
            Constant::WORKER_ERROR  => '',
            Constant::WORKER_EXIT   => '',
            Constant::WORKER_STOP   => '',
            Constant::MANAGER_START => '',
            Constant::MANAGER_STOP  => '',
            Constant::BEFORE_RELOAD => '',
            Constant::AFTER_RELOAD  => '',
            Constant::START         => '',
        ]);
    }


    /**
     * @param BASEServerListener $context
     * @param array $config
     */
    private function startListenerHandler(BASEServerListener $context, array $config)
    {
        if ($this->server) {
            $context->addNewListener($config['type'], $config['host'], $config['port'], $config['mode'], $config);
        } else {
            $config['settings'] = array_merge($configs['settings'] ?? [], $config['settings'] ?? []);

            $config['events'] = array_merge($configs['events'] ?? [], $config['events'] ?? []);

            $context->createBaseServer($config['type'], $config['host'], $config['port'], $config['mode'], $config);
        }
    }


    /**
     * @param string $type
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array $settings
     */
    private function addNewListener(string $type, string $host, int $port, int $mode, array $settings = [])
    {
        switch ($type) {
            case Constant::SERVER_TYPE_TCP:
                TCPServerListener::instance($this->server, $host, $port, $mode, $settings);
                break;
            case Constant::SERVER_TYPE_UDP:
                UDPServerListener::instance($this->server, $host, $port, $mode, $settings);
                break;
            case Constant::SERVER_TYPE_HTTP:
                HTTPServerListener::instance($this->server, $host, $port, $mode, $settings);
                break;
            case Constant::SERVER_TYPE_WEBSOCKET:
                WebSocketServerListener::instance($this->server, $host, $port, $mode, $settings);
                break;
        }
    }


    /**
     * @param string $type
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param array $settings
     */
    private function createBaseServer(string $type, string $host, int $port, int $mode, array $settings = [])
    {
        $match = match ($type) {
            Constant::SERVER_TYPE_BASE,
            Constant::SERVER_TYPE_TCP,
            Constant::SERVER_TYPE_UDP => Server::class,
            Constant::SERVER_TYPE_HTTP => HServer::class,
            Constant::SERVER_TYPE_WEBSOCKET => WServer::class
        };
        $this->server = new $match($host, $port, SWOOLE_PROCESS, $mode);
        $this->server->set($settings['settings']);
        $this->addDefaultListener($type, $settings);
    }


    /**
     * @param string $type
     * @param array $settings
     * @return void
     */
    private function addDefaultListener(string $type, array $settings): void
    {
        if (($this->server->setting['task_worker_num'] ?? 0) > 0) $this->addTaskListener($settings['events']);
        if ($type === Constant::SERVER_TYPE_WEBSOCKET) {
            $reflect = Snowflake::getDi()->getReflect(WebSocketServerListener::class)?->newInstance();
            $this->server->on('handshake', $settings['events'][Constant::HANDSHAKE] ?? [$reflect, 'onHandshake']);
            $this->server->on('message', $settings['events'][Constant::MESSAGE] ?? [$reflect, 'onMessage']);
            $this->server->on('close', $settings['events'][Constant::CLOSE] ?? [$reflect, 'onClose']);
        } else if ($type === Constant::SERVER_TYPE_UDP) {
            $reflect = Snowflake::getDi()->getReflect(UDPServerListener::class)?->newInstance();
            $this->server->on('packet', $settings['events'][Constant::PACKET] ?? [$reflect, 'onPacket']);
        } else if ($type === Constant::SERVER_TYPE_HTTP) {
            $reflect = Snowflake::getDi()->getReflect(HTTPServerListener::class)?->newInstance();
            $this->server->on('request', $settings['events'][Constant::REQUEST] ?? [$reflect, 'onRequest']);
        } else {
            $reflect = Snowflake::getDi()->getReflect(TCPServerListener::class)?->newInstance();
            $this->server->on('receive', $settings['events'][Constant::RECEIVE] ?? [$reflect, 'onReceive']);
        }
        $this->addServerEventCallback($settings['events']);
    }


    /**
     * @param array $events
     */
    private function addServerEventCallback(array $events)
    {
        if (count($events) < 1) {
            return;
        }
        foreach ($events as $event_type => $callback) {
            if ($this->server->getCallback($event_type) !== null) {
                continue;
            }
            $this->server->on($event_type, $callback);
        }
    }


    /**
     * @param array $events
     */
    private function addTaskListener(array $events = []): void
    {
        $task_use_object = $this->server->setting['task_object'] ?? $this->server->setting['task_use_object'] ?? false;
        $reflect = Snowflake::getDi()->getReflect(ServerTask::class)?->newInstance();
        if ($task_use_object || $this->server->setting['task_enable_coroutine']) {
            $this->server->on('task', $events[Constant::TASK] ?? [$reflect, 'onCoroutineTask']);
        } else {
            $this->server->on('task', $events[Constant::TASK] ?? [$reflect, 'onTask']);
        }
        $this->server->on('finish', $events[Constant::FINISH] ?? [$reflect, 'onFinish']);
    }
}


$context = BASEServerListener::getContext();
$context->start();
