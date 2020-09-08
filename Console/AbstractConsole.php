<?php


namespace Console;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Input;
use Snowflake\Snowflake;
use Swoole\Coroutine\Channel;

/**
 * Class AbstractConsole
 * @package Console
 */
abstract class AbstractConsole extends Component
{

	/**
	 * @var Command[]
	 */
	public $commands = [
		DefaultCommand::class
	];

	/** @var Input $parameters */
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
		$this->signCommand(Snowflake::createObject(DefaultCommand::class));

		parent::__construct($config);
	}

	/**
	 * @param Input $input
	 * @return $this
	 */
	public function setParameters(Input $input)
	{
		$this->parameters = $input;
		return $this;
	}

	/**
	 * @param Command $command
	 * @return mixed
	 */
	public function execCommand(Command $command)
	{
		return $command->onHandler($this->parameters);
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
			$this->signCommand(Snowflake::createObject($command));
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
