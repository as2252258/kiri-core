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
use Kiri\Abstracts\BaseApplication;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Kernel;
use Kiri\Crontab\CrontabProviders;
use Kiri\Exception\NotFindClassException;
use Kiri\FileListen\FileChangeCustomProcess;
use ReflectionException;
use Server\Events\OnBeforeCommandExecute;
use Server\ServerCommand;
use Server\ServerProviders;
use stdClass;
use Swoole\Process;
use Swoole\Timer;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

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
		if (!isset($this->_process[$class])) {
			$this->_process[$class] = [];
		}
		$this->_process[$class][] = $process;
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
		$manager = $this->getServer();
		$manager->addProcess(FileChangeCustomProcess::class);

		enable_file_modification_listening();
	}


	/**
	 * @param Closure|array $closure
	 * @return $this
	 * @throws Exception
	 */
	public function middleware(Closure|array $closure): static
	{
		$this->getRouter()->setMiddleware($closure);
		return $this;
	}


	/**
	 * @param bool $useTree
	 * @return $this
	 * @throws Exception
	 */
	public function setUseTree(bool $useTree): static
	{
		$this->getRouter()->setUseTree($useTree);
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
	 * @throws Exception
	 */
	public function execute(array $argv): void
	{
		[$input, $output] = $this->argument($argv);
		try {
			$console = di(ConsoleApplication::class);
			$command = $input->getFirstArgument();
			if (empty($command)) {
				$command = 'list';
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function enableFileChange(Command $class, $input, $output): void
	{
		fire(new OnBeforeCommandExecute());
		if (!($class instanceof ServerCommand)) {
			scan_directory(directory('app'), 'App');
		}
		$class->run($input, $output);
		$output->writeln('ok');
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
