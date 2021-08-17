<?php


$str = '{"datacenter":"tencent-datacenter","data_dir":"/root/consul/data","log_file":"/root/consul/log/","log_level":"INFO","bind_addr":"0.0.0.0","client_addr":"0.0.0.0","node_name":"tencent-node","ui":true,"bootstrap_expect":1,"server":true,"acl":{"enabled":true,"default_policy":"deny","enable_token_persistence":true,"enable_key_list_policy":true}}';

//ini_set('memory_limit','3096M');
//
//use Snowflake\Application;
//
//require_once __DIR__ . '/vendor/autoload.php';
//$config = array_merge(
//	require_once __DIR__ . '/System/Process/config.php',
//	require_once __DIR__ . '/Http/config.php'
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


//require_once __DIR__ . '/function.php';
//require_once __DIR__ . '/Http/Client/Result.php';
//require_once __DIR__ . '/Http/Client/HttpParse.php';
//require_once __DIR__ . '/Http/Client/Curl.php';
//
//\Swoole\Coroutine::create(function () {
//	$curl = \Http\Client\Curl::NewRequest();
////	$curl->setCallback(function ($body) {
////		return $body;
////	});
////	var_dump($curl->get('https://www.baidu.com'));
////	$curl->setHost('www.baidu.com');
////	$curl->setIsSsl(true);
////	$curl->setCallback(function ($body) {
////		return $body;
////	});
//
////	var_dump($curl->get('https://test-api.zhuangb123.com/'));
////	var_dump($curl->post('https://test-api.zhuangb123.com/test'));
////	var_dump($curl->get('https://test-api.zhuangb123.com/2'));
////	var_dump($curl->get('https://test-api.zhuangb123.com/test'));
////	var_dump($curl->get('https://test-api.zhuangb123.com/'));
////	var_dump($curl->get('https://test-api.zhuangb123.com/test'));
////
////	$curl->clean('/test', [$curl::GET, $curl::POST]);
////	$curl->clean('/', [$curl::GET]);
////
////
//	$curl->setHost('47.92.194.207');
//	$curl->setPort(6602);
//	var_dump($curl->get('/'));
////	var_dump($curl->post('/test'));
////	var_dump($curl->get('/'));
////	var_dump($curl->get('/test'));
////	var_dump($curl->get('/'));
////	var_dump($curl->get('/test'));
////	var_dump();
//});
//
//
////
//
////(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})
////preg_match('/((http[s]?):\/\/)?(([\w-_]+\.)+\w+(:\d+)?)/', 'asdas.asd:4554/asdhagsdahsjs/asdassd/asdasd/as/d?id=akjsda&sdjkd=asdjasd', $out);
////var_dump($out);
//
//
////[$uri, $host, $isHttps, $domain, $_1, $_2, $path] = $out;
//// $out[0],$out[1],$out[3],$out[4],$out[7]

//
//$process = new \Swoole\Process(function (\Swoole\Process $process) {
//	try {
//		Swoole\Process::signal(9 | 15, function ($signo) {
//			var_dump($signo);
//			echo "shutdown.";
//			file_put_contents(__DIR__ . '/log', __DIR__);
//		});
//		Swoole\Event::wait();
//	}catch (Throwable $exception){
//		var_dump($exception);
//	}
//	var_dump($process->pid);
//
//
//	while (true) {
//		Swoole\Coroutine::sleep(1);
//	}
//
//}, false, 1, true);
//$process->start();
//

//$client = function ($name, callable $createCallback, callable $check, callable $release) {
//	static $null = null;
//	if (empty($null)) {
//		$null = call_user_func($createCallback);
//	}
//	if (!call_user_func($check, $null)) {
//		throw new Exception('check error.');
//	}
//	return $null;
//};
//
//
//$client(
//	'MyBuy',
//	function () {
//		return new stdClass();
//	},
//	function ($class) {
//		return $class ? true : false;
//	},
//	function ($class) {
//		unset($class);
//	}
//);
////
////
//
//
//error_reporting(E_ALL);
//
//$a['hello'] = base64_encode(random_bytes(1000));
//$a['world'] = 'hello';
//$a['int'] = rand(1, 999999);
//$a['list'] = ['a,', 'b', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'];
//
//$val = serialize($a);
//$str = pack('N', strlen($val)) . $val . "\r\n";
//
//var_dump(swoole_substr_unserialize($val, strlen($val)));
//
//var_dump($str, unpack('N', pack('N', strlen($val))));

//var_dump( openssl_get_cipher_methods());
foreach (openssl_get_cipher_methods() as $openssl_get_cipher_method) {

	$iv = '';

	if (openssl_cipher_iv_length($openssl_get_cipher_method) > 0) {
		continue;
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($openssl_get_cipher_method));
	}

	$tag = '';

	$result = openssl_encrypt(json_encode([
		'time'  => microtime(),
		'scene' => 'application',
		'ot'    => 1
	]), $openssl_get_cipher_method, "xl.zhuangb123.com", 0, $iv, $tag);


	echo $openssl_get_cipher_method . ',';

//	if ($result != false) {
//		echo str_pad($openssl_get_cipher_method, 30, ' ', STR_PAD_RIGHT) . '=> ' . str_replace('=', '', $result) . PHP_EOL;
//		echo str_pad($openssl_get_cipher_method, 30, ' ', STR_PAD_RIGHT) . '=> ' . openssl_decrypt(str_replace('=', '', $result),
//				$openssl_get_cipher_method, "xl.zhuangb123.com", 0, $iv, $tag) . PHP_EOL;
//	}
}

