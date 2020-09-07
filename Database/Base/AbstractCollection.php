<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/9 0009
 * Time: 9:44
 */

namespace Database\Base;


use ArrayIterator;
use Exception;
use Snowflake\Abstracts\Component;
use Database\ActiveRecord;
use Traversable;

/**
 * Class AbstractCollection
 * @package Database\Base
 */
abstract class AbstractCollection extends Component implements \IteratorAggregate, \ArrayAccess
{

	/**
	 * @var ActiveRecord[]
	 */
	protected $_item = [];

	/** @var ActiveRecord */
	protected $model;

	protected $query;

	/**
	 * Collection constructor.
	 *
	 * @param $query
	 * @param array $array
	 * @param null $model
	 */
	public function __construct($query, array $array = [], $model = null)
	{
		$this->_item = $array;
		$this->query = $query;
		$this->model = $model;

		parent::__construct([]);
	}


	/**
	 * @return int
	 */
	public function getLength()
	{
		return count($this->_item);
	}


	/**
	 * @param $item
	 */
	public function setItems($item)
	{
		$this->_item = $item;
	}


	/**
	 * @param $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * @param $item
	 */
	public function addItem($item)
	{
		array_push($this->_item, $item);
	}

	/**
	 * @return ArrayIterator|Traversable
	 * @throws Exception
	 */
	public function getIterator()
	{
		return new CollectionIterator($this->model, $this->_item);
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return !empty($this->_item) && isset($this->_item[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null|ActiveRecord
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			return NULL;
		}
		/** @var ActiveRecord $model */
		return $this->_item[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->_item[$offset] = $value;
	}


	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		if ($this->offsetExists($offset)) {
			unset($this->_item[$offset]);
		}
	}
}
