<?php

namespace Kiri\Process;

require_once 'OnProcessInterface.php';
require_once 'Process.php';

use function Co\run;

class TestProcess extends Process
{


	protected string $name = 'test process';


	/**
	 */
	public function onStart()
	{
	}


	public function handle()
	{
		// TODO: Implement doWhile() method.
	}


	public function onShutdown()
	{
		// TODO: Implement onShutdown() method.
	}
}

$array = [];

for ($i = 0; $i < 10; $i++) {
	$class = new TestProcess();
	$process = new \Swoole\Process([$class, 'start'], $class->getRedirectStdinAndStdout(),
		$class->getPipeType(), $class->isEnableCoroutine());
	$process->start();

	array_push($array, $process);
}
run(function () use ($array) {

	foreach ($array as $value) {
		var_dump($value->getCallback());
	}

});
