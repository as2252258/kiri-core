<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:29
 */

namespace Snowflake\Di;


use Snowflake\Exception\ComponentException;
use Snowflake\Abstracts\Component;
use Exception;
use Snowflake\Snowflake;

/**
 * Class Service
 * @package Snowflake\Snowflake\Di
 */
class Service extends Component
{

	private $_components = [];


	private $_definition = [];


	protected $_alias = [];

	/**
	 * @param $id
	 *
	 * @return mixed
	 * @throws
	 */
	public function get($id)
	{
		if (isset($this->_components[$id])) {
			return $this->_components[$id];
		}
		if (isset($this->_definition[$id])) {
			$object = $this->_definition[$id];
			if (!is_object($object)) {
				$object = Snowflake::createObject($object);
			}
		} else if (!isset($this->_alias[$id])) {
			throw new ComponentException("Unknown component ID: $id");
		} else {
			$id = $this->_alias[$id];

			$object = Snowflake::createObject($id);
		}
		return $this->_components[$id] = $object;
	}

	/**
	 * @param string $className
	 * @param string $alias
	 */
	public function setAlias(string $className, string $alias)
	{
		$this->_alias[$className] = $alias;
	}

	/**
	 * @param $id
	 * @param $definition
	 *
	 * @return callable|mixed|void
	 * @throws Exception
	 */
	public function set($id, $definition)
	{
		if ($definition === NULL) {
			return $this->remove($id);
		}

		unset($this->_components[$id]);
		if (is_object($definition) || is_callable($definition, TRUE)) {
			return $this->_definition[$id] = $definition;
		} else if (!is_array($definition)) {
			throw new ComponentException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
		}
		if (!isset($definition['class'])) {
			throw new ComponentException("The configuration for the \"$id\" component must contain a \"class\" element.");
		} else {
			$this->_definition[$id] = $definition;
		}
		return $this->_components[$id] = Snowflake::createObject($definition);
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id)
	{
		return isset($this->_definition[$id]) || isset($this->_components[$id]) || isset($this->_alias[$id]);
	}

	/**
	 * @param array $data
	 * @throws Exception
	 */
	public function setComponents(array $data)
	{
		foreach ($data as $key => $val) {
			$this->set($key, $val);
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name)
	{
		if ($this->has($name)) {
			return $this->get($name);
		}

		return parent::__get($name);
	}

	/**
	 * @param $id
	 */
	public function remove($id)
	{
		unset($this->_components[$id]);
		unset($this->_definition[$id]);
		if (isset($this->_alias[$id])) {
			unset($this->_components[$this->_alias[$id]]);
			unset($this->_definition[$this->_alias[$id]]);
			unset($this->_alias[$id]);
		}
	}
}
