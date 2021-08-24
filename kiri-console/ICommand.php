<?php
declare(strict_types=1);

namespace Console;


use Kiri\Abstracts\Input;

interface ICommand
{

	public function onHandler(Input $dtl);

}
