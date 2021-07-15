<?php


return [
	'servers' => [
		'settings' => [
			'worker_num'               => swoole_cpu_num() * 3,
			'reactor_num'              => swoole_cpu_num(),
			'log_file'                 => APP_PATH . 'storage/request.log',
			'stats_file'               => APP_PATH . 'storage/stats.log',
			'dispatch_mode'            => 3,
			'task_worker_num'          => 1,
			'enable_coroutine'         => true,
			'task_enable_coroutine'    => true,
			'daemonize'                => 0,
			'open_tcp_keepalive'       => 1,
			'heartbeat_check_interval' => 60,
			'heartbeat_idle_time'      => 600,
			'tcp_keepidle'             => 3,
			'tcp_keepinterval'         => 1,
			'tcp_keepcount'            => 2,
			'max_wait_time'            => 60,
			'reload_async'             => true,
			'tcp_fastopen'             => 1,
			'tcp_defer_accept'         => 1
		],
		'events'   => [
			BASEServerListener::SERVER_ON_PIPE_MESSAGE  => [],
			BASEServerListener::SERVER_ON_SHUTDOWN      => [],
			BASEServerListener::SERVER_ON_TASK          => [],
			BASEServerListener::SERVER_ON_WORKER_START  => [],
			BASEServerListener::SERVER_ON_WORKER_ERROR  => [],
			BASEServerListener::SERVER_ON_WORKER_EXIT   => [],
			BASEServerListener::SERVER_ON_WORKER_STOP   => [],
			BASEServerListener::SERVER_ON_MANAGER_START => [],
			BASEServerListener::SERVER_ON_MANAGER_STOP  => [],
			BASEServerListener::SERVER_ON_BEFORE_RELOAD => [],
			BASEServerListener::SERVER_ON_AFTER_RELOAD  => [],
			BASEServerListener::SERVER_ON_START         => [],
		],
		'handler'  => [
			[
				'type'     => BASEServerListener::SERVER_TYPE_WEBSOCKET,
				'host'     => '0.0.0.0',
				'port'     => 9001,
				'mode'     => SWOOLE_SOCK_TCP,
				'settings' => [
					'open_http_protocol'  => false,
					'open_http2_protocol' => false
				],
				'events'   => [
					BASEServerListener::SERVER_ON_CONNECT   => [WebSocketServerListener::class, 'onConnect'],
					BASEServerListener::SERVER_ON_HANDSHAKE => [WebSocketServerListener::class, 'onHandshake'],
					BASEServerListener::SERVER_ON_MESSAGE   => [WebSocketServerListener::class, 'onMessage'],
					BASEServerListener::SERVER_ON_CLOSE     => [WebSocketServerListener::class, 'onClose'],
				]
			], [
				'type'     => BASEServerListener::SERVER_TYPE_HTTP,
				'host'     => '0.0.0.0',
				'port'     => 9001,
				'mode'     => SWOOLE_SOCK_TCP,
				'events'   => [
					BASEServerListener::SERVER_ON_REQUEST => [HTTPServerListener::class, 'onRequest'],
				],
				'settings' => [
					'open_http_protocol'     => true,
					'open_http2_protocol'    => false,
					'upload_tmp_dir'         => APP_PATH . 'storage',
					'http_parse_cookie'      => true,
					'http_compression'       => true,
					'http_compression_level' => 5,
					'enable_unsafe_event'    => false,
				]
			], [
				'type'   => BASEServerListener::SERVER_TYPE_TCP,
				'host'   => '0.0.0.0',
				'port'   => 9001,
				'mode'   => SWOOLE_SOCK_TCP,
				'events' => [
					BASEServerListener::SERVER_ON_CONNECT => [TCPServerListener::class, 'onConnect'],
					BASEServerListener::SERVER_ON_RECEIVE => [TCPServerListener::class, 'onReceive'],
					BASEServerListener::SERVER_ON_CLOSE   => [TCPServerListener::class, 'onClose'],
				]
			], [
				'type'   => BASEServerListener::SERVER_TYPE_UDP,
				'host'   => '0.0.0.0',
				'port'   => 9001,
				'mode'   => SWOOLE_SOCK_TCP,
				'events' => [
					BASEServerListener::SERVER_ON_PACKET => [UDPServerListener::class, 'onPacket'],
				]
			],
		]
	]
];
