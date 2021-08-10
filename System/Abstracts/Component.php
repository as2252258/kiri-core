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
use Kiri\Exception\ComponentException;
use Kiri\Kiri;

/**
 * Class Component
 * @package Kiri\Kiri\Base
 */
class Component extends BaseObject
{


	/**
	 * @param $name
	 * @param $value
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		if (property_exists($this, $name)) {
			$this->$name = $value;
		} else {
			parent::__set($name, $value);
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name): mixed
	{
		if (property_exists($this, $name)) {
			return $this->$name ?? null;
		} else {
			return parent::__get($name);
		}
	}
}
