<?php

declare(strict_types=1);

namespace Snowflake\Abstracts;


use Snowflake\Core\Dtl;


/**
 * Interface IListener
 * @package Snowflake\Abstracts
 */
interface IListener
{


	/**
	 * @param Dtl $dtl
	 * @return mixed
	 */
	public function execute(Dtl $dtl): mixed;


}
