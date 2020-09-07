<?php


namespace Database\Base;


use Database\ActiveRecord;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class CollectionIterator
 * @package Database\Base
 */
class CollectionIterator extends \ArrayIterator
{

	/** @var ActiveRecord */
	private $model;


	/**
	 * CollectionIterator constructor.
	 * @param $model
	 * @param array $array
	 * @param int $flags
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 */
	public function __construct($model, $array = array(), $flags = 0)
	{
		$this->model = $model;
		if (is_string($model)) {
			$this->model = Snowflake::createObject($model);
		}
		parent::__construct($array, $flags);
	}


	/**
	 * @return ActiveRecord|mixed
	 */
	public function current()
	{
		return (clone $this->model)->setAttributes(parent::current());
	}


}
