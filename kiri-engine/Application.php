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
use Kiri\Abstracts\{BaseApplication, Kernel};
use Kiri\Di\LocalService;
use Kiri\Di\Scanner;
use Kiri\Error\ErrorHandler;
use Kiri\Events\{OnAfterCommandExecute, OnBeforeCommandExecute};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\{Application as ConsoleApplication,
	Input\ArgvInput,
	Output\ConsoleOutput,
	Output\OutputInterface
};


/**
 * Class Init
 *
 * @package Kiri
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public string $id = 'uniqueId';


	public string $state = '';


	/**
	 * @return void
	 * @throws ReflectionException
	 */
	public function init(): void
	{
		$error = Kiri::getDi()->get(ErrorHandler::class);
		$error->registerShutdownHandler(\config('error.shutdown', []));
		$error->registerExceptionHandler(\config('error.exception', []));
		$error->registerErrorHandler(\config('error.error', []));
		$this->id = \config('id', uniqid('id.'));
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
			$class->onImport(Kiri::getDi()->get(LocalService::class));
		}
		return $this;
	}


	/**
	 * @param Kernel $kernel
	 * @return $this
	 * @throws ReflectionException
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
	 * @throws ReflectionException
	 */
	public function command(string $command): void
	{
		$container = Kiri::getDi();
		$console = $container->get(ConsoleApplication::class);
		$console->add($container->get($command));
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
		$container = Kiri::getDi();

		[$input, $output] = $this->argument($argv);
		$console = $container->get(ConsoleApplication::class);
		$command = $console->find($input->getFirstArgument());

		$scanner = $container->get(Scanner::class);
		$scanner->read(APP_PATH . 'app/');

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
		$container = Kiri::getDi();
		$input = new ArgvInput($argv);
		$container->bind(ArgvInput::class, $input);

		$output = new ConsoleOutput();
		$container->bind(OutputInterface::class, $output);

		return [$input, $output];
	}
}
