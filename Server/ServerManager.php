<?php

namespace Server;

use Closure;
use ReflectionException;
use Server\SInterface\CustomProcess;
use Server\Task\ServerTask;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Server as HServer;
use Swoole\Process;
use Swoole\Server;
use Swoole\WebSocket\Server as WServer;


/**
 * Class ServerManager
 * @package HttpServer\Service
 */
class ServerManager extends Abstracts\Server
{

	public string $host = '';

	public int $port = 0;


	/** @var Server\Port[] */
	public array $ports = [];

	public int $mode = SWOOLE_TCP;


	private Server|WServer|HServer|null $server = null;


	private static ?ServerManager $BASEServerListener = null;


	/**
	 * @return static
	 */
	public static function getContext(): static
	{
		if (!(static::$BASEServerListener)) {
			static::$BASEServerListener = new ServerManager();
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function initBaseServer($configs): void
	{
		$context = ServerManager::getContext();
		foreach ($this->sortService($configs['ports']) as $config) {
			$this->startListenerHandler($context, $config);
		}
		$this->addServerEventCallback($this->getSystemEvents($configs));
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
			if (Snowflake::getPlatform()->isLinux()) {
				$soloProcess->name($customProcess->getProcessName($soloProcess));
			}
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
			Constant::DISCONNECT    => '',
			Constant::START         => '',
		]);
	}


	/**
	 * @param ServerManager $context
	 * @param array $config
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws \Exception
	 */
	private function startListenerHandler(ServerManager $context, array $config)
	{
		if ($this->server) {
			$context->addNewListener($config['type'], $config['host'], $config['port'], $config['mode'], $config);
		} else {
			$config['settings'] = array_merge($configs['settings'] ?? [], $config['settings'] ?? []);

			if (!isset($config['settings']['log_file'])) {
				$config['settings']['log_file'] = storage('system.log');
			}
			if (!isset($config['settings']['pid_file'])) {
				$config['settings']['pid_file'] = storage('.pid');
			}
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
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	private function addNewListener(string $type, string $host, int $port, int $mode, array $settings = [])
	{
		switch ($type) {
			case Constant::SERVER_TYPE_TCP:
				$this->ports[$port] = TCPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case Constant::SERVER_TYPE_UDP:
				$this->ports[$port] = UDPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case Constant::SERVER_TYPE_HTTP:
				$this->ports[$port] = HTTPServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
			case Constant::SERVER_TYPE_WEBSOCKET:
				$this->ports[$port] = WebSocketServerListener::instance($this->server, $host, $port, $mode, $settings);
				break;
		}
	}


	/**
	 * @param int $port
	 * @param string $event
	 * @return Closure|array|null
	 */
	public function getPortCallback(int $port, string $event): Closure|array|null
	{
		/** @var Server\Port $_port */
		$_port = $this->ports[$port] ?? null;
		if (is_null($_port)) {
			return null;
		}
		return $_port->getCallback($event);
	}


	/**
	 * @param string $type
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array $settings
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function createBaseServer(string $type, string $host, int $port, int $mode, array $settings = [])
	{
		$match = match ($type) {
			Constant::SERVER_TYPE_BASE, Constant::SERVER_TYPE_TCP,
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function addDefaultListener(string $type, array $settings): void
	{
		$this->addServerEventCallback($settings['events']);
		if (($this->server->setting['task_worker_num'] ?? 0) > 0) {
			$this->addTaskListener($settings['events']);
		}
		if ($type === Constant::SERVER_TYPE_WEBSOCKET) {
			$reflect = $this->getNewInstance(WebSocketServerListener::class);
			$this->server->on('handshake', [$reflect, 'onHandshake']);
			$this->server->on('message', [$reflect, 'onMessage']);
			$this->server->on('close', [$reflect, 'onClose']);

			$reflect->setEvents(Constant::HANDSHAKE, $settings['events'][Constant::HANDSHAKE] ?? null);
			$reflect->setEvents(Constant::MESSAGE, $settings['events'][Constant::MESSAGE] ?? null);
			$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);
			$reflect->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);
		} else if ($type === Constant::SERVER_TYPE_UDP) {
			$reflect = $this->getNewInstance(UDPServerListener::class);
			$this->server->on('packet', [$reflect, 'onPacket']);

			$reflect->setEvents(Constant::PACKET, $settings['events'][Constant::PACKET] ?? null);
		} else if ($type === Constant::SERVER_TYPE_HTTP) {
			$reflect = $this->getNewInstance(HTTPServerListener::class);
			$this->server->on('request', [$reflect, 'onRequest']);
			$this->addCloseOrDisconnect($reflect, $settings);
		} else {
			$reflect = $this->getNewInstance(TCPServerListener::class);
			$this->server->on('connect', [$reflect, 'onConnect']);
			$this->server->on('receive', [$reflect, 'onReceive']);
			$this->addCloseOrDisconnect($reflect, $settings);
		}
	}


	/**
	 *
	 */
	public function start()
	{
		$this->server->start();
	}


	/**
	 * @param string $class
	 * @return object
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function getNewInstance(string $class): object
	{
		return Snowflake::getDi()->getReflect($class)?->newInstance();
	}


	/**
	 * @param $reflect
	 * @param $settings
	 */
	private function addCloseOrDisconnect($reflect, $settings): void
	{
		if (swoole_version() >= '4.7.0') {
			$this->server->on('disconnect', [$reflect, 'onDisconnect']);
			$reflect->setEvents(Constant::DISCONNECT, $settings['events'][Constant::DISCONNECT] ?? null);
		} else {
			$this->server->on('close', [$reflect, 'onClose']);
			$reflect->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);
		}
	}


	/**
	 * @param array $events
	 */
	private function addServerEventCallback(array $events): void
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
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


$context = ServerManager::getContext();
$context->start();
