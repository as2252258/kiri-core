<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Snowflake;


use Console\Console;
use Console\ConsoleProviders;
use Console\Kernel;
use Database\DatabasesProviders;
use Exception;
use HttpServer\Server;
use HttpServer\ServerProviders;
use Snowflake\Abstracts\BaseApplication;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Exception\ComponentException;
use Swoole\Runtime;
use Swoole\Timer;

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
	public $id = 'uniqueId';


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
	public function import(string $service)
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
	 * @param string $command
	 * @throws ComponentException
	 */
	public function register(string $command)
	{
		/** @var Console $abstracts */
		$abstracts = $this->get('console');
		$abstracts->register($command);
	}


	/**
	 * @param $argv
	 * @return bool|string
	 * @throws
	 */
	public function start(Input $argv)
	{
		$this->set('input', $argv);
		try {
			$manager = Snowflake::app()->get('console');
			$manager->setParameters();
			$class = $manager->search();
			$params = response()->send($manager->execCommand($class));
		} catch (\Exception $exception) {
			$params = response()->send(implode("\n", [
				'Msg: ' . $exception->getMessage(),
				'Line: ' . $exception->getLine(),
				'File: ' . $exception->getFile()
			]));
		} finally {
			Timer::clearAll();
			return $params;
		}
	}


	/**
	 * @param $className
	 * @param null $abstracts
	 * @return mixed
	 * @throws Exception
	 */
	public function make($className, $abstracts = null)
	{
		return make($className, $abstracts);
	}
}
