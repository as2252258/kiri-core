<?php


namespace Database\Base;


use Database\ActiveRecord;


/**
 * Class CollectionIterator
 * @package Database\Base
 */
class CollectionIterator extends \ArrayIterator
{

	/** @var ActiveRecord */
	private $model;


	public function __construct($model, $array = array(), $flags = 0)
	{
		$this->model = $model;
		parent::__construct($array, $flags);
	}


	/**
	 * @return ActiveRecord|mixed
	 */
	public function current()
	{
		return $this->model::populate(parent::current());
	}


}
