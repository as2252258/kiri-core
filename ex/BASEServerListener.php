<?php

use SInterface\CustomProcess;
use Swoole\Coroutine;
use Swoole\Http\Server as HServer;
use Swoole\Process;
use Swoole\Server;
use Swoole\WebSocket\Server as WServer;
use Task\ServerTask;

require_once 'HTTPServerListener.php';
require_once 'TCPServerListener.php';
require_once 'UDPServerListener.php';
require_once 'WebSocketServerListener.php';
require_once 'Task/ServerTask.php';
require_once 'ListenerHelper.php';
require_once 'Manager/ServerManager.php';
require_once 'Manager/ServerBase.php';
require_once 'Worker/ServerWorker.php';


/**
 * @param Closure $closure
 * @param int $sleep
 */
function loop(Closure $closure, int $sleep = 1)
{
	call_user_func($closure);

	loop($closure, $sleep);
}

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


	const SERVER_TYPE_HTTP = 'http';
	const SERVER_TYPE_WEBSOCKET = 'ws';
	const SERVER_TYPE_TCP = 'tcp';
	const SERVER_TYPE_UDP = 'udp';
	const SERVER_TYPE_BASE = 'base';


	const SERVER_ON_START = 'Start';
	const SERVER_ON_SHUTDOWN = 'Shutdown';
	const SERVER_ON_WORKER_START = 'WorkerStart';
	const SERVER_ON_WORKER_STOP = 'WorkerStop';
	const SERVER_ON_WORKER_EXIT = 'WorkerExit';
	const SERVER_ON_CONNECT = 'Connect';
	const SERVER_ON_HANDSHAKE = 'handshake';
	const SERVER_ON_MESSAGE = 'message';
	const SERVER_ON_RECEIVE = 'Receive';
	const SERVER_ON_PACKET = 'Packet';
	const SERVER_ON_REQUEST = 'request';
	const SERVER_ON_CLOSE = 'Close';
	const SERVER_ON_TASK = 'Task';
	const SERVER_ON_FINISH = 'Finish';
	const SERVER_ON_PIPE_MESSAGE = 'PipeMessage';
	const SERVER_ON_WORKER_ERROR = 'WorkerError';
	const SERVER_ON_MANAGER_START = 'ManagerStart';
	const SERVER_ON_MANAGER_STOP = 'ManagerStop';
	const SERVER_ON_BEFORE_RELOAD = 'BeforeReload';
	const SERVER_ON_AFTER_RELOAD = 'AfterReload';


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
		$this->addProcess(RelationshipSystemProcess::class);
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
		$process = $this->server->addProcess(
			new Process(
				function (Process $soloProcess) use ($customProcess) {
					$soloProcess->name($customProcess->getProcessName($soloProcess));
					/** @var \Swoole\Coroutine\Socket $export */
					$export = $soloProcess->exportSocket();
					loop(function () use ($export, $customProcess) {
						$read = $export->recv();
						if (!empty($read)) {
							$customProcess->receive($read);
						}
					});
					$customProcess->onHandler($soloProcess);
				},
				$redirect_stdin_and_stdout,
				$pipe_type,
				$enable_coroutine
			)
		);
		/** @var \Swoole\Coroutine\Socket $socket */
		$socket = $process->exportSocket();
		$socket->send("");
	}


	/**
	 * @param array $ports
	 * @return array
	 */
	private function sortService(array $ports): array
	{
		$array = [];
		foreach ($ports as $port) {
			if ($port['type'] == static::SERVER_TYPE_WEBSOCKET) {
				array_unshift($array, $port);
			} else if ($port['type'] == static::SERVER_TYPE_HTTP) {
				if (!empty($array) && $array[0]['type'] == self::SERVER_TYPE_WEBSOCKET) {
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
			BASEServerListener::SERVER_ON_PIPE_MESSAGE  => '',
			BASEServerListener::SERVER_ON_SHUTDOWN      => '',
			BASEServerListener::SERVER_ON_WORKER_START  => '',
			BASEServerListener::SERVER_ON_WORKER_ERROR  => '',
			BASEServerListener::SERVER_ON_WORKER_EXIT   => '',
			BASEServerListener::SERVER_ON_WORKER_STOP   => '',
			BASEServerListener::SERVER_ON_MANAGER_START => '',
			BASEServerListener::SERVER_ON_MANAGER_STOP  => '',
			BASEServerListener::SERVER_ON_BEFORE_RELOAD => '',
			BASEServerListener::SERVER_ON_AFTER_RELOAD  => '',
			BASEServerListener::SERVER_ON_START         => '',
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
			case self::SERVER_TYPE_TCP:
				TCPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case self::SERVER_TYPE_UDP:
				UDPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case self::SERVER_TYPE_HTTP:
				HTTPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case self::SERVER_TYPE_WEBSOCKET:
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
			self::SERVER_TYPE_BASE, self::SERVER_TYPE_TCP, self::SERVER_TYPE_UDP => Server::class,
			self::SERVER_TYPE_HTTP => HServer::class,
			self::SERVER_TYPE_WEBSOCKET => WServer::class
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
		if ($type === BASEServerListener::SERVER_TYPE_WEBSOCKET) {
			$this->server->on('handshake', $settings['events'][static::SERVER_ON_HANDSHAKE] ?? [WebSocketServerListener::class, 'onHandshake']);
			$this->server->on('message', $settings['events'][static::SERVER_ON_MESSAGE] ?? [WebSocketServerListener::class, 'onMessage']);
			$this->server->on('close', $settings['events'][static::SERVER_ON_CLOSE] ?? [WebSocketServerListener::class, 'onClose']);
		} else if ($type === BASEServerListener::SERVER_TYPE_UDP) {
			$this->server->on('packet', $settings['events'][static::SERVER_ON_PACKET] ?? [UDPServerListener::class, 'onPacket']);
		} else if ($type === BASEServerListener::SERVER_TYPE_HTTP) {
			$this->server->on('request', $settings['events'][static::SERVER_ON_REQUEST] ?? [HTTPServerListener::class, 'onRequest']);
		} else {
			$this->server->on('receive', $settings['events'][static::SERVER_ON_RECEIVE] ?? [TCPServerListener::class, 'onReceive']);
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
		if ($task_use_object || $this->server->setting['task_enable_coroutine']) {
			$this->server->on('task', $events[static::SERVER_ON_TASK] ?? [ServerTask::class, 'onCoroutineTask']);
		} else {
			$this->server->on('task', $events[static::SERVER_ON_TASK] ?? [ServerTask::class, 'onTask']);
		}
		$this->server->on('finish', $events[static::SERVER_ON_FINISH] ?? [ServerTask::class, 'onFinish']);
	}
}


$context = BASEServerListener::getContext();
$context->start();
