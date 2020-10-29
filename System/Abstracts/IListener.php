<?php

declare(strict_types=1);
namespace Snowflake\Abstracts;


use Snowflake\Core\Dtl;

interface IListener
{


	public function handler(Dtl $dtl);


}
