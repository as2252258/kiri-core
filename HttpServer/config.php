<?php

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
					$router = Snowflake::app()->router;
					$router->loadRouterSetting();
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
			'events'   => [
				Event::SERVER_WORKER_START => function () {
					$path = APP_PATH . 'app/Websocket';
					$websocket = Snowflake::app()->annotation->websocket;
					$websocket->registration_notes($path, 'App\\Sockets\\');
				},
				Event::SERVER_HANDSHAKE    => function (Request $request, Response $response) {
					$this->error($request->fd . ' connect.');
					$response->status(101);
					$response->end();
				},
				Event::SERVER_MESSAGE      => function (\Swoole\WebSocket\Server $server, Frame $frame) {
					$this->error('websocket SERVER_MESSAGE.');
					if (is_null($json = json_decode($frame->data, true))) {
						return $server->push($frame->fd, 'format error~');
					}
					$websocket = Snowflake::app()->annotation->websocket;
					if ($websocket->has($json['path'])) {
						return $websocket->runWith($json['path'], [$frame->fd, $json]);
					} else {
						return $server->push($frame->fd, 'hello word~');
					}
				},
				Event::SERVER_CLOSE        => function (int $fd) {
					$this->error($fd . ' disconnect.');
					return 'hello word~';
				}
			]
		],
	]
];
