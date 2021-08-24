<?php

declare(strict_types=1);

namespace Kiri\Abstracts;


use Kiri\Core\Dtl;


/**
 * Interface IListener
 * @package Kiri\Abstracts
 */
interface IListener
{


	/**
	 * @param Dtl $dtl
	 * @return mixed
	 */
	public function execute(Dtl $dtl): mixed;


}
