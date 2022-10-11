<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Kiri;


use Exception;
use Kiri;
use Kiri\Abstracts\{BaseMain, Config, Kernel};
use Kiri\Events\{OnAfterCommandExecute, OnBeforeCommandExecute};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\{Application as ConsoleApplication,
	Input\ArgvInput,
	Output\ConsoleOutput,
	Output\OutputInterface
};
use Kiri\Di\LocalService;
use Kiri\Exception\ConfigException;
use Kiri\Error\ErrorHandler;


/**
 * Class Init
 *
 * @package Kiri
 *
 * @property-read Config $config
 */
class Main extends BaseMain
{

	/**
	 * @var string
	 */
	public string $id = 'uniqueId';


	public string $state = '';


	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ConfigException
	 */
	public function init(): void
	{
		$error = $this->container->get(ErrorHandler::class);
		$error->registerShutdownHandler(Config::get('error.shutdown', []));
		$error->registerExceptionHandler(Config::get('error.exception', []));
		$error->registerErrorHandler(Config::get('error.error', []));
		$this->id = Config::get('id', uniqid('id.'));
	}

	/**
	 * @param string $service
	 * @return $this
	 * @throws
	 */
	public function import(string $service): static
	{
		if (!class_exists($service)) {
			return $this;
		}
		$class = Kiri::getDi()->get($service);
		if (method_exists($class, 'onImport')) {
			$class->onImport($this->container->get(LocalService::class));
		}
		return $this;
	}


	/**
	 * @param Kernel $kernel
	 * @return $this
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function commands(Kernel $kernel): static
	{
		foreach ($kernel->getCommands() as $command) {
			$this->command($command);
		}
		return $this;
	}


	/**
	 * @param string $command
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function command(string $command): void
	{
		$console = $this->container->get(ConsoleApplication::class);
		$console->add($this->container->get($command));
	}


	/**
	 * @param array $argv
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function execute(array $argv): void
	{
		[$input, $output] = $this->argument($argv);
		$console = $this->container->get(ConsoleApplication::class);
		$command = $console->find($input->getFirstArgument());

		fire(new OnBeforeCommandExecute());

		$command->run($input, $output);
		fire(new OnAfterCommandExecute());
		$output->writeln('ok' . PHP_EOL);
	}


	/**
	 * @param $argv
	 * @return array
	 */
	private function argument($argv): array
	{
		$input = new ArgvInput($argv);
		$this->container->setBindings(ArgvInput::class, $input);

		$output = new ConsoleOutput();
		$this->container->setBindings(OutputInterface::class, $output);

		return [$input, $output];
	}
}
