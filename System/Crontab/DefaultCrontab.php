<?php


namespace Snowflake\Crontab;


/**
 * Class DefaultCrontab
 * @package Snowflake\Crontab
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
	public function max_execute(): void
	{
		// TODO: Implement max_execute() method.
	}
}
