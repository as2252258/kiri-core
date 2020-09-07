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

		foreach ($configs as $config) {
			var_dump($this->isUse($config['port']));
			if ($this->isUse($config['port'])) {
				return $this->error('Port ' . $config['host'] . '::' . $config['port'] . ' is already.');
			}
		}
		$baseServer->start();
	}


	/**
	 * @return bool
	 */
	public function isRunner()
	{
		if (empty($this->port)) {
			return false;
		}
		if (Snowflake::isLinux()) {
			exec('netstat -tunlp | grep ' . $this->port, $output);
		} else {
			exec('lsof -i :' . $this->port . ' | grep -i "LISTEN"', $output);
		}
		if (!empty($output)) {
			return true;
		}
		return false;
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

		$application = Snowflake::app();
		foreach ($processes as $name => $process) {
			$class = Snowflake::createObject($process);
			if (!method_exists($class, 'onHandler')) {
				continue;
			}

			$this->debug(sprintf('Process %s', $process));
			$system = new Process([$class, 'onHandler'], false, 1, true);
			if (Snowflake::isLinux()) {
				$system->name($name);
			}
			$this->baseServer->addProcess($system);
			$application->set(get_class($class), $system);
		}
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
		if ($this->baseServer instanceof Websocket) {
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
		$websocket = Snowflake::app()->annotation->register('websocket', AWebsocket::class);
		$websocket->namespace = 'App\\Websocket';
		$websocket->path = APP_PATH . 'app/Websocket';

		$event = Snowflake::app()->event;
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
