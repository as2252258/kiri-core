<?php


return [
	'server' => [
		'settings' => [
			'worker_num'               => swoole_cpu_num(),
			'reactor_num'              => swoole_cpu_num(),
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
			'enable_delay_receive'     => true,
			'tcp_fastopen'             => 1,
			'tcp_defer_accept'         => 1
		],
		'events'   => [
			BASEServerListener::SERVER_ON_PIPE_MESSAGE  => [ServerBase::class, 'onPipeMessage'],
			BASEServerListener::SERVER_ON_SHUTDOWN      => [ServerBase::class, 'onShutdown'],
			BASEServerListener::SERVER_ON_WORKER_START  => [ServerWorker::class, 'onWorkerStart'],
			BASEServerListener::SERVER_ON_WORKER_ERROR  => [ServerWorker::class, 'onWorkerError'],
			BASEServerListener::SERVER_ON_WORKER_EXIT   => [ServerWorker::class, 'onWorkerExit'],
			BASEServerListener::SERVER_ON_WORKER_STOP   => [ServerWorker::class, 'onWorkerStop'],
			BASEServerListener::SERVER_ON_MANAGER_START => [ServerManager::class, 'onManagerStart'],
			BASEServerListener::SERVER_ON_MANAGER_STOP  => [ServerManager::class, 'onManagerStop'],
			BASEServerListener::SERVER_ON_BEFORE_RELOAD => [ServerBase::class, 'onBeforeReload'],
			BASEServerListener::SERVER_ON_AFTER_RELOAD  => [ServerBase::class, 'onAfterReload'],
			BASEServerListener::SERVER_ON_START         => [ServerBase::class, 'onStart'],
		],
		'ports'    => [
			[
				'type'     => BASEServerListener::SERVER_TYPE_HTTP,
				'host'     => '0.0.0.0',
				'port'     => 9002,
				'mode'     => SWOOLE_SOCK_TCP,
				'events'   => [
					BASEServerListener::SERVER_ON_REQUEST => [HTTPServerListener::class, 'onRequest'],
				],
				'settings' => [
					'open_http_protocol'     => true,
					'open_http2_protocol'    => false,
					'http_parse_cookie'      => true,
					'http_compression'       => true,
					'http_compression_level' => 5,
					'enable_unsafe_event'    => false,
				]
			],
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
			],
			[
				'type'   => BASEServerListener::SERVER_TYPE_TCP,
				'host'   => '0.0.0.0',
				'port'   => 9003,
				'mode'   => SWOOLE_SOCK_TCP,
				'events' => [
					BASEServerListener::SERVER_ON_CONNECT => [TCPServerListener::class, 'onConnect'],
					BASEServerListener::SERVER_ON_RECEIVE => [TCPServerListener::class, 'onReceive'],
					BASEServerListener::SERVER_ON_CLOSE   => [TCPServerListener::class, 'onClose'],
				]
			],
			[
				'type'   => BASEServerListener::SERVER_TYPE_UDP,
				'host'   => '0.0.0.0',
				'port'   => 9004,
				'mode'   => SWOOLE_SOCK_UDP,
				'events' => [
					BASEServerListener::SERVER_ON_PACKET => [UDPServerListener::class, 'onPacket'],
				]
			],
		]
	]
];
