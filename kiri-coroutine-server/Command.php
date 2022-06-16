<?php

namespace Kiri\CoroutineServer;

use Swoole\Coroutine;
use function Co\run;

class Command
{

	public array $arrays = [];


	/** @var array<Server> */
	private array $servers = [];


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function init(): void
	{
		$this->getServers();
		run(function () {
			$this->sig();

			$waite = new Coroutine\WaitGroup();
			foreach ($this->servers as $server) {
				$waite->add();
				$server->run($waite);
			}
			$waite->wait();
		});
	}


	/**
	 * @return void
	 */
	public function sig(): void
	{
		Coroutine::create(function () {
			$data = Coroutine::waitSignal(SIGTERM | SIGINT, -1);
			if ($data) {
				foreach ($this->servers as $server) {
					$server->stop();
				}
			}
		});
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function getServers(): void
	{
		foreach ($this->arrays as $array) {
			if (isset($this->servers[$array['name']])) {
				throw new \Exception('');
			}

			$server = new Server(\Kiri::getDi(), []);
			$server->setReusePort($array['reuse_port']);
			$server->setHost($array['host']);
			$server->setPort($array['port']);
			$server->setIsSsl($array['isSsl']);

			$this->servers[$array['name']] = $server;
		}
	}

}
