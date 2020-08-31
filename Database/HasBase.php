<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 15:47
 */

namespace Database;

use Exception;

/**
 * Class HasBase
 * @package Database
 *
 * @include Query
 */
abstract class HasBase
{

	/** @var ActiveRecord|Collection */
	protected $data;

	/** @var ActiveRecord */
	protected $model;

	/** @var */
	protected $primaryId;

	/** @var array */
	protected $value = [];


	/** @var Relation $_relation */
	protected $_relation;

	/**
	 * HasBase constructor.
	 * @param ActiveRecord $model
	 * @param $primaryId
	 * @param $value
	 * @param Relation $relation
	 * @throws Exception
	 */
	public function __construct($model, $primaryId, $value, $relation)
	{
		if (is_array($value)) {
			if (empty($value)) $value = [];
			$_model = $model::find()->in($primaryId, $value);
		} else {
			$_model = $model::find()->where(['t1.' . $primaryId => $value]);
		}

		$this->_relation = $relation->bindIdentification($model, $_model);

		$this->model = $model;
		$this->primaryId = $primaryId;
		$this->value = $value;
	}

	abstract public function get();

	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if (empty($this->value)) {
			return null;
		}
		return $this->get();
	}
}
