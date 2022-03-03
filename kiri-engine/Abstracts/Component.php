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
use Kiri\Error\StdoutLoggerInterface;

/**
 * Class Component
 * @package Kiri\Base
 */
class Component implements Configure
{


	protected ?StdoutLoggerInterface $logger = null;


	/**
	 * BaseAbstract constructor.
	 *
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(array $config = [])
	{
		if (is_null($this->logger)) {
			$this->logger = Kiri::getDi()->get(StdoutLoggerInterface::class);
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
