<?php


namespace HttpServer;

use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Events\OnRequest;
use HttpServer\Route\Annotation\Annotation;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Service\Http;
use HttpServer\Service\Receive;
use HttpServer\Service\Packet;
use HttpServer\Service\WebSocket;
use Exception;
use ReflectionException;
use Snowflake\Config;
use Snowflake\Core\ArrayAccess;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Process;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Swoole\Runtime;

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
class Server extends Application
{
	const HTTP = 'HTTP';
	const TCP = 'TCP';
	const PACKAGE = 'PACKAGE';
	const WEBSOCKET = 'WEBSOCKET';

	private $listening = [];
	private $server = [
		'HTTP'      => [SWOOLE_TCP, Http::class],
		'TCP'       => [SWOOLE_TCP, Receive::class],
		'PACKAGE'   => [SWOOLE_UDP, Packet::class],
		'WEBSOCKET' => [SWOOLE_SOCK_TCP, WebSocket::class],
	];

	/** @var Http|WebSocket|Packet|Receive */
	private $baseServer;


	/**
	 * @param array $configs
	 * @return Http|Packet|Receive|WebSocket
	 * @throws Exception
	 */
	public function initCore(array $configs)
	{
		if ($this->baseServer) {
			return $this->getServer();
		}

		$annotation = Snowflake::get()->annotation;
		$annotation->register('tcp', Tcp::class);
		$annotation->register('http', Annotation::class);
		$annotation->register('websocket', AWebsocket::class);

		$this->enableCoroutine((bool)Config::get('enable_coroutine'));
		foreach ($this->sortServers($configs) as $server) {
			$this->create($server);
		}

		$this->onProcessListener();
		return $this->getServer();
	}


	/**
	 * @return void
	 *
	 * start server
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function start()
	{
		$configs = Config::get('servers', true);
		$baseServer = $this->initCore($configs);
		$baseServer->start();
	}


	/**
	 * @param bool $isEnable
	 */
	private function enableCoroutine($isEnable = true)
	{
		if ($isEnable !== true) {
			return;
		}
		Runtime::enableCoroutine(true, SWOOLE_HOOK_TCP |
			SWOOLE_HOOK_UNIX |
			SWOOLE_HOOK_UDP |
			SWOOLE_HOOK_UDG |
			SWOOLE_HOOK_SSL |
			SWOOLE_HOOK_TLS |
			SWOOLE_HOOK_SLEEP |
			SWOOLE_HOOK_STREAM_FUNCTION |
			SWOOLE_HOOK_PROC
		);
	}


	/**
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function onProcessListener()
	{
		$processes = Config::get('processes');
		if (empty($processes) || !is_array($processes)) {
			return;
		}

		$application = Snowflake::get();
		foreach ($processes as $name => $process) {
			$class = Snowflake::createObject($process);
			if (!method_exists($class, 'onHandler')) {
				continue;
			}

			$this->debug(sprintf('Process %s', $process));
			$system = new Process([$class, 'onHandler'], false, null, true);
			if (Snowflake::isLinux()) {
				$system->name($name);
			}
			$this->baseServer->addProcess($system);
			$application->set($name, $process);
		}
	}


	/**
	 * @return Http|WebSocket|Packet|Receive
	 */
	public function getServer()
	{
		return $this->baseServer;
	}


	/**
	 * @param $config
	 * @return mixed
	 * @throws Exception
	 */
	private function create($config)
	{
		$settings = Config::get('settings', false, []);
		if (!isset($this->server[$config['type']])) {
			throw new Exception('Unknown server type(' . $config['type'] . ').');
		}
		if (isset($config['settings'])) {
			$settings = ArrayAccess::merge($settings, $config['settings']);
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
		$event = Snowflake::get()->event;
		foreach ($config['events'] as $name => $_event) {
			$event->on($name, $_event);
		}
	}

	/**
	 * @param $config
	 * @param $settings
	 * @return mixed
	 * @throws Exception
	 */
	private function dispatchCreate($config, $settings)
	{
		if (!($this->baseServer instanceof \Swoole\Server)) {
			$class = $this->dispatch($config['type']);
			$this->baseServer = new $class($config['host'], $config['port'], SWOOLE_PROCESS, $config['mode']);
			$this->baseServer->set($settings);
			$this->bindAnnotation();
		} else {
			$newListener = $this->baseServer->addlistener($config['host'], $config['port'], $config['mode']);
			if (isset($config['settings']) && is_array($config['settings'])) {
				$newListener->set($config['settings']);
			}
			$this->onListenerBind($config, $this->baseServer);
		}
		return $this->baseServer;
	}


	/**
	 * @throws Exception
	 */
	private function bindAnnotation()
	{
		if ($this->baseServer instanceof WebSocket) {
			$this->onLoadWebsocketHandler();
		}
		if ($this->baseServer instanceof Http) {
			$this->onLoadHttpHandler();
		}
	}


	/**
	 * @param $config
	 * @param $newListener
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function onListenerBind($config, $newListener)
	{
		$this->debug(sprintf('Listener %s::%d', $config['host'], $config['port']));
		if ($config['type'] == self::HTTP) {
			$this->onBind($newListener, 'request', [Snowflake::createObject(OnRequest::class), 'onHandler']);
		} else if ($config['type'] == self::TCP || $config['type'] == self::PACKAGE) {
			$this->onBind($newListener, 'connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
			$this->onBind($newListener, 'close', [Snowflake::createObject(OnClose::class), 'onHandler']);
			$this->onBind($newListener, 'packet', [Snowflake::createObject(OnPacket::class), 'onHandler']);
			$this->onBind($newListener, 'receive', [Snowflake::createObject(OnReceive::class), 'onHandler']);
		} else if ($config['type'] == self::WEBSOCKET) {
			throw new Exception('Base server must instanceof \Swoole\WebSocket\Server::class.');
		} else {
			throw new Exception('Unknown server type(' . $config['type'] . ').');
		}
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
		$event = Snowflake::get()->getEvent();
		$router = Snowflake::get()->getRouter();
		if ($event->exists(Event::SERVER_WORKER_START, [$router, 'loadRouterSetting'])) {
			return;
		}
		$event->on(Event::SERVER_WORKER_START, [$router, 'loadRouterSetting']);
	}


	/**
	 * @throws Exception
	 */
	public function onLoadWebsocketHandler()
	{
		/** @var AWebsocket $websocket */
		$websocket = Snowflake::get()->annotation->register('websocket', AWebsocket::class);
		$websocket->namespace = 'App\\Websocket';
		$websocket->path = APP_PATH . 'app/Websocket';

		$event = Snowflake::get()->event;
		if ($event->exists(Event::SERVER_WORKER_START, [$websocket, 'registration_notes'])) {
			return;
		}
		$event->on(Event::SERVER_WORKER_START, [$websocket, 'registration_notes']);
	}


	/**
	 * @param $type
	 * @return string
	 */
	private function dispatch($type)
	{
		$default = [
			self::HTTP      => Http::class,
			self::WEBSOCKET => WebSocket::class,
			self::TCP       => Receive::class,
			self::PACKAGE   => Packet::class
		];
		return $default[$type] ?? Receive::class;
	}

	/**
	 * @param $servers
	 * @return array
	 */
	private function sortServers($servers)
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
