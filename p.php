<?php


use Swoole\Runtime;
use Swoole\Coroutine\Http\Server;

Runtime::enableCoroutine(true);

\Co\run(function () {
	Swoole\Coroutine::create(function () {
		var_dump(1);
	});
	$server = new Server('0.0.0.0',9501);
	$server->handle('/', function () {
	});
	$server->start();
});
