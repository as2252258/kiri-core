<?php


namespace Kiri\Crontab;


/**
 * Class DefaultCrontab
 * @package Kiri\Crontab
 */
class DefaultCrontab extends Crontab
{


	/**
	 * @return bool
	 */
	public function isStop(): bool
	{
		return true;
	}


	/**
	 *
	 */
	public function process(): void
	{
		// TODO: Implement process() method.
	}


	/**
	 *
	 */
	public function onMaxExecute(): void
	{
		// TODO: Implement max_execute() method.
	}
}
