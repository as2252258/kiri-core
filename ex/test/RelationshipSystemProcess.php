<?php

use SInterface\CustomProcess;
use Swoole\Process;


/**
 * Class RelationshipSystemProcess
 */
class RelationshipSystemProcess implements CustomProcess
{


	/**
	 * RelationshipSystemProcess constructor.
	 * @param mixed $params
	 */
	public function __construct(public mixed $params)
	{

	}


	/**
	 * @param Process $process
	 * @return string
	 */
	public function getProcessName(Process $process): string
	{
		return 'system-service: ' . get_called_class() . '[' . $process->pid . ']';
	}


	/**
	 * @param mixed $data
	 */
	public function receive(mixed $data): void
	{

	}


	/**
	 *
	 */
	public function onHandler(Process $process): void
	{
		// TODO: Implement onHandler() method.
	}
}
