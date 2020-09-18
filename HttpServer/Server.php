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
use HttpServer\Service\Websocket;
use Exception;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
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
	use Action;

	const HTTP = 'HTTP';
	const TCP = 'TCP';
	const PACKAGE = 'PACKAGE';
	const WEBSOCKET = 'WEBSOCKET';

	private $listening = [];
	private $server = [
		'HTTP'      => [SWOOLE_TCP, Http::class],
		'TCP'       => [SWOOLE_TCP, Receive::class],
		'PACKAGE'   => [SWOOLE_UDP, Packet::class],
		'WEBSOCKET' => [SWOOLE_SOCK_TCP, Websocket::class],
	];


	/** @var Http|Websocket|Packet|Receive */
	private $baseServer;

	public $daemon = 0;


	private $process = [];


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
	public function initCore(array $configs)
	{
		$annotation = Snowflake::app()->annotation;
		$annotation->register('tcp', Tcp::class);
		$annotation->register('http', Annotation::class);
		$annotation->register('websocket', AWebsocket::class);

		$this->enableCoroutine((bool)Config::get('settings.enable_coroutine'));
		foreach ($this->sortServers($configs) as $server) {
			$this->create($server);
			if (!$this->baseServer) {
				return null;
			}
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
	 * @throws Exception
	 */
	public function error_stop($host, $Port)
	{
		$this->error(sprintf('Port %s::%d is already.', $host, $Port));
		if ($this->baseServer) {
			$this->baseServer->shutdown();
		}
	}


	/**
	 * @return bool
	 * @throws ConfigException
	 */
	public function isRunner()
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
	public function onProcessListener()
	{
		$processes = Config::get('processes');
		if (!empty($processes) && is_array($processes)) {
			return $this->deliveryProcess(merge($processes, $this->process));
		}
		return $this->deliveryProcess($this->process);
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
			$system = new $process(Snowflake::app(), $name);
			$this->baseServer->addProcess($system);
			$application->set($process, $system);
		}
	}


	/**
	 * @param $daemon
	 * @return Server
	 */
	public function setDaemon($daemon)
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
	 * @return mixed
	 * @throws Exception
	 */
	private function dispatchCreate($config, $settings)
	{
		if (!($this->baseServer instanceof \Swoole\Server)) {
			$this->parseServer($config, $settings);
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
	 * @param $config
	 * @param $settings
	 * @throws Exception
	 * @return void
	 */
	private function parseServer($config, $settings)
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
		$this->baseServer->set($settings);
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
		if ($this->isUse($config['port'])) {
			return $this->error_stop($config['host'], $config['port']);
		}
		$this->debug(sprintf('Listener %s::%d', $config['host'], $config['port']));
		if ($config['type'] == self::HTTP) {
			$this->onBind($newListener, 'request', [Snowflake::createObject(OnRequest::class), 'onHandler']);
		} else if ($config['type'] == self::TCP || $config['type'] == self::PACKAGE) {
			$this->onBind($newListener, 'connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
			$this->onBind($newListener, 'close', [Snowflake::createObject(OnClose::class), 'onHandler']);
			$this->onBind($newListener, 'packet', [Snowflake::createObject(OnPacket::class), 'onHandler']);
			$this->onBind($newListener, 'receive', [Snowflake::createObject(OnReceive::class), 'onHandler']);
		} else if ($config['type'] == self::WEBSOCKET) {
			throw new Exception('Base server must instanceof \Swoole\Websocket\Server::class.');
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
		$event = Snowflake::app()->getEvent();
		$router = Snowflake::app()->getRouter();
		$event->on(Event::SERVER_WORKER_START, [$router, 'loadRouterSetting']);
	}


	/**
	 * @throws Exception
	 */
	public function onLoadWebsocketHandler()
	{
		/** @var AWebsocket $websocket */
		$websocket = Snowflake::app()->annotation->get('websocket');
		$websocket->namespace = 'App\\Websocket';
		$websocket->path = APP_PATH . 'app/Websocket';

		$event = Snowflake::app()->getEvent();
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
