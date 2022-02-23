<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:28
 */
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri;
use Kiri\Di\Container;
use Kiri\Error\StdoutLogger;
use Kiri\Events\EventDispatch;
use Kiri\Events\EventProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Component
 * @package Kiri\Base
 * @property EventDispatch $eventDispatch
 * @property EventProvider $eventProvider
 * @property Container $container
 */
class Component implements Configure
{


	protected ?StdoutLogger $logger = null;


	/**
	 * BaseAbstract constructor.
	 *
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(array $config = [])
	{
		if (is_null($this->logger)) {
			$this->logger = Kiri::getDi()->get(StdoutLogger::class);
		}
		if (!empty($config) && is_array($config)) {
			Kiri::configure($this, $config);
		}
	}

	/**
	 * @throws Exception
	 */
	public function init()
	{
	}


	/**
	 * @return string
	 */
	#[Pure] public static function className(): string
	{
		return static::class;
	}


	/**
	 * @return Container|ContainerInterface
	 */
	#[Pure] public function getContainer(): ContainerInterface|Container
	{
		return Kiri::getDi();
	}


	/**
	 * @return EventProvider
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getEventProvider(): EventProvider
	{
		return $this->getContainer()->get(EventProvider::class);
	}


	/**
	 * @return EventDispatch
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function getEventDispatch(): EventDispatch
	{
		return $this->getContainer()->get(EventDispatch::class);
	}


	/**
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get(string $name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->{$method}();
		} else if (method_exists($this, $name)) {
			return $this->{$name};
		} else {
			throw new Exception('Unable getting property ' . get_called_class() . '::' . $name);
		}
	}


	/**
	 * @param string $name
	 * @param $value
	 * @return void
	 * @throws Exception
	 */
	public function __set(string $name, $value): void
	{
		$method = 'set' . ucfirst($name);
		if (method_exists($this, $method)) {
			$this->{$method}($value);
		} else if (method_exists($this, $name)) {
			$this->{$name} = $value;
		} else {
			throw new Exception('Unable setting property ' . get_called_class() . '::' . $name);
		}
	}


}
