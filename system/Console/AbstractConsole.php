<?php


namespace Snowflake\Console;


use Exception;
use Swoole\Coroutine\Channel;

/**
 * Class AbstractConsole
 * @package BeReborn\Console
 */
abstract class AbstractConsole
{

	/**
	 * @var Command[]
	 */
	public $commands = [];

	/** @var Dtl $parameters */
	private $parameters;

	/** @var array */
	private $_config;

	/**
	 * @param array $config
	 * AbstractConsole constructor.
	 * @throws Exception
	 */
	public function __construct(array $config = [])
	{
		$this->_config = $config;
		$this->signCommand(\BeReborn::createObject(DefaultCommand::class));
	}

	/**
	 * @return array
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/**
	 * @return $this
	 */
	public function setParameters()
	{
		$this->parameters = new Dtl($_SERVER['argv']);
		return $this;
	}

	/**
	 * @param Command $command
	 * @return mixed
	 */
	public function execCommand(Command $command)
	{
		return $command->handler($this->parameters);
	}

	/**
	 * @return Command|null
	 */
	public function search()
	{
		$name = $this->parameters->getCommandName();
		$this->parameters->set('commandList', $this->getCommandList());
		foreach ($this->commands as $command) {
			if ($command->command != $name) {
				continue;
			}
			return $command;
		}
		return null;
	}

	/**
	 * @param Command $abstractConsole
	 *
	 * 注册命令
	 */
	public function signCommand(Command $abstractConsole)
	{
		$this->commands[] = $abstractConsole;
	}

	/**
	 * @param $kernel
	 * @throws Exception
	 */
	public function batch($kernel)
	{
		if (is_object($kernel)) {
			if (!property_exists($kernel, 'commands')) {
				return;
			}
			$kernel = $kernel->commands;
		}
		if (!is_array($kernel)) {
			return;
		}
		foreach ($kernel as $command) {
			$this->signCommand(\BeReborn::createObject($command));
		}
	}

	/**
	 * @param Command $abstractConsole
	 * 释放一个命令
	 */
	public function destroyCommand(Command $abstractConsole)
	{
		foreach ($this->commands as $index => $command) {
			if ($abstractConsole === $command) {
				unset($this->commands[$index]);
				break;
			}
		}
	}

	/**
	 * @return array
	 */
	private function getCommandList()
	{
		$_tmp = [];
		foreach ($this->commands as $command) {
			$_tmp[$command->command] = [$command->description, $command];
		}
		ksort($_tmp, SORT_ASC);
		return $_tmp;
	}


}
