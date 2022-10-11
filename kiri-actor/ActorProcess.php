<?php

namespace Kiri\Actor;

use Exception;
use Kiri\Di\ContainerInterface;
use Kiri\Server\Abstracts\BaseProcess;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Process;


class ActorProcess extends BaseProcess
{


	/**
	 * @var bool
	 */
	protected bool $enable_coroutine = true;


	/**
	 * @var string
	 */
	public string $name = 'actor-process';


	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(public ContainerInterface $container)
	{
	}


	/**
	 * @param Process $process
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	public function process(Process $process): void
	{
		// TODO: Implement process() method.
		$actorManager = $this->container->get(ActorManager::class);
		while (!$this->isStop()) {
			$read = json_decode($process->read(), true);
			if (is_null($read) || !isset($read['event'])) {
				continue;
			}
			switch ($read['event']) {
				case ActorState::MESSAGE:
					$actorManager->write($read['name'], $read['message']);
					break;
				case ActorState::CREATE:
					/** @var ActorInterface $actor */
					$actor = $this->container->create($read['class']);
					$actorManager->addActor($actor);
					break;
				case ActorState::SHUTDOWN:
					$actorManager->closeActor($read['name']);
					break;
			}
		}
	}


	/**
	 * @return $this
	 */
	public function onSigterm(): static
	{
		pcntl_signal(SIGTERM, function () {
			$this->onProcessStop();
		});
		return $this;
	}

}
