<?php


namespace HttpServer;

use HttpServer\Events\Http;
use HttpServer\Events\Receive;
use HttpServer\Events\Packet;
use HttpServer\Events\WebSocket;
use Exception;
use Snowflake\Snowflake;

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

	/**
	 * @param array $configs
	 * @return array
	 * @throws Exception
	 */
	public function initCore(array $configs)
	{
		$response = [];
		foreach ($configs as $server) {
			$response[] = $this->create($server);
		}
		return $response;
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
		switch ($config['type']) {
			case self::HTTP:
				$handler = [
					['request', [Http::class, 'onHandler']]
				];
				break;
			case self::TCP:
				$handler = [
					['receive', [Receive::class, 'onReceive']]
				];
				break;
			case self::PACKAGE:
				$handler = [
					['packet', [Packet::class, 'onHandler']]
				];
				break;
			case self::WEBSOCKET:
				$handler = [
					['handshake', [WebSocket::class, 'onHandshake']],
					['message', [WebSocket::class, 'onMessage']],
					['close', [WebSocket::class, 'onClose']],
				];
				break;
			default:
				throw new Exception('Unknown server type(' . $config['type'] . ').');
		}
		return [$this->server[$config['type']], $config, $handler, $settings];
	}


}
