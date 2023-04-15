<?php
declare(strict_types=1);

namespace Kiri\Actor;

use Swoole\Coroutine;

class ActorManager
{

	/** @var array<string, ActorInterface> */
	private array $nodes = [];


	/**
	 * @param Actor $actor
	 * @return void
	 */
	public function addActor(ActorInterface $actor): void
	{
		$this->nodes[$actor->uniqueId] = $actor;
		Coroutine::create(function (Actor $actor) {
			$actor->run();
		}, $actor);
	}


	/**
	 * @param $name
	 * @return void
	 */
	public function closeActor($name): void
	{
		$node = $this->nodes[$name] ?? null;
		if (is_null($node)) {
			return;
		}
		foreach ($node as $actor) {
			$actor->shutdown();
		}
	}


	/**
	 * @param $name
	 * @param $message
	 * @return bool
	 */
	public function write($name, $message): bool
	{
		$actor = $this->nodes[$name] ?? null;
		if (is_null($actor)) {
			return false;
		}
		return $actor->write($message);
	}


	/**
	 * @param $name
	 * @return array
	 */
	public function lists($name): array
	{
		$array = [];
		foreach ($this->nodes[$name] as $actor) {
			$array[] = [
				'id'      => $actor->getName(),
				'state'   => $actor->getState()->name,
				'runTime' => $actor->getRunTime()
			];
		}
		return $array;
	}


	/**
	 * @param string $uniqueId
	 * @return bool
	 */
	public function hasActor(string $uniqueId): bool
	{
		return isset($this->nodes[$uniqueId]) && $this->nodes[$uniqueId] instanceof ActorInterface;
	}


	/**
	 * @return void
	 */
	public function clean(): void
	{
		foreach ($this->nodes as $actor) {
			$actor->shutdown();
		}
		$this->nodes = [];
	}

}
