<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */
declare(strict_types=1);

namespace Database\Base;


use Annotation\Event;
use Annotation\Inject;
use Annotation\Model\Get;
use Annotation\Model\Set;
use ArrayAccess;
use Database\SqlBuilder;
use Database\Traits\HasBase;
use HttpServer\Http\Context;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Database\ActiveQuery;
use Database\ActiveRecord;
use Database\Connection;
use Database\HasMany;
use Database\HasOne;
use Database\Mysql\Columns;
use Database\Relation;
use Exception;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use validator\Validator;
use Database\IOrm;
use Snowflake\Snowflake;
use Snowflake\Event as SEvent;

/**
 * Class BOrm
 *
 * @package Snowflake\Abstracts
 *
 * @property bool $isCreate
 * @method rules()
 * @method static tableName()
 */
abstract class BaseActiveRecord extends Component implements IOrm, ArrayAccess
{

	const AFTER_SAVE = 'after::save';
	const BEFORE_SAVE = 'before::save';


	const ANNOTATION_GET = 'get';
	const ANNOTATION_SET = 'set';


	#[Inject(SEvent::class)]
	protected ?SEvent $event;


	/** @var array */
	protected array $_attributes = [];

	/** @var array */
	protected array $_oldAttributes = [];

	/** @var array */
	protected array $_relate = [];

	/** @var null|string */
	protected ?string $primary = NULL;


	private array $_annotations = [];


	private array $_fields = [];

	/**
	 * @var bool
	 */
	protected bool $isNewExample = TRUE;

	protected array $actions = [];

	protected ?Relation $_relation;


	private array $_with = [];

	/**
	 * @param $data
	 * @return ActiveRecord
	 */
	public function setWith($data): static
	{
		if (empty($data)) {
			return $this;
		}
		$this->_with = $data;
		return $this;
	}


	/**
	 * @return array|null
	 */
	public function getWith(): array|null
	{
		return $this->_with;
	}


	/**
	 * @param SEvent $event
	 * 默认注入
	 */
	public function setEvent(SEvent $event)
	{
		$this->event = $event;
	}


