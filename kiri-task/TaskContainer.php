<?php

namespace Kiri\Task;

use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Component;
use Kiri\Core\HashMap;
use Psr\Container\ContainerExceptionInterface;
use Kiri\Di\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Server;

class TaskContainer extends Component
{


	private HashMap $hashMap;


	/**
	 * @param ContainerInterface $container
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public ContainerInterface $container, array $config = [])
	{
		parent::__construct($config);
		$this->hashMap = new HashMap();
	}


	/**
	 * @param string $key
	 * @param $handler
	 */
	public function add(string $key, $handler)
	{
		$this->hashMap->put($key, $handler);
	}


	/**
	 * @param string $key
	 * @return bool
	 */
	#[Pure] public function has(string $key): bool
	{
		return $this->hashMap->has($key);
	}


	/**
	 * @param string $key
	 * @return OnTaskInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function get(string $key): OnTaskInterface
	{
		$task = $this->hashMap->get($key);
		if (is_string($task)) {
			$task = $this->container->get($task);
			if (!empty($task)) {
				$this->add($key, $task);
			}
		}
		return $task;
	}


}
