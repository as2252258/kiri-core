<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */
declare(strict_types=1);

namespace Snowflake;


use Console\Console;
use Console\ConsoleProviders;
use Database\DatabasesProviders;
use Exception;
use HttpServer\ServerProviders;
use ReflectionException;
use Snowflake\Abstracts\BaseApplication;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Input;
use Snowflake\Abstracts\Kernel;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Exception\NotFindPropertyException;
use stdClass;
use Swoole\Timer;
use function Co\run;

/**
 * Class Init
 *
 * @package Snowflake
 *
 * @property-read Config $config
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public string $id = 'uniqueId';


	/**
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$this->import(ConsoleProviders::class);
		$this->import(DatabasesProviders::class);
		$this->import(ServerProviders::class);
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
		$class = Snowflake::createObject($service);
		if (method_exists($class, 'onImport')) {
			$class->onImport($this);
		}
		return $this;
	}


	/**
	 * @param $kernel
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
	public function start(Input $argv): void
	{
		try {
			ini_set('opcache.enable', '1');
			ini_set('opcache.enable_cli', '1');
			ini_set('opcache.jit_buffer_size', '100M');
			ini_set('opcache.jit', '1255');

			fire(Event::SERVER_BEFORE_START);

			$this->set('input', $argv);

			$manager = Snowflake::app()->get('console');
			$manager->setParameters($argv);
			$class = $manager->search();
			response()->send($manager->execCommand($class));
		} catch (\Throwable $exception) {
			response()->send(implode("\n", [
				'Msg: ' . $exception->getMessage(),
				'Line: ' . $exception->getLine(),
				'File: ' . $exception->getFile()
			]));
		} finally {
			Timer::clearAll();
		}
	}


	/**
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws NotFindPropertyException|NotFindClassException
	 */
	public function scan_system_annotation()
	{
		$this->debug('scan system files...');

		$annotation = Snowflake::app()->getAttributes();
		$annotation->read(__DIR__ . '/../Console/', 'Console', 'system');
		$annotation->read(__DIR__ . '/../Database/', 'Database', 'system');
		$annotation->read(__DIR__ . '/../Gii/', 'Gii', 'system');
		$annotation->read(__DIR__ . '/../HttpServer/', 'HttpServer', 'system');
		$annotation->read(__DIR__ . '/../Kafka/', 'Kafka', 'system');
		$annotation->read(__DIR__ . '/../System/', 'Snowflake', 'system');
		$annotation->read(__DIR__ . '/../Validator/', 'Validator', 'system');
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
