<?php


namespace HttpServer;

use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Events\OnRequest;
use HttpServer\Service\Http;
use HttpServer\Service\Receive;
use HttpServer\Service\Packet;
use HttpServer\Service\WebSocket;
use Exception;
use ReflectionException;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Process;

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
		$configs = $this->sortServers($configs);
		foreach ($configs as $server) {
			$this->create($server);
		}
		$this->onProcessListener();
		return $this->getServer();
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
		$settings = $config['settings'] ?? [];
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
		} else {
			$newListener = $this->baseServer->addlistener($config['host'], $config['port'], $config['mode']);
			if (!empty($settings)) {
				$newListener->set($settings);
			}
			$this->onListenerBind($config, $this->baseServer);
		}
		return $this->baseServer;
	}


	/**
	 * @param $config
	 * @param $newListener
	 * @throws Exception
	 */
	private function onListenerBind($config, $newListener)
	{
		if ($config['type'] == self::HTTP) {
			$newListener->on('request', [Snowflake::createObject(OnRequest::class), 'onHandler']);
		} else if ($config['type'] == self::TCP || $config['type'] == self::PACKAGE) {
			$newListener->on('connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
			$newListener->on('close', [Snowflake::createObject(OnClose::class), 'onHandler']);
			$newListener->on('packet', [Snowflake::createObject(OnPacket::class), 'onHandler']);
			$newListener->on('receive', [Snowflake::createObject(OnReceive::class), 'onHandler']);

			var_dump($newListener);
		} else if ($config['type'] == self::WEBSOCKET) {
			throw new Exception('Base server must instanceof \Swoole\WebSocket\Server::class.');
		} else {
			throw new Exception('Unknown server type(' . $config['type'] . ').');
		}
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
