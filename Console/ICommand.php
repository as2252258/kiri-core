<?php
declare(strict_types=1);

namespace Console;


use Snowflake\Abstracts\Input;

interface ICommand
{

	public function onHandler(Input $dtl);

}
