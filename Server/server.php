<?php


use Server\Constant;
use Server\HTTPServerListener;
use Server\Manager\OnServerDefault;
use Server\Manager\OnServerManager;
use Server\TCPServerListener;
use Server\UDPServerListener;
use Server\WebSocketServerListener;
use Server\Worker\OnServerWorker;

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
			Constant::PIPE_MESSAGE  => [OnServerDefault::class, 'onPipeMessage'],
			Constant::SHUTDOWN      => [OnServerDefault::class, 'onShutdown'],
			Constant::WORKER_START  => [OnServerWorker::class, 'onWorkerStart'],
			Constant::WORKER_ERROR  => [OnServerWorker::class, 'onWorkerError'],
			Constant::WORKER_EXIT   => [OnServerWorker::class, 'onWorkerExit'],
			Constant::WORKER_STOP   => [OnServerWorker::class, 'onWorkerStop'],
			Constant::MANAGER_START => [OnServerManager::class, 'onManagerStart'],
			Constant::MANAGER_STOP  => [OnServerManager::class, 'onManagerStop'],
			Constant::BEFORE_RELOAD => [OnServerDefault::class, 'onBeforeReload'],
			Constant::AFTER_RELOAD  => [OnServerDefault::class, 'onAfterReload'],
			Constant::START         => [OnServerDefault::class, 'onStart'],
		],
		'ports'    => [
			[
				'type'     => Constant::SERVER_TYPE_HTTP,
				'host'     => '0.0.0.0',
				'port'     => 9002,
				'mode'     => SWOOLE_SOCK_TCP,
				'events'   => [
					Constant::REQUEST => [HTTPServerListener::class, 'onRequest'],
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
				'type'     => Constant::SERVER_TYPE_WEBSOCKET,
				'host'     => '0.0.0.0',
				'port'     => 9001,
				'mode'     => SWOOLE_SOCK_TCP,
				'settings' => [
					'open_http_protocol'  => false,
					'open_http2_protocol' => false
				],
				'events'   => [
					Constant::CONNECT   => [WebSocketServerListener::class, 'onConnect'],
					Constant::HANDSHAKE => [WebSocketServerListener::class, 'onHandshake'],
					Constant::MESSAGE   => [WebSocketServerListener::class, 'onMessage'],
					Constant::CLOSE     => [WebSocketServerListener::class, 'onClose'],
				]
			],
			[
				'type'   => Constant::SERVER_TYPE_TCP,
				'host'   => '0.0.0.0',
				'port'   => 9003,
				'mode'   => SWOOLE_SOCK_TCP,
				'events' => [
					Constant::CONNECT => [TCPServerListener::class, 'onConnect'],
					Constant::RECEIVE => [TCPServerListener::class, 'onReceive'],
					Constant::CLOSE   => [TCPServerListener::class, 'onClose'],
				]
			],
			[
				'type'   => Constant::SERVER_TYPE_UDP,
				'host'   => '0.0.0.0',
				'port'   => 9004,
				'mode'   => SWOOLE_SOCK_UDP,
				'events' => [
					Constant::PACKET => [UDPServerListener::class, 'onPacket'],
				]
			],
		]
	]
];
