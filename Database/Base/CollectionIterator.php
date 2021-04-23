<?php

declare(strict_types=1);

namespace Database\Base;


use Database\ActiveQuery;
use Database\ActiveRecord;
use Exception;
use ReflectionException;
use Snowflake\Channel;
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


	public function clean()
	{
		unset($this->query);
	}


	/**
	 * CollectionIterator constructor.
	 * @param $model
	 * @param $query
	 * @param array $array
	 * @param int $flags
	 * @throws Exception
	 */
	public function __construct($model, $query, $array = array(), $flags = 0)
	{
		$this->model = $model;
		$this->query = $query;
		parent::__construct($array, $flags);
	}


	/**
	 * @param $current
	 * @return ActiveRecord
	 * @throws Exception
	 */
	protected function newModel($current): ActiveRecord
	{
		return $this->model::populate($current);
	}


	/**
	 * @throws Exception
	 */
	public function current(): ActiveRecord
	{
		if (is_array($current = parent::current())) {
			$current = $this->newModel($current);
		}
		return $this->query->getWith($current);
	}


}
