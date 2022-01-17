<?php

namespace Kiri\Server;

use Kiri;
use Kiri\Context;
use Kiri\Events\EventDispatch;
use Kiri\Server\Abstracts\BaseProcess;
use Kiri\Server\Contract\OnProcessInterface;
use Kiri\Server\Events\OnProcessStart;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Manager;
use Swoole\Process\Pool;


class CoroutineServer
{


	private Manager $manager;


	/**
	 * @param string|OnProcessInterface|BaseProcess $customProcess
	 * @param string $system
	 * @return void
	 */
	public function addProcess(string|OnProcessInterface|BaseProcess $customProcess, string $system)
	{
		if (is_string($customProcess)) {
			$customProcess = Kiri::getDi()->get($customProcess);
		}
		$this->manager->add(function (Pool $pool, int $workerId) use ($customProcess, $system) {
			$process = $pool->getProcess($workerId);

			if (Kiri::getPlatform()->isLinux()) {
				$process->name($system . '(' . $customProcess->getName() . ')');
			}

			Kiri::getDi()->get(EventDispatch::class)->dispatch(new OnProcessStart());

			set_env('environmental', Kiri::PROCESS);
			$channel = Coroutine::create(function () use ($process, $customProcess) {
				while (!$customProcess->isStop()) {
					$message = $process->read();
					if (!empty($message)) {
						$message = unserialize($message);
					}
					if (is_null($message)) {
						continue;
					}
					$customProcess->onBroadcast($message);
				}
			});
			Context::setContext('waite:process:message', $channel);

			$customProcess->onSigterm()->process($process);

		}, $customProcess->isEnableCoroutine());
	}


	/**
	 * @param array $settings
	 * @return void
	 */
	public function httpServer(array $settings = []): void
	{
		$this->manager->add(function (Pool $pool, int $workerId) use ($settings) {
			$host = $settings['host'];
			$port = $settings['port'];

			$server = new Server($host, $port, false, true);
			$server->set($settings);

			$callback = $settings['events'][Constant::REQUEST] ?? null;
			if (is_null($callback)) {
				$pool->shutdown();
				return;
			}
			if (is_string($callback[0])) {
				$callback[0] = Kiri::getDi()->get($callback[0]);
			}
			$server->handle('/', $callback);
			$server->start();
		}, true);
	}


	/**
	 * @param array $settings
	 * @return void
	 */
	public function websocketServer(array $settings)
	{
		$this->manager->add(function () use ($settings) {
			$host = $settings['host'];
			$port = $settings['port'];

			$server = new Server($host, $port, false, true);
			$server->set($settings);
			$hServer = \Kiri::getDi()->get(\Kiri\Message\Server::class);
			$server->handle('/', function (Request $request, Response $response) use ($hServer) {
				call_user_func([$hServer, 'onRequest'], $request, $response);
			});
			$server->start();
		}, true);

	}


}
