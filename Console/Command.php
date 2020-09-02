<?php


namespace Console;

use Snowflake\Abstracts\BaseObject;

/**
 * Class Command
 * @package Console
 */
abstract class Command extends BaseObject implements CommandInterface
{

	public $command = '';
	public $description = '';

	/**
	 * @return string
	 * 返回执行的命令名称
	 */
	public function getName()
	{
		return $this->command;
	}


	/**
	 * @return string
	 *
	 * 返回命令描述
	 */
	public function getDescription()
	{
		return $this->description;
	}

}
