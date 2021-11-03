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

