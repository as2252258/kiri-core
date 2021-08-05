<?php

namespace Snowflake\Crontab;

interface CrontabInterface
{


	/**
	 *
	 */
	public function onMaxExecute(): void;


	/**
	 * @return bool
	 */
	public function isStop(): bool;

}
