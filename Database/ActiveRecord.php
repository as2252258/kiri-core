<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */
declare(strict_types=1);

namespace Database;


use Exception;
use Database\Base\BaseActiveRecord;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Database\Traits\HasBase;

defined('SAVE_FAIL') or define('SAVE_FAIL', 3227);
defined('FIND_OR_CREATE_MESSAGE') or define('FIND_OR_CREATE_MESSAGE', 'Create a new model, but the data cannot be empty.');

/**
 * Class Orm
 * @package Database
 *
 * @property $attributes
 * @property-read $oldAttributes
 * @method beforeSearch($model)
 */
class ActiveRecord extends BaseActiveRecord
{

	const DECR = 'decr';
	const INCR = 'incr';


	/**
	 * @return array
	 */
	public function rules(): array
	{
		return [];
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return ActiveRecord|false
	 * @throws Exception
	 */
	public function increment(string $column, int $value): bool|ActiveRecord
	{
		if (!$this->mathematics([$column => $value], '-')) {
			return false;
		}
		$this->{$column} += $value;
		return $this->refresh();
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return ActiveRecord|false
	 * @throws Exception
	 */
	public function decrement(string $column, int $value): bool|ActiveRecord
	{
		if (!$this->mathematics([$column => $value], '-')) {
			return false;
		}
		$this->{$column} -= $value;
		return $this->refresh();
	}


	/**
	 * @param array $columns
	 * @return ActiveRecord|false
	 * @throws Exception
	 */
	public function increments(array $columns): bool|static
	{
		if (!$this->mathematics($columns, '+')) {
			return false;
		}
		foreach ($columns as $key => $attribute) {
			$this->$key += $attribute;
		}
		return $this;
	}


	/**
	 * @param array $columns
	 * @return ActiveRecord|false
	 * @throws Exception
	 */
	public function decrements(array $columns): bool|static
	{
		if (!$this->mathematics($columns, '-')) {
			return false;
		}
		foreach ($columns as $key => $attribute) {
			$this->$key -= $attribute;
		}
		return $this;
	}

	/**
	 * @param array $condition
	 * @param array $attributes
	 * @return bool|ActiveRecord
	 * @throws ComponentException
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public static function findOrCreate(array $condition, array $attributes = []): bool|static
	{
		/** @var static $select */
		$select = static::find()->where($condition)->first();
		if (!empty($select)) {
			return $select;
		}
		if (empty($attributes)) {
			return \logger()->addError(FIND_OR_CREATE_MESSAGE, 'mysql');
		}

		$className = get_called_class();
		$select = objectPool($className, function () use ($className) {
			return new $className();
		});

		$select->attributes = $attributes;
		if (!$select->save()) {
			return \logger()->addError($select->getLastError(), 'mysql');
		}
		return $select;
	}


	/**
	 * @param $action
	 * @param $columns
	 * @param array $condition
	 * @return array|bool|int|string|null
	 * @throws Exception
	 */
	private function mathematics($columns, $action, $condition = []): int|bool|array|string|null
	{
		if (empty($condition)) {
			$condition = [$this->getPrimary() => $this->getPrimaryValue()];
		}

		$activeQuery = static::find()->where($condition);
		$create = SqlBuilder::builder($activeQuery)->mathematics($columns, $action);
		if (is_bool($create)) {
			return false;
		}
		return static::getDb()->createCommand($create[0], $create[1])->exec();
	}


	/**
	 * @param array $fields
	 * @return ActiveRecord|bool
	 * @throws Exception
	 */
	public function update(array $fields): static|bool
	{
		return $this->save($fields);
	}


	/**
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public static function inserts(array $data): bool
	{
		/** @var static $class */
		$class = Snowflake::createObject(static::className());
		if (empty($data)) {
			return $class->addError('Insert data empty.', 'mysql');
		}
		return $class::find()->batchInsert($data);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function delete(): bool
	{
		$conditions = $this->_oldAttributes;
		if (empty($conditions)) {
			return $this->addError("Delete condition do not empty.", 'mysql');
		}
		$primary = $this->getPrimary();

		if (!empty($primary)) {
			$conditions = [$primary => $this->getAttribute($primary)];
		}
		return static::deleteByCondition($conditions);
	}


	/**
	 * @param       $condition
	 * @param array $attributes
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function updateAll(mixed $condition, $attributes = []): bool
	{
		$condition = static::find()->where($condition);
		return $condition->batchUpdate($attributes);
	}

	/**
	 * @param       $condition
	 * @param array $attributes
	 *
	 * @return array|Collection
	 * @throws Exception
	 */
	public static function findAll($condition, $attributes = []): array|Collection
	{
		$query = static::find()->where($condition);
		if (!empty($attributes)) {
			$query->bindParams($attributes);
		}
		return $query->all();
	}

	/**
	 * @param $method
	 * @return mixed
	 * @throws Exception
	 */
	private function resolveObject($method): mixed
	{
		$resolve = $this->{$this->getRelate($method)}();
		if ($resolve instanceof HasBase) {
			$resolve = $resolve->get();
		}
		if ($resolve instanceof Collection) {
			return $resolve->toArray();
		} else if ($resolve instanceof ActiveRecord) {
			return $resolve->toArray();
		} else if (is_object($resolve)) {
			return get_object_vars($resolve);
		} else {
			return $resolve;
		}
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	public function toArray(): array
	{
		$data = $this->_attributes;
		foreach ($this->getAnnotation(self::ANNOTATION_GET) as $key => $item) {
			if (!isset($data[$key])) {
				continue;
			}
			$data[$key] = $this->runAnnotation($key, $data[$key]);
		}
		$data = array_merge($data, $this->runRelate());
		$this->recover();
		return $data;
	}


	/**
	 * @throws ComponentException
	 */
	public function recover()
	{
		objectRecover(get_called_class(), $this);
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function runRelate(): array
	{
		$relates = [];
		if (empty($with = $this->getWith())) {
			return $relates;
		}
		foreach ($with as $val) {
			$relates[$val] = $this->resolveObject($val);
		}
		return $relates;
	}


	/**
	 * @param $data
	 * @return ActiveRecord
	 */
	public function setWith($data): static
	{
		$this->_with = $data;
		var_dump(get_called_class(), $this->_with);
		return $this;
	}


	/**
	 * @return array|null
	 */
	public function getWith(): array|null
	{
		var_dump(get_called_class(), $this->_with);
		return $this->_with;
	}


	/**
	 * @param string $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return ActiveQuery
	 * @throws Exception
	 */
	public function hasOne(string $modelName, $foreignKey, $localKey): mixed
	{
		if (!$this->has($localKey)) {
			throw new Exception("Need join table primary key.");
		}

		$value = $this->getAttribute($localKey);

		$relation = $this->getRelation();

		return new HasOne($modelName, $foreignKey, $value, $relation);
	}


	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return ActiveQuery
	 * @throws Exception
	 */
	public function hasCount($modelName, $foreignKey, $localKey): mixed
	{
		if (!$this->has($localKey)) {
			throw new Exception("Need join table primary key.");
		}

		$value = $this->getAttribute($localKey);

		$relation = $this->getRelation();

		return new HasCount($modelName, $foreignKey, $value, $relation);
	}


	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return ActiveQuery
	 * @throws Exception
	 */
	public function hasMany($modelName, $foreignKey, $localKey): mixed
	{
		if (!$this->has($localKey)) {
			throw new Exception("Need join table primary key.");
		}

		$value = $this->getAttribute($localKey);

		$relation = $this->getRelation();

		return new HasMany($modelName, $foreignKey, $value, $relation);
	}

	/**
	 * @param $modelName
	 * @param $foreignKey
	 * @param $localKey
	 * @return ActiveQuery
	 * @throws Exception
	 */
	public function hasIn($modelName, $foreignKey, $localKey): mixed
	{
		if (!$this->has($localKey)) {
			throw new Exception("Need join table primary key.");
		}

		$value = $this->getAttribute($localKey);

		$relation = $this->getRelation();

		return new HasMany($modelName, $foreignKey, $value, $relation);
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function afterDelete(): bool
	{
		if (!$this->hasPrimary()) {
			return TRUE;
		}
		$value = $this->getPrimaryValue();
		if (empty($value)) {
			return TRUE;
		}
		return TRUE;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function beforeDelete(): bool
	{
		if (!$this->hasPrimary()) {
			return TRUE;
		}
		$value = $this->getPrimaryValue();
		if (empty($value)) {
			return TRUE;
		}
		return TRUE;
	}
}
