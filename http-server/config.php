<?php

use HttpServer\Route\Router;
use HttpServer\Server;
use Snowflake\Snowflake;
use Snowflake\Event;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

return [
	'servers' => [
		[
			'id'       => '',
			'type'     => Server::HTTP,
			'host'     => '127.0.0.1',
			'port'     => 9527,
			'settings' => [
				'worker_num'       => 10,
				'enable_coroutine' => 1
			],
			'events'   => [
				Event::SERVER_WORKER_START => function () {
					$router = Snowflake::get()->router;
					$router->loader();
				},
			]
		],
		[
			'id'       => '',
			'type'     => Server::PACKAGE,
			'host'     => '127.0.0.1',
			'port'     => 9628,
			'settings' => [
				'worker_num'       => 10,
				'enable_coroutine' => 1
			],
			'message'  => [
				'pack'   => function ($data) {
					return \Snowflake\Core\JSON::encode($data);
				},
				'unpack' => function ($data) {
					return \Snowflake\Core\JSON::decode($data);
				},
			],
			'events'   => [
			]
		],
		[
			'id'       => '',
			'type'     => Server::TCP,
			'host'     => '127.0.0.1',
			'port'     => 9629,
			'settings' => [
				'worker_num'       => 10,
				'enable_coroutine' => 1
			],
			'message'  => [
				'pack'   => function ($data) {
					return \Snowflake\Core\JSON::encode($data);
				},
				'unpack' => function ($data) {
					return \Snowflake\Core\JSON::decode($data);
				},
			],
			'events'   => [
				Event::RECEIVE_CONNECTION => function ($data) {
					return 'hello word~';
				}
			]
		],
		[
			'id'       => '',
			'type'     => Server::WEBSOCKET,
			'host'     => '127.0.0.1',
			'port'     => 9530,
			'settings' => [
				'worker_num'       => 10,
				'enable_coroutine' => 1
			],
			'grpc'     => [
				'host'     => '127.0.0.1',
				'port'     => 5555,
				'mode'     => SWOOLE_SOCK_TCP,
				'receive'  => function ($server, int $fd, int $reactorId, string $data) {
					$server->push(1, 'success.');
					$server->send($fd, 'success.');

					$socket = Snowflake::get()->get(\HttpServer\Events\WebSocket::class);
					$socket->push(1, 'hello word~~~~~~~~~~~~~');
				},
				'settings' => []
			],
			'events'   => [
				Event::SERVER_WORKER_START => function () {
					$websocket = Snowflake::get()->annotation->websocket;
//					$websocket->path = $this->socketControllers;
					$websocket->namespace = 'App\\Sockets\\';
					$websocket->registration_notes();
				},
				Event::SERVER_HANDSHAKE    => function (Request $request, Response $response) {
					$this->error($request->fd . ' connect.');
					$response->status(101);
					$response->end();
				},
				Event::SERVER_MESSAGE      => function (\Swoole\WebSocket\Server $server, Frame $frame) {
					$this->error('websocket SERVER_MESSAGE.');

					return $server->push($frame->fd, 'hello word~');
				},
				Event::SERVER_CLOSE        => function (int $fd) {
					$this->error($fd . ' disconnect.');
					return 'hello word~';
				}
			]
		],
	]
];
