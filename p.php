<?php


//
//function after(Process $process)
//{
//	Timer::after(random_int(500, 2000), function () use ($process) {
//		$process->write("11111");
//
//		after($process);
//	});
//}
//
//
//function read(Process $process, Coroutine\Channel $channel)
//{
//	$data = $process->read();
//
//	$channel->push($data);
//
//	var_dump($channel->length());
//
//	read($process, $channel);
//}
//
//$process = new Process(function (Process $process) {
//
//	$array = new Coroutine\Channel(9999999);
//
//	$barrier = Barrier::make();
//
//	Coroutine::create('read', $process, $array);
//	Coroutine::create(function (Coroutine\Channel $channel) {
//		var_dump(1111);
//		function ch(Coroutine\Channel $channel) {
//			$data = $channel->pop();
//
//			var_dump($data);
//
//			ch($channel);
//		};
//		ch($channel);
//	}, $array);
//	Barrier::wait($barrier);
//}, null, SWOOLE_UNIX_STREAM, true);
//$process->start();
//
//Coroutine\run(function () use ($process) {
//	after($process);
//});


var_dump(json_encode([
    "Datacenter"     => "dc1",
    "Node"           => "iz8vbi3edjyskl7kpuwudqz",
    "SkipNodeUpdate" => FALSE,
    "Service"        => [
        "ID"              => "redis1",
        "Service"         => "FriendRpcService",
        "Address"         => "172.26.221.211",
        "TaggedAddresses" => [
            "lan" => [
                "address" => "127.0.0.1",
                "port"    => 9627,
            ],
            "wan" => [
                "address" => "172.26.221.211",
                "port"    => 9627,
            ],
        ],
        "Meta"            => [
            "redis_version" => "4.0",
        ],
        "Port"            => 9627,
    ],
    "Check"          => [
        "Node"       => "iz8vbi3edjyskl7kpuwudqz",
        "CheckId"    => "service:redis1",
        "Name"       => "Redis health check",
        "Notes"      => "Script based health check",
        "Status"     => "passing",
        "ServiceID"  => "redis1",
        "Definition" => [
            "Http"                           => "http://172.26.221.211:9627",
            "Interval"                       => "5s",
            "Timeout"                        => "1s",
            "DeregisterCriticalServiceAfter" => "30s",
        ],
    ],
]));
