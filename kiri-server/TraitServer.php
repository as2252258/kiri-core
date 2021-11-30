<?php

namespace Kiri\Server;

use Kiri\Server\Contract\OnProcessInterface;

trait TraitServer
{


	protected array $process = [];


	/**
	 * @param OnProcessInterface|string $process
	 * @throws \Exception
	 */
	public function addProcess(OnProcessInterface|string $process)
	{
		if (is_string($process) && !in_array(OnProcessInterface::class, class_implements($process))) {
			throw new \Exception('Other Process must instance ' . OnProcessInterface::class);
		}
		$this->process[] = $process;
	}


	/**
	 * @param array $ports
	 * @return array
	 */
	public function sortService(array $ports): array
	{
		$array = [];
		foreach ($ports as $port) {
			if ($port['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
				array_unshift($array, $port);
			} else if ($port['type'] == Constant::SERVER_TYPE_HTTP) {
				if (!empty($array) && $array[0]['type'] == Constant::SERVER_TYPE_WEBSOCKET) {
					$array[] = $port;
				} else {
					array_unshift($array, $port);
				}
			} else {
				$array[] = $port;
			}
		}
		return $array;
	}

}
