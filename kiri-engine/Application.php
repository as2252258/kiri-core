<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Kiri;


use Closure;
use Database\DatabasesProviders;
use Exception;
use Kiri\Abstracts\{BaseApplication, Config, Kernel};
use Kiri\Crontab\CrontabProviders;
use Kiri\Events\{OnAfterCommandExecute, OnBeforeCommandExecute};
use Kiri\FileListen\HotReload;
use ReflectionException;
use Server\ServerProviders;
use stdClass;
use Swoole\Process;
use Swoole\Timer;
use Symfony\Component\Console\{Application as ConsoleApplication,
	Command\Command,
	Input\ArgvInput,
	Input\InputInterface,
	Output\ConsoleOutput,
	Output\OutputInterface
};

/**
 * Class Init
 *
 * @package Kiri
 *
 * @property-read Config $config
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public string $id = 'uniqueId';


	public string $state = '';


	/** @var array<array<Process>> */
	private array $_process = [];


	/**
	 */
	public function init()
	{
		$this->import(ServerProviders::class);

		$this->register(Runtime::class);
	}


	/**
	 * @throws
	 */
	public function withDatabase()
	{
		$this->import(DatabasesProviders::class);
	}


	/**
	 * @throws
	 */
	public function withCrontab()
	{
		$this->import(CrontabProviders::class);
	}


	/**
	 * @param string $class
	 * @param Process $process
	 */
	public function addProcess(string $class, Process $process)
	{
	}


	/**
	 * @return Process[]
	 */
	public function getProcess(): array
	{
		return $this->_process;
	}


	/**
	 * @param string $class
	 * @return Process|null
	 */
	public function getProcessName(string $class): ?Process
	{
		return $this->_process[$class] ?? null;
	}


	/**
	 * @throws
	 */
	public function withFileChangeListen()
	{
		$container = Kiri::getDi();

		$console = $container->get(ConsoleApplication::class);
		$console->add($container->get(HotReload::class));
	}


	/**
	 * @param Closure|array $closure
	 * @return $this
	 * @throws Exception
	 */
	public function middleware(Closure|array $closure): static
	{
		return $this;
	}


	/**
	 * @param bool $useTree
	 * @return $this
	 * @throws Exception
	 */
	public function setUseTree(bool $useTree): static
	{
		return $this;
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
			$class->onImport($this);
		}
		return $this;
	}


	/**
	 * @param Kernel $kernel
	 * @return $this
	 */
	public function commands(Kernel $kernel): static
	{
		foreach ($kernel->getCommands() as $command) {
			$this->register($command);
		}
		return $this;
	}


	/**
	 * @param string $command
	 * @throws
	 */
	public function register(string $command)
	{
		di(ConsoleApplication::class)->add(di($command));
	}


	/**
	 * @param array $argv
	 * @return void
	 */
	public function execute(array $argv): void
	{
		ini_set('swoole.enable_preemptive_scheduler', 'On');
		ini_set('swoole.enable_library', 'On');
		[$input, $output] = $this->argument($argv);
		try {
			$console = di(ConsoleApplication::class);
			$command = $input->getFirstArgument();
			if (empty($command)) {
				$command = 'sw:server';
			}
			$command = $console->find($command);
			if ($command instanceof Command) {
				$this->enableFileChange($command, $input, $output);
			}
		} catch (\Throwable $exception) {
			$output->writeln(jTraceEx($exception));
		} finally {
			Timer::clearAll();
		}
	}


	/**
	 * @param $argv
	 * @return array
	 */
	private function argument($argv): array
	{
		return [new ArgvInput($argv), new ConsoleOutput()];
	}


	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function enableFileChange(Command $class, $input, $output): void
	{
		fire(new OnBeforeCommandExecute());
		if (!($class instanceof HotReload)) {
			scan_directory(directory('app'), 'App');
		}

		$this->container->setBindings(OutputInterface::class, $output);

		$class->run($input, $output);
		fire(new OnAfterCommandExecute());
		$output->writeln('ok' . PHP_EOL);
	}


	/**
	 * @param $className
	 * @param null $abstracts
	 * @return stdClass
	 * @throws Exception
	 */
	public function make($className, $abstracts = null): stdClass
	{
		return make($className, $abstracts);
	}
}
