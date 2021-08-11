<?php


use Server\Constant;

return [
	'rpc' => [
		'name'     => 'json-rpc',
		'type'     => Constant::SERVER_TYPE_TCP,
		'host'     => '0.0.0.0',
		'mode'     => SWOOLE_SOCK_TCP,
		'port'     => 5377,
		'setting'  => [
			'open_tcp_keepalive'      => true,
			'tcp_keepidle'            => 30,
			'tcp_keepinterval'        => 10,
			'tcp_keepcount'           => 10,
			'open_http_protocol'      => false,
			'open_websocket_protocol' => false,
		],
		'events'   => [
			Constant::CONNECT => [],
			Constant::CLOSE   => [],
		],
		'registry' => [
			'protocol' => 'consul',
			'address'  => [
				'host' => '47.14.25.45',
				'port' => 5527,
				'path' => ''
			],
		],

		'consumers' => [

		]
	]

];
