<?php

declare(strict_types=1);
namespace Database\Base;


use Database\ActiveQuery;
use Database\ActiveRecord;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Class CollectionIterator
 * @package Database\Base
 */
class CollectionIterator extends \ArrayIterator
{

	private ActiveRecord $model;


	/** @var ActiveQuery */
	private ActiveQuery $query;


	/**
	 * CollectionIterator constructor.
	 * @param $model
	 * @param $query
	 * @param array $array
	 * @param int $flags
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function __construct($model, $query, $array = array(), $flags = 0)
	{
		$this->model = $model;
		if (is_string($model)) {
			$this->model = Snowflake::createObject($model);
		}
		$this->query = $query;
		parent::__construct($array, $flags);
	}


	/**
	 * @param $current
	 * @return ActiveRecord
	 */
	protected function newModel($current)
	{
		return (clone $this->model)->setAttributes($current);

	}


	/**
	 * @return ActiveRecord|mixed
	 */
	public function current()
	{
		$current = parent::current();
		if (!($current instanceof ActiveRecord)) {
			$current = $this->newModel($current);
		}
		return $this->query->getWith($current);
	}


}
