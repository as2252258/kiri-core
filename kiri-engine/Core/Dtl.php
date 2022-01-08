<?php
declare(strict_types=1);


namespace Kiri\Core;


use Exception;
use Kiri\Abstracts\Component;

/**
 * Class Dtl
 * @package Kiri\Core
 */
class Dtl extends Component
{

	protected array $params;


	/**
	 * Dtl constructor.
	 * @param $params
	 * @throws Exception
	 */
	public function __construct($params)
	{
		parent::__construct([]);
		$this->params = $params;
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return $this->params;
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function get($name): mixed
	{
		$array = $this->toArray();
		if (!isset($array[$name])) {
			return null;
		}
		return $array[$name];
	}


}
