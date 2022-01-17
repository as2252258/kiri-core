<?php

namespace Kiri\Task;

use Exception;
use ReflectionException;

trait TaskResolve
{


	/**
	 * @param $handler
	 * @param $params
	 * @return object
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function handle($handler, $params): object
	{
		if (!class_exists($handler) && $this->hashMap->has($handler)) {
			$handler = $this->hashMap->get($handler);
		}
		$implements = $this->container->getReflect($handler);
		if (!in_array(OnTaskInterface::class, $implements->getInterfaceNames())) {
			throw new Exception('Task must instance ' . OnTaskInterface::class);
		}
		return $implements->newInstanceArgs($params);
	}

}
