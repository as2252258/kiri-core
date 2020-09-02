<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Snowflake;


use Exception;
use HttpServer\Server;
use ReflectionException;
use Snowflake\Abstracts\BaseApplication;
use Snowflake\Exception\NotFindClassException;

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
	 * @throws Exception
	 */
	public function init()
	{
		Snowflake::get()->processes->initCore();
	}


	/**
	 * @param string $service
	 * @return $this
	 * @throws NotFindClassException
	 * @throws ReflectionException
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
	 * @throws
	 */
	public function start()
	{
		$process = Snowflake::get()->processes;
		$process->start();
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
