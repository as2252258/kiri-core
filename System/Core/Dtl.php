<?php
declare(strict_types=1);


namespace Snowflake\Core;


use Exception;
use Snowflake\Abstracts\Component;

/**
 * Class Dtl
 * @package Snowflake\Core
 */
class Dtl extends Component
{

	protected array $params;


	/**
	 * Dtl constructor.
	 * @param $params
	 */
	public function __construct($params)
	{
		parent::__construct([]);
		$this->params = $params;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function toArray()
	{
		if (!is_array($this->params)) {
			return ArrayAccess::toArray($this->params);
		}
		return $this->params;
	}


	/**
	 * @param $name
	 * @return mixed|null
	 * @throws Exception
	 */
	public function get($name)
	{
		$array = $this->toArray();
		if (!isset($array[$name])) {
			return null;
		}
		return $array[$name];
	}


}
