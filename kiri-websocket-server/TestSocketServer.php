<?php


class TestSocketServer
{

	public function onHandshake($request, $response): int
	{
		return 200;
	}


	public function onMessage($server, $frame): void
	{
		// TODO: Implement onMessage() method.
	}


	public function onClose($server, int $fd): void
	{
		// TODO: Implement onClose() method.
	}


	public function onOpen($server, $request): void
	{
		// TODO: Implement onOpen() method.
	}


}

var_dump(is_callable(new TestSocketServer(), true));

//
//Router::addServer('ws', function () {
//	Router::get('/', 'TestSocketServer');
//});
