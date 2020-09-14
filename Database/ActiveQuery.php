<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 14:42
 */

namespace Database;

use Snowflake\Abstracts\Component;
use Database\Orm\Select;
use Database\Traits\QueryTrait;
use Exception;
use Snowflake\Snowflake;

/**
 * Class ActiveQuery
 * @package Database
 */
class ActiveQuery extends Component
{

	use QueryTrait;

	/** @var array */
	public $with = [];

	/** @var bool */
	public $asArray = FALSE;

	/** @var bool */
	public $useCache = FALSE;

	/** @var Connection $db */
	public $db = NULL;

	/**
	 * @var array
	 * 参数绑定
	 */
	public $attributes = [];


	/** @var ActiveRecord */
	public $modelClass;

	/**
	 * Comply constructor.
	 * @param $model
	 * @param array $config
	 * @throws
	 */
	public function __construct($model, $config = [])
	{
		if (!is_object($model)) {
			$model = Snowflake::createObject($model);
		}
		$this->modelClass = $model;
		parent::__construct($config);
	}

	/**
	 *
	 */
	public function afterInit()
	{
		$this->debug(get_called_class() . ' AFTER INIT.');
		$this->clear();
	}

	/**
	 * 清除不完整数据
	 */
	public function clear()
	{
		$this->db = null;
		$this->useCache = false;
		$this->with = [];
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function addParam($key, $value)
	{
		$this->attributes[$key] = $value;
		return $this;
	}

	/**
	 * @param array $values
	 * @return $this
	 */
	public function addParams(array $values)
	{
		foreach ($values as $key => $val) {
			$this->addParam($key, $val);
		}
		return $this;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function with($name)
	{
		if (empty($name)) {
			return $this;
		}
		if (is_string($name)) {
			$name = explode(',', $name);
		}
		foreach ($name as $key => $val) {
			array_push($this->with, $val);
		}
		return $this;
	}

	/**
	 * @param bool $isArray
	 * @return $this
	 */
	public function asArray($isArray = TRUE)
	{
		$this->asArray = $isArray;
		return $this;
	}

	/**
	 * @return ActiveRecord
	 * @throws
	 */
	public function first()
	{
		$data = $this->modelClass::getDb()
			->createCommand($this->oneLimit()->queryBuilder())
			->one();

		if (empty($data)) {
			return NULL;
		}
		$newModel = $this->modelClass;
		$newModel = $this->populate($newModel, $data);
		if ($this->asArray) {
			return $newModel->toArray();
		}
		return $newModel;
	}

	/**
	 * @return array|Collection
	 */
	public function get()
	{
		return $this->all();
	}


	/**
	 * @throws Exception
	 */
	public function flush()
	{
		$sql = $this->getChange()->truncate($this->getTable());
		return $this->command($sql)->flush();
	}


	/**
	 * @param int $size
	 * @param callable $callback
	 * @param mixed $param
	 * @param int $offset
	 * @param int $total
	 * @throws Exception
	 */
	public function plunk(int $size, callable $callback, $param = null, $offset = 0, $total = -1)
	{
		$pagination = new Pagination($this);
		$pagination->setOffset($offset);
		$pagination->setLimit($size);
		$pagination->setMax($total);
		$pagination->setCallback($callback);
		$pagination->search($param);
	}

	/**
	 * @param string $field
	 * @param string $setKey
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public function column(string $field, $setKey = '')
	{
		return $this->all()->column($field, $setKey);
	}

	/**
	 * @return array|Collection
	 * @throws
	 */
	public function all()
	{
		$collect = new Collection($this, $this->modelClass::getDb()
			->createCommand($this->queryBuilder())
			->all(), $this->modelClass);
		if ($this->asArray) {
			return $collect->toArray();
		}
		return $collect;
	}

	/**
	 * @param ActiveRecord $model
	 * @param $data
	 * @return ActiveRecord
	 * @throws Exception
	 */
	public function populate(ActiveRecord $model, $data)
	{
		return $this->getWith($model::populate($data));
	}


	/**
	 * @param $model
	 * @return mixed
	 */
	public function getWith($model)
	{
		if (empty($this->with) || !is_array($this->with)) {
			return $model;
		}
		foreach ($this->with as $val) {
			$method = 'get' . ucfirst($val);
			if (!method_exists($model, $method)) {
				continue;
			}
			$model->setRelate($val, $method);
		}
		return $model;
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function count()
	{
		$data = $this->modelClass::getDb()
			->createCommand($this->getBuild()->count($this))
			->one();
		if ($data && is_array($data)) {
			return (int)array_shift($data);
		}
		return 0;
	}


	/**
	 * @param array $data
	 * @return array|Command|bool|int|string
	 * @throws Exception
	 */
	public function batchUpdate(array $data)
	{
		return $this->getDb()->createCommand()
			->batchUpdate($this->getTable(), $data, $this->getCondition())
			->exec();
	}

	/**
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function batchInsert(array $data)
	{
		return $this->getDb()->createCommand()
			->batchInsert($this->getTable(), $data)
			->exec();
	}

	/**
	 * @param $filed
	 *
	 * @return null
	 * @throws Exception
	 */
	public function value($filed)
	{
		$first = $this->first()->toArray();
		return $first[$filed] ?? null;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function exists()
	{
		$column = $this->modelClass::getDb()
			->createCommand($this->queryBuilder())
			->fetchColumn();
		return $column !== false;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function deleteAll()
	{
		return $this->modelClass::getDb()
			->createCommand($this->queryBuilder())
			->delete();
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function delete()
	{
		return $this->deleteAll();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getCondition()
	{
		return $this->getBuild()->getWhere($this->where);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function queryBuilder()
	{
		return $this->getBuild()->getQuery($this);
	}

	/**
	 * @param $sql
	 * @param array $attr
	 * @return Command
	 * @throws Exception
	 */
	private function command($sql, $attr = [])
	{
		if (!empty($attr) && is_array($attr)) {
			$attr = array_merge($this->attributes, $attr);
		} else {
			$attr = $this->attributes;
		}
		return $this->getDb()->createCommand($sql, $attr)
			->setModelName($this->modelClass);
	}

	/**
	 * @return Select
	 * @throws Exception
	 */
	public function getBuild()
	{
		return $this->getDb()->getSchema()->getQueryBuilder();
	}

	/**
	 * @return orm\Change
	 * @throws Exception
	 */
	public function getChange()
	{
		return $this->getDb()->getSchema()->getChange();
	}

	/**
	 * @return Connection
	 * @throws Exception
	 */
	public function getDb()
	{
		return $this->modelClass::getDb();
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getPrimary()
	{
		return $this->modelClass->getPrimary();
	}
}
