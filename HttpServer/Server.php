<?php


namespace HttpServer;

use Annotation\IAnnotation;
use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Events\OnRequest;
use HttpServer\Route\Annotation\Http as AnnotationHttp;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Service\Http;
use HttpServer\Service\Receive;
use HttpServer\Service\Packet;
use HttpServer\Service\Websocket;
use Exception;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
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


	/** @var Http|Websocket|Packet|Receive */
	private Packet|Websocket|Receive|Http $baseServer;

	public int $daemon = 0;


	private array $listenTypes = [];


	private array $process = [];


	/**
	 * @param $name
	 * @param $process
	 */
	public function addProcess($name, $process)
	{
		$this->process[$name] = $process;
	}


	/**
	 * @param array $configs
	 * @return Http|Packet|Receive|Websocket
	 * @throws Exception
	 */
	public function initCore(array $configs): Packet|Websocket|Receive|Http
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
	 * @return void
	 *
	 * start server
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function start()
	{
		$configs = Config::get('servers', true);
		Snowflake::clearWorkerId();
		$baseServer = $this->initCore($configs);
		if (!$baseServer) {
			return;
		}
		$baseServer->start();
	}


	/**
	 * @param $host
	 * @param $Port
	 * @return Http|Packet|Receive|Websocket
	 * @throws Exception
	 */
	public function error_stop($host, $Port): Packet|Websocket|Receive|Http
	{
		$this->error(sprintf('Port %s::%d is already.', $host, $Port));
		if ($this->baseServer) {
			$this->baseServer->shutdown();
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


	/**
	 * @param bool $isEnable
	 */
	private function enableCoroutine($isEnable = true)
	{
		if (!$isEnable) {
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
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function onProcessListener(): void
	{
		if (!($this->baseServer instanceof \Swoole\Server)) {
			return;
		}

		$attributes = Snowflake::app()->getAttributes();
		$attributes->readControllers(CONTROLLER_PATH, 'controllers');
		$attributes->readControllers(SOCKET_PATH, 'sockets');

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
			$is_enable_coroutine = true;
			if (is_array($process)) {
				[$process, $is_enable_coroutine] = $process;
			}
			$this->debug(sprintf('Process %s', $process));
			if (!is_string($process)) {
				continue;
			}
			$system = new $process(Snowflake::app(), $name, $is_enable_coroutine);
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
	 * @return Http|Websocket|Packet|Receive
	 */
	public function getServer(): Packet|Websocket|Receive|Http
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
		$event = Snowflake::app()->event;
		foreach ($config['events'] as $name => $_event) {
			$event->on($name, $_event);
		}
	}

	/**
	 * @param $config
	 * @param $settings
	 * @return \Swoole\Server|Packet|Receive|Http|Websocket
	 * @throws Exception
	 */
	private function dispatchCreate($config, $settings): \Swoole\Server|Packet|Receive|Http|Websocket
	{
		if (!($this->baseServer instanceof \Swoole\Server)) {
			$this->parseServer($config, $settings);
		} else {
			if ($this->isUse($config['port'])) {
				return $this->error_stop($config['host'], $config['port']);
			}
			$newListener = $this->baseServer->addlistener($config['host'], $config['port'], $config['mode']);
			if (isset($config['settings']) && is_array($config['settings'])) {
				$newListener->set($config['settings']);
			}
			$this->onListenerBind($config, $this->baseServer);
		}
		return $this->baseServer;
	}


	/**
	 * @param $config
	 * @param $settings
	 * @return Packet|Websocket|Receive|Http
	 * @throws Exception
	 */
	private function parseServer($config, $settings): Packet|Websocket|Receive|Http
	{
		$class = $this->dispatch($config['type']);
		if ($this->isUse($config['port'])) {
			return $this->error_stop($config['host'], $config['port']);
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
		return $this->baseServer->set($settings);
	}


	/**
	 * @param $config
	 * @param $newListener
	 * @return void
	 * @throws ReflectionException
	 * @throws Exception
	 * @throws NotFindClassException
	 */
	private function onListenerBind($config, $newListener)
	{
		$this->debug(sprintf('Listener %s::%d -> %s', $config['host'], $config['port'], $config['mode']));
		if ($config['type'] == self::WEBSOCKET) {
			throw new Exception('Base server must instanceof \Swoole\Websocket\Server::class.');
		} else if (!in_array($config['type'], [self::HTTP, self::TCP, self::PACKAGE])) {
			throw new Exception('Unknown server type(' . $config['type'] . ').');
		}
		if (in_array($config['type'], $this->listenTypes)) {
			return;
		}
		if ($config['type'] == self::HTTP) {
			if (in_array($config['type'], $this->listenTypes)) {
				throw new Exception('Base server must instanceof \Swoole\Websocket\Server::class.');
			}
			$this->onBind($newListener, 'request', [Snowflake::createObject(OnRequest::class), 'onHandler']);
		} else {
			$this->noHttp($newListener, $config);
		}
		array_push($this->listenTypes, $config['type']);
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
			$attributes->readControllers(CONTROLLER_PATH, 'controllers');

			$aliases = $attributes->getAlias('controllers');
			foreach ($aliases as $alias) {
				$handler = $alias['handler'];
				foreach ($alias['attributes'] as $key => $attribute) {
					if ($attribute instanceof IAnnotation) {
						$attribute->setHandler($handler);
					}
				}
			}
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
			$attributes->readControllers(SOCKET_PATH, 'sockets');

			$aliases = $attributes->getAlias('sockets');
			foreach ($aliases as $alias) {
				$handler = $alias['handler'];
				foreach ($alias['attributes'] as $key => $attribute) {
					if ($attribute instanceof IAnnotation) {
						$attribute->setHandler($handler);
					}
				}
			}
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
