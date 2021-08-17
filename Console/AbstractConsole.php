<?php
declare(strict_types=1);

namespace Console;


use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Input;
use Kiri\Kiri;
use Server\Events\OnAfterCommandExecute;
use Server\Events\OnBeforeCommandExecute;

/**
 * Class AbstractConsole
 * @package Console
 */
abstract class AbstractConsole extends Component
{

    /**
     * @var Command[]
     */
    public array $commands = [];

    /** @var Input $parameters */
    private Input $parameters;

    /** @var array */
    private array $_config;

    /**
     * @param array $config
     * AbstractConsole constructor.
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        $this->_config = $config;
        $this->signCommand(Kiri::createObject(DefaultCommand::class));

        parent::__construct($config);
    }

    /**
     * @param Input $input
     * @return $this
     */
    public function setParameters(Input $input): static
    {
        $this->parameters = $input;
        return $this;
    }

	/**
	 * @param Command $command
	 * @return mixed
	 * @throws Exception
	 */
    public function exec(Command $command): mixed
    {
        fire(new OnBeforeCommandExecute());

        $data = $command->onHandler($this->parameters);

        fire(new OnAfterCommandExecute($data));

        return $data;
    }

    /**
     * @return Command|null
     */
    public function search(): ?Command
    {
        $name = $this->parameters->getCommandName();
        $this->parameters->set('commandList', $this->getCommandList());

        $help = 'system:help';
        foreach ($this->commands as $command) {
            if ($command->command == $help) {
                $help = $command;
            }
            if ($command->command != $name) {
                continue;
            }
            return $command;
        }
        if (is_object($help)) {
            return $help;
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
            $this->signCommand(Kiri::createObject($command));
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
    private function getCommandList(): array
    {
        $_tmp = [];
        foreach ($this->commands as $command) {
            if ($command->command === 'system:help') {
                continue;
            }
            $_tmp[$command->command] = [$command->description, $command];
        }
        ksort($_tmp, SORT_ASC);
        return $_tmp;
    }


}
