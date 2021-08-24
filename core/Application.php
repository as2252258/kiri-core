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
use Console\Console;
use Console\ConsoleProviders;
use Database\DatabasesProviders;
use Exception;
use Http\Command;
use Http\Context\Response;
use Http\Server;
use Http\ServerProviders;
use Kiri\Abstracts\BaseApplication;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Input;
use Kiri\Abstracts\Kernel;
use Kiri\Crontab\CrontabProviders;
use Kiri\Exception\NotFindClassException;
use Kiri\FileListen\FileChangeCustomProcess;
use ReflectionException;
use Server\ResponseInterface;
use stdClass;
use Swoole\Process;
use Swoole\Timer;

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
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$this->import(ConsoleProviders::class);
		$this->import(ServerProviders::class);
	}


	/**
	 * @throws NotFindClassException
	 */
	public function withDatabase()
	{
		$this->import(DatabasesProviders::class);
	}


	/**
	 * @throws NotFindClassException
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
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function withFileChangeListen()
	{
		$manager = $this->getServer();
		$manager->addProcess(FileChangeCustomProcess::class);

		putenv('enableFileChange=on');
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
			throw new NotFindClassException($service);
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
		/** @var Console $abstracts */
		$abstracts = $this->get('console');
		$abstracts->register($command);
	}


	/**
	 * @param Input $argv
	 * @return void
	 * @throws Exception
	 */
	public function execute(Input $argv): void
	{
		try {
			$this->register(Runtime::class);

			$manager = Kiri::app()->get('console');
			$class = $manager->setParameters($argv)->search();

			$this->enableFileChange($class);

			$data = $this->getBuilder($manager->exec($class));
		} catch (\Throwable $exception) {
			$data = $this->getBuilder(logger()->exception($exception));
		} finally {
			print_r($data);
			Timer::clearAll();
		}
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function enableFileChange($class): void
	{
		if (env('enableFileChange', 'off') == 'off' || !($class instanceof Command)) {
			scan_directory(directory('app'), 'App');
		}
	}


	/**
	 * @param $data
	 * @return Response|ResponseInterface
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function getBuilder($data): Response|ResponseInterface
	{
		return di(Response::class)->getBuilder($data);
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
