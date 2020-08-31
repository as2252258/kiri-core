<?php


defined('CONNECT_HOST') or define('CONNECT_HOST', '');
defined('CONNECT_USER') or define('CONNECT_USER', '');
defined('CONNECT_PASS') or define('CONNECT_PASS', '');

return [
	'cache'     => [

		'file' => [
			'path' => strpos(null, 'data')
		],

		'redis' => [
			'host'         => '127.0.0.1',
			'port'         => '6379',
			'prefix'       => 'cache_',
			'auth'         => '',
			'databases'    => '0',
			'timeout'      => -1,
			'read_timeout' => -1,
		],
	],
	'databases' => [
		'db'     => [
			'id'          => 'db',
			'cds'         => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST,
			'username'    => CONNECT_USER,
			'password'    => CONNECT_PASS,
			'tablePrefix' => 'aircraftwar_',
			'maxNumber'   => 100,
			'slaveConfig' => [
				'cds'      => 'mysql:dbname=aircraftwar;host=' . CONNECT_HOST,
				'username' => CONNECT_USER,
				'password' => CONNECT_PASS
			],
		],
		'server' => [
			'id'          => 'server',
			'cds'         => 'mysql:dbname=server;host=' . CONNECT_HOST,
			'username'    => CONNECT_USER,
			'password'    => CONNECT_PASS,
			'tablePrefix' => 'master_',
			'maxNumber'   => 100,
			'slaveConfig' => [
				'cds'      => 'mysql:dbname=server;host=' . CONNECT_HOST,
				'username' => CONNECT_USER,
				'password' => CONNECT_PASS
			],
		],
		'game'   => [
			'id'          => 'game',
			'cds'         => 'mysql:dbname=game;host=' . CONNECT_HOST,
			'username'    => CONNECT_USER,
			'password'    => CONNECT_PASS,
			'maxNumber'   => 100,
			'tablePrefix' => 'game_',
			'slaveConfig' => [
				'cds'      => 'mysql:dbname=game;host=' . CONNECT_HOST,
				'username' => CONNECT_USER,
				'password' => CONNECT_PASS
			],
		],
	]
];
