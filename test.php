<?php


//ini_set('memory_limit','3096M');
//
//use Snowflake\Application;
//
//require_once __DIR__ . '/vendor/autoload.php';
//$config = array_merge(
//	require_once __DIR__ . '/System/Process/config.php',
//	require_once __DIR__ . '/HttpServer/config.php'
//);
//
//$application = new Application($config);
//$application->start();

//
//$replace = str_replace(__DIR__, '', __FILE__);
//var_dump(ltrim($replace, '/'));
//
//$comment = '@Handshake()';
//
//var_dump(preg_match('/@(Handshake)\((.*?)\)/', $comment, $events));
//var_dump($events);


require_once __DIR__ . '/function.php';
require_once __DIR__ . '/HttpServer/Client/Result.php';
require_once __DIR__ . '/HttpServer/Client/HttpParse.php';
require_once __DIR__ . '/HttpServer/Client/Curl.php';

\Swoole\Coroutine::create(function () {
	$curl = \HttpServer\Client\Curl::NewRequest();
//	$curl->setCallback(function ($body) {
//		return $body;
//	});
//	var_dump($curl->get('https://www.baidu.com'));
//	$curl->setHost('www.baidu.com');
//	$curl->setIsSsl(true);
//	$curl->setCallback(function ($body) {
//		return $body;
//	});

//	var_dump($curl->get('https://test-api.zhuangb123.com/'));
//	var_dump($curl->post('https://test-api.zhuangb123.com/test'));
//	var_dump($curl->get('https://test-api.zhuangb123.com/2'));
//	var_dump($curl->get('https://test-api.zhuangb123.com/test'));
//	var_dump($curl->get('https://test-api.zhuangb123.com/'));
//	var_dump($curl->get('https://test-api.zhuangb123.com/test'));
//
//	$curl->clean('/test', [$curl::GET, $curl::POST]);
//	$curl->clean('/', [$curl::GET]);
//
//
	$curl->setHost('47.92.194.207');
	$curl->setPort(6602);
	var_dump($curl->get('/'));
//	var_dump($curl->post('/test'));
//	var_dump($curl->get('/'));
//	var_dump($curl->get('/test'));
//	var_dump($curl->get('/'));
//	var_dump($curl->get('/test'));
//	var_dump();
});


//

//(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})
//preg_match('/((http[s]?):\/\/)?(([\w-_]+\.)+\w+(:\d+)?)/', 'asdas.asd:4554/asdhagsdahsjs/asdassd/asdasd/as/d?id=akjsda&sdjkd=asdjasd', $out);
//var_dump($out);


//[$uri, $host, $isHttps, $domain, $_1, $_2, $path] = $out;
// $out[0],$out[1],$out[3],$out[4],$out[7]
