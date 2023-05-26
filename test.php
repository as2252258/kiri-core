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

//run(function () {
//
//	$client = new Client('47.92.194.207',8500);
//	$client->get('/v1/agent/services?filter=Service == FriendRpcService');
//	$client->close();
//	var_dump($client->getBody());
//
//});

$spl = new \SplPriorityQueue();
$spl->insert(1,0);
$spl->insert(2,0);
$spl->insert(3,0);
$spl->insert(4,0);
$spl->insert(5,0);
$spl->insert(6,0);
$spl->insert(7,0);


$spl->compare();
$spl->extract();

var_dump($spl);