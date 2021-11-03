<?php


namespace Ar;

//require_once "a.php";
//
//
//$time = microtime();
//
//echo \count([]) . PHP_EOL;
//
//echo $time . PHP_EOL;
//echo microtime() . PHP_EOL;
//
//
//
//$time = microtime();
//
//echo count([]) . PHP_EOL;
//
//echo $time . PHP_EOL;
//echo microtime() . PHP_EOL;
//


use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;

run(function () {

	$client = new Client('47.92.194.207',8500);
	$client->get('/v1/agent/services?filter=Service == FriendRpcService');
	$client->close();
	var_dump($client->getBody());

});
