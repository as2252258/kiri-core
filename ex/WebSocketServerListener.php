<?php

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;

require_once 'ListenerHelper.php';


/**
 * Class WebSocketServerListener
 * @package HttpServer\Service
 */
class WebSocketServerListener
{

	protected static Server\Port $_http;


	/**
	 * UDPServerListener constructor.
	 * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public static function instance(mixed $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
		}
		static::$_http = $server->addlistener($host, $port, $mode);
		static::$_http->set($settings['settings'] ?? []);
		static::$_http->on('handshake', $settings['events'][BASEServerListener::SERVER_ON_HANDSHAKE] ?? [static::class, 'onHandshake']);
		static::$_http->on('message', $settings['events'][BASEServerListener::SERVER_ON_MESSAGE] ?? [static::class, 'onMessage']);
		static::$_http->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [static::class, 'onClose']);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public static function onHandshake(Request $request, Response $response)
	{
		/** @var \Swoole\WebSocket\Server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			throw new Exception('protocol error.', 500);
		}
		$key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
		$headers = [
			'Upgrade'               => 'websocket',
			'Connection'            => 'Upgrade',
			'Sec-websocket-Accept'  => $key,
			'Sec-websocket-Version' => '13',
		];
		if (isset($request->header['sec-websocket-protocol'])) {
			$headers['Sec-websocket-Protocol'] = $request->header['sec-websocket-protocol'];
		}
		foreach ($headers as $key => $val) {
			$response->setHeader($key, $val);
		}

		$response->setStatusCode(101);
		$response->end();
	}


//
//	public static function decode($received): ?string
//	{
//		$decoded = null;
//		$buffer = $received;
//		$len = ord($buffer[1]) & 127;
//		if ($len === 126) {
//			$masks = substr($buffer, 4, 4);
//			$data  = substr($buffer, 8);
//		} else {
//			if ($len === 127) {
//				$masks = substr($buffer, 10, 4);
//				$data  = substr($buffer, 14);
//			} else {
//				$masks = substr($buffer, 2, 4);
//				$data  = substr($buffer, 6);
//			}
//		}
//		for ($index = 0; $index < strlen($data); $index++) {
//			$decoded .= $data[$index] ^ $masks[$index % 4];
//		}
//
//		return $decoded;
//	}
//
//	const BINARY_TYPE_BLOB = "\x81";
//
//
//	public static function encode($buffer): string
//	{
//		$len = strlen($buffer);
//
//		$first_byte = self::BINARY_TYPE_BLOB;
//
//		if ($len <= 125) {
//			$encode_buffer = $first_byte . chr($len) . $buffer;
//		} else {
//			if ($len <= 65535) {
//				$encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
//			} else {
//				//pack("xxxN", $len)pack函数只处理2的32次方大小的文件，实际上2的32次方已经4G了。
//				$encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
//			}
//		}
//
//		return $encode_buffer;
//	}
//
//
//	private static function socketConnection($server, $fd, $data)
//	{
//		$http_protocol = [];
//		foreach ($data as $key => $datum) {
//			if (empty($datum) || $key == 0) {
//				continue;
//			}
//			[$key, $value] = explode(': ', $datum);
//
//			$http_protocol[trim($key)] = trim($value);
//		}
//
//		$key = base64_encode(sha1($http_protocol['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
//		$headers = [
//			'HTTP/1.1 101 Switching Protocols',
//			'Upgrade: websocket',
//			'Connection: Upgrade',
//			'Sec-WebSocket-Accept: ' . $key,
//			'Sec-WebSocket-Version: 13',
//		];
//		if (isset($http_protocol['Sec-WebSocket-Protocol'])) {
//			$headers[] = 'Sec-WebSocket-Protocol: ' . $http_protocol['Sec-WebSocket-Protocol'];
//		}
//		$server->send($fd, implode("\r\n", $headers) . "\r\n\r\n");
//	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onConnect(Server $server, int $fd)
	{
		var_dump(__FILE__ . ':' . __LINE__);
		$server->confirm($fd);
	}


	/**
	 * @param \Swoole\WebSocket\Server|Server $server
	 * @param Frame $frame
	 */
	public static function onMessage(\Swoole\WebSocket\Server|Server $server, Frame $frame)
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onClose(Server $server, int $fd)
	{
		var_dump($server->getClientInfo($fd));
	}

}
