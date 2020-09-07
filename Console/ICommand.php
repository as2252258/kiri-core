<?php


namespace Console;


use Snowflake\Abstracts\Input;

interface ICommand
{

	public function onHandler(Input $dtl);

}