	/**
	 * object init
	 */
	public function clean()
	{
		$this->_attributes = [];
		$this->_oldAttributes = [];
	}


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->_annotations = [];
		if (!Context::hasContext(Relation::class)) {
			$relation = Snowflake::createObject(Relation::class);
			$this->_relation = Context::setContext(Relation::class, $relation);
		} else {
			$this->_relation = Context::getContext(Relation::class);
		}
		$this->createAnnotation();
	}


	/**
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function createAnnotation()
	{
		$annotation = Snowflake::app()->getAttributes();
		$annotation->injectProperty($this);

		$methods = $annotation->getMethods(get_called_class());
		foreach ($methods as $method => $attributes) {
			foreach ($attributes as $attribute) {
				$attribute->execute([$this, $method]);
			}
		}
	}


	/**
	 * @param $name
	 * @param $method
	 * @return static
	 */
	public function addGets($name, $method): static
	{
		if (!isset($this->_annotations[self::ANNOTATION_GET])) {
			$this->_annotations[self::ANNOTATION_GET] = [];
		}
		$this->_annotations[self::ANNOTATION_GET][$name] = [$this, $method];
		return $this;
	}


	/**
	 * @param $name
	 * @param $method
	 * @return static
	 */
	public function addSets($name, $method): static
	{
		if (!isset($this->_annotations[self::ANNOTATION_SET])) {
			$this->_annotations[self::ANNOTATION_SET] = [];
		}
		$this->_annotations[self::ANNOTATION_SET][$name] = [$this, $method];
		return $this;
	}


	/**
	 * @return array
	 */
	public function getActions(): array
	{
		return $this->actions;
	}

	/**
	 * @return bool
	 */
	public function getIsCreate(): bool
	{
		return $this->isNewExample === TRUE;
	}

	/**
	 * @param bool $bool
	 * @return $this
	 */
	public function setIsCreate($bool = FALSE): static
	{
		$this->isNewExample = $bool;
		return $this;
	}

	/**
	 * @return mixed
	 * @throws ComponentException
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * get last exception or other error
	 */
	public function getLastError(): mixed
	{
		return Snowflake::app()->getLogger()->getLastError('mysql');
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function hasPrimary(): bool
	{
		if ($this->primary !== NULL) {
			return true;
		}
		$primary = static::getColumns()->getPrimaryKeys();
		if (!empty($primary)) {
			return $this->primary = is_array($primary) ? current($primary) : $primary;
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public function isAutoIncrement(): bool
	{
		return $this->getAutoIncrement() !== null;
	}

	/**
	 * @throws Exception
	 */
	public function getAutoIncrement(): int|string|null
	{
		return static::getColumns()->getAutoIncrement();
	}

	/**
	 * @return null|string
	 * @throws Exception
	 */
	public function getPrimary(): ?string
	{
		if (!$this->hasPrimary()) {
			return null;
		}
		return $this->primary;
	}

	/**
	 * @return int|null
	 * @throws Exception
	 */
	public function getPrimaryValue(): ?int
	{
		if (!$this->hasPrimary()) {
			return null;
		}
		return $this->getAttribute($this->primary);
	}

	/**
	 * @param $param
	 * @param null $db
	 * @return BaseActiveRecord|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function findOne($param, $db = NULL): static|null
	{
		if (is_bool($param)) {
			return null;
		}
		if (is_numeric($param)) {
			$param = static::getPrimaryCondition($param);
		}
		return static::find()->where($param)->first();
	}


	/**
	 * @param $param
	 * @return array
	 * @throws Exception
	 */
	private static function getPrimaryCondition($param): array
	{
		$primary = static::getColumns()->getPrimaryKeys();
		if (empty($primary)) {
			throw new Exception('Primary key cannot be empty.');
		}
		if (is_array($primary)) {
			$primary = current($primary);
		}
		return [$primary => $param];
	}


	/**
	 * @param null $field
	 * @return ActiveRecord|null
	 * @throws Exception
	 * @throws Exception
	 */
	public static function max($field = null): ?ActiveRecord
	{
		$columns = static::getColumns();
		if (empty($field)) {
			$field = $columns->getFirstPrimary();
		}
		$columns = $columns->get_fields();
		if (!isset($columns[$field])) {
			return null;
		}
		$first = static::find()->max($field)->first();
		if (empty($first)) {
			return null;
		}
		return $first[$field];
	}

	/**
	 * @return ActiveQuery
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public static function find(): ActiveQuery
	{
		return Snowflake::createObject(ActiveQuery::class, [get_called_class()]);
	}

	/**
	 * @param null $condition
	 * @param array $attributes
	 *
	 * @param bool $if_condition_is_null
	 * @return bool
	 * @throws Exception
	 */
	public static function deleteByCondition($condition = NULL, $attributes = [], $if_condition_is_null = false): bool
	{
		if (empty($condition)) {
			if (!$if_condition_is_null) {
				return false;
			}
			return static::find()->delete();
		}
		$model = static::find()->ifNotWhere($if_condition_is_null)->where($condition);
		if (!empty($attributes)) {
			$model->bindParams($attributes);
		}
		return $model->delete();
	}


	/**
	 * @return array
	 */
	public function getAttributes(): array
	{
		return $this->_attributes;
	}

	/**
	 * @return array
	 */
	public function getOldAttributes(): array
	{
		return $this->_oldAttributes;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setAttribute($name, $value): mixed
	{
		return $this->_attributes[$name] = $value;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setOldAttribute($name, $value): mixed
	{
		return $this->_oldAttributes[$name] = $value;
	}

	/**
	 * @param array $param
	 * @return $this
	 * @throws Exception
	 */
	public function setAttributes(array $param): static
	{
		if (empty($param)) {
			return $this;
		}
		foreach ($param as $key => $val) {
			if (!$this->has($key)) {
				$this->setAttribute($key, $val);
			} else {
				$this->$key = $val;
			}
		}
		return $this;
	}

	/**
	 * @param $param
	 * @return $this
	 */
	public function setOldAttributes($param): static
	{
		if (empty($param) || !is_array($param)) {
			return $this;
		}
		foreach ($param as $key => $val) {
			$this->setOldAttribute($key, $val);
		}
		return $this;
	}

	/**
	 * @param $attributes
	 * @param $param
	 * @return $this|bool
	 * @throws Exception
	 */
	private function insert($param, $attributes): bool|static
	{
		if (empty($param)) {
			return FALSE;
		}
		$dbConnection = static::getDb();

		[$sql, $param] = SqlBuilder::builder(static::find())->insert($param);

		$trance = $dbConnection->beginTransaction();
		try {
			if (!($lastId = (int)$dbConnection->createCommand($sql, $param)->save(true, $this))) {
				throw new Exception('保存失败.' . $sql);
			}
			$trance->commit();
			$lastId = $this->setPrimary($lastId, $param);

			$this->refresh();

			$this->event->dispatch(self::AFTER_SAVE, [$attributes, $param]);
		} catch (\Throwable $exception) {
			$trance->rollback();
			$lastId = $this->addError($exception, 'mysql');
		}
		return $lastId;
	}


	/**
	 * @param $lastId
	 * @param $param
	 * @return static
	 * @throws Exception
	 */
	private function setPrimary($lastId, $param): static
	{
		if ($this->isAutoIncrement()) {
			$this->setAttribute($this->getAutoIncrement(), (int)$lastId);
			return $this;
		}

		if (!$this->hasPrimary()) {
			return $this;
		}

		$primary = $this->getPrimary();
		if (!isset($param[$primary]) || empty($param[$primary])) {
			$this->setAttribute($primary, (int)$lastId);
		}
		return $this->setAttributes($param);
	}


	/**
	 * @param $fields
	 * @param $condition
	 * @param $param
	 * @return $this|bool
	 * @throws Exception
	 */
	private function updateInternal($fields, $condition, $param): bool|static
	{
		if (empty($param)) {
			return true;
		}
		$command = static::getDb();

		if ($this->hasPrimary()) {
			$condition = [$this->getPrimary() => $this->getPrimaryValue()];
		}

		$generate = SqlBuilder::builder(static::find()->where($condition))->update($param);
		if (is_bool($generate)) {
			return $generate;
		}

		$trance = $command->beginTransaction();
		if (!$command->createCommand(...$generate)->save(false, $this)) {
			$trance->rollback();
			return false;
		}

		$trance->commit();

		$this->refresh();

		$this->event->dispatch(self::AFTER_SAVE, [$fields, $param]);

		return true;
	}

	/**
	 * @param null $data
	 * @return bool|$this
	 * @throws Exception
	 */
	public function save($data = NULL): static|bool
	{
		if (!is_null($data)) {
			$this->_attributes = merge($this->_attributes, $data);
		}

		if (!$this->validator($this->rules())) {
			return false;
		}

		if (!$this->event->dispatch(self::BEFORE_SAVE, [$this], $this)) {
			return false;
		}

		static::getDb()->enablingTransactions();
		[$change, $condition, $fields] = $this->filtration_and_separation();

		if (!$this->isNewExample) {
			return $this->updateInternal($fields, $condition, $change);
		}
		return $this->insert($change, $fields);
	}


	/**
	 * @param array $rule
	 * @return bool
	 * @throws Exception
	 */
	public function validator(array $rule): bool
	{
		if (empty($rule)) return true;
		$validate = $this->resolve($rule);
		if (!$validate->validation()) {
			return $this->addError($validate->getError(), 'mysql');
		} else {
			return TRUE;
		}
	}

	/**
	 * @param $rule
	 * @return Validator
	 * @throws Exception
	 */
	private function resolve($rule): Validator
	{
		$validate = Validator::getInstance();
		$validate->setParams($this->_attributes);
		$validate->setModel($this);
		foreach ($rule as $Key => $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validate->make($field, $val);
		}
		return $validate;
	}

	/**
	 * @param string $name
	 * @return null
	 * @throws Exception
	 */
	public function getAttribute(string $name)
	{
		if ($this->hasAnnotation($name)) {
			return $this->runAnnotation($name, $this->_attributes[$name]);
		}
		return $this->_attributes[$name] ?? null;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	protected function runAnnotation(string $name, mixed $value, string $type = self::ANNOTATION_GET): mixed
	{
		return call_user_func($this->_annotations[$type][$name], $value);
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function filtration_and_separation(): array
	{
		$_tmp = [];
		$condition = [];
		$columns = static::getColumns();

		$format = $columns->getAllField();

		foreach ($this->_attributes as $key => $val) {
			$oldValue = $this->_oldAttributes[$key] ?? null;
			if ($val !== $oldValue) {
				$_tmp[$key] = $this->toFormat($columns, $format, $key, $val);
			} else {
				$condition[$key] = $val;
			}
		}
		return [$_tmp, $condition, array_keys($_tmp)];
	}


	/**
	 * @param $columns
	 * @param $format
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	public function toFormat($columns, $format, $key, $value): mixed
	{
		if (isset($format[$key])) {
			return $columns->encode($value, $columns->clean($format[$key]));
		}
		return $value;
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public function setRelate($name, $value)
	{
		$this->_relate[$name] = $value;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function hasRelate($name): bool
	{
		return isset($this->_relate[$name]);
	}


	/**
	 * @param array $relates
	 */
	public function setRelates(array $relates)
	{
		if (empty($relates)) {
			return;
		}
		foreach ($relates as $key => $val) {
			$this->setRelate($key, $val);
		}
	}

	/**
	 * @return array
	 */
	public function getRelates(): array
	{
		return $this->_relate;
	}


	/**
	 * @return Relation|null
	 */
	public function getRelation(): ?Relation
	{
		return $this->_relation;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function getRelate($name): mixed
	{
		if (!isset($this->_relate[$name])) {
			return NULL;
		}
		return $this->_relate[$name];
	}


	/**
	 * @param $attribute
	 * @return bool
	 * @throws Exception
	 */
	public function has($attribute): bool
	{
		if (empty($this->_fields)) {
			$this->_fields = static::getColumns()->format();
		}
		return array_key_exists($attribute, $this->_fields);
	}

	/**ƒ
	 * @return string
	 * @throws Exception
	 */
	public static function getTable(): string
	{
		$tablePrefix = static::getDb()->tablePrefix;

		$table = static::tableName();

		if (str_starts_with($table, $tablePrefix)) {
			return $table;
		}

		if (empty($table)) {
			$class = preg_replace('/model\\\\/', '', get_called_class());
			$table = lcfirst($class);
		}

		$table = trim($table, '{{%}}');
		if ($tablePrefix) {
			$table = $tablePrefix . $table;
		}
		return $table;
	}


	/**
	 * @param $attributes
	 * @param $changeAttributes
	 * @return bool
	 * @throws Exception
	 */
	#[Event(ActiveRecord::AFTER_SAVE)]
	public function afterSave($attributes, $changeAttributes): bool
	{
		return true;
	}


	/**
	 * @param $model
	 * @return bool
	 */
	#[Event(ActiveRecord::BEFORE_SAVE)]
	public function beforeSave($model): bool
	{
		return true;
	}

	/**
	 * @return Connection
	 * @throws Exception
	 */
	public static function getDb(): Connection
	{
		return static::setDatabaseConnect('db');
	}

	/**
	 * @return static
	 */
	public function refresh(): static
	{
		$this->_oldAttributes = $this->_attributes;
		return $this;
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		if (!$this->has($name)) {
			parent::__set($name, $value);
			return;
		}
		if ($this->hasAnnotation($name, 'set')) {
			$this->runAnnotation($name, $value, self::ANNOTATION_SET);
		} else {
			$this->_attributes[$name] = $value;
		}
	}


	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name): mixed
	{
		$value = $this->_attributes[$name] ?? null;
		if ($this->hasAnnotation($name)) {
			return $this->runAnnotation($name, $value);
		}
		if (array_key_exists($name, $this->_attributes)) {
			return static::getColumns()->_decode($name, $value);
		}
		if (in_array($name, $this->_with)) {
			$data = $this->{$this->_relate[$name]}();
			if ($data instanceof HasBase) {
				return $data->get();
			}
			return $data;
		}
		return parent::__get($name);
	}


	/**
	 * @param string $type
	 * @return array
	 */
	protected function getAnnotation($type = self::ANNOTATION_GET): array
	{
		return $this->_annotations[$type] ?? [];
	}


	/**
	 * @param $name
	 * @param string $type
	 * @return bool
	 */
	protected function hasAnnotation($name, $type = self::ANNOTATION_GET): bool
	{
		if (!isset($this->_annotations[$type])) {
			return false;
		}
		return isset($this->_annotations[$type][$name]);
	}


	/**
	 * @param $item
	 * @param $data
	 * @return array
	 */
	protected function resolveAttributes($item, $data): array
	{
		return call_user_func($item, $data);
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name): bool
	{
		return isset($this->_attributes[$name]);
	}

	/**
	 * @param $call
	 * @return mixed
	 * @throws Exception
	 */
	private function resolveClass($call): mixed
	{
		if ($call instanceof HasOne) {
			return $call->get();
		} else if ($call instanceof HasMany) {
			return $call->get();
		} else {
			return $call;
		}
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 * @throws Exception
	 */
	public function offsetExists(mixed $offset): bool
	{
		return $this->has($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @throws Exception
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->__get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws Exception
	 */
	public function offsetSet(mixed $offset, mixed $value)
	{
		$this->__set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @throws Exception
	 */
	public function offsetUnset(mixed $offset)
	{
		if (!$this->has($offset)) {
			return;
		}
		unset($this->_attributes[$offset]);
		unset($this->_oldAttributes[$offset]);
		if (isset($this->_relate)) {
			unset($this->_relate[$offset]);
		}
	}

	/**
	 * @return array
	 */
	public function unset(): array
	{
		$fields = func_get_args();
		$fields = array_shift($fields);
		if (!is_array($fields)) {
			$fields = explode(',', $fields);
		}

		$array = array_combine($fields, $fields);

		return array_diff_assoc($array, $this->_attributes);
	}


	/**
	 * @param $dbName
	 * @return mixed
	 * @throws Exception
	 */
	public static function setDatabaseConnect($dbName): Connection
	{
		return Snowflake::app()->db->get($dbName);
	}

	/**
	 * @return Columns
	 * @throws Exception
	 */
	public static function getColumns(): Columns
	{
		return static::getDb()->getSchema()
			->getColumns()
			->table(static::getTable());
	}

	/**
	 * @param array $data
	 * @return static
	 * @throws
	 */
	public static function populate(array $data): static
	{
		$className = get_called_class();

		/** @var static $model */
		$model = objectPool($className, function () use ($className) {
			return new $className();
		});
		$model->setOldAttributes($data);
		$model->refresh();
		return $model;
	}

}
