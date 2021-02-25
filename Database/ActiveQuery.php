<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 14:42
 */
declare(strict_types=1);

namespace Database;

use Snowflake\Abstracts\Component;
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
	public array $with = [];

	/** @var bool */
	public bool $asArray = FALSE;

	/** @var bool */
	public bool $useCache = FALSE;

	/**
	 * @var Connection|null
	 */
	public ?Connection $db = NULL;

	/**
	 * @var array
	 * 参数绑定
	 */
	public array $attributes = [];


	private SqlBuilder $builder;


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

		$this->builder = SqlBuilder::builder($this);
		parent::__construct($config);
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
	public function addParam($key, $value): static
	{
		$this->attributes[$key] = $value;
		return $this;
	}

	/**
	 * @param array $values
	 * @return $this
	 */
	public function addParams(array $values): static
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
	public function with($name): static
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
	public function asArray($isArray = TRUE): static
	{
		$this->asArray = $isArray;
		return $this;
	}


	/**
	 * @param $sql
	 * @return mixed
	 */
	public function execute($sql): Command
	{
		return $this->modelClass::getDb()->createCommand($sql);
	}


	/**
	 * @return ActiveRecord|array|null
	 * @throws Exception
	 */
	public function first(): ActiveRecord|array|null
	{
		$data = $this->execute($this->builder->one())->one();
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
	public function get(): Collection|array
	{
		return $this->all();
	}


	/**
	 * @throws Exception
	 */
	public function flush(): array|bool|int|string|null
	{
		return $this->execute($this->builder->truncate())->exec();
	}


	/**
	 * @param int $size
	 * @param callable $callback
	 * @return Pagination
	 * @throws Exception
	 */
	public function page(int $size, callable $callback): Pagination
	{
		$pagination = new Pagination($this);
		$pagination->setOffset(0);
		$pagination->setLimit($size);
		$pagination->setCallback($callback);
		return $pagination;
	}

	/**
	 * @param string $field
	 * @param string $setKey
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public function column(string $field, $setKey = ''): ?array
	{
		return $this->all()->column($field, $setKey);
	}

	/**
	 * @return array|Collection
	 * @throws
	 */
	public function all(): Collection|array
	{
		$data = $this->execute($this->builder->all())->all();
		$collect = new Collection($this, $data, $this->modelClass);
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
	public function populate(ActiveRecord $model, $data): ActiveRecord
	{
		return $this->getWith($model::populate($data));
	}


	/**
	 * @param $model
	 * @return mixed
	 */
	public function getWith($model): mixed
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
	public function count(): int
	{
		$data = $this->execute($this->builder->count())->one();
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
	public function batchUpdate(array $data): Command|array|bool|int|string
	{
		return $this->execute($this->builder->update($data))->exec();
	}

	/**
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function batchInsert(array $data): bool
	{
		return $this->execute($this->builder->insert($data, true))->exec();
	}

	/**
	 * @param $filed
	 *
	 * @return null
	 * @throws Exception
	 */
	public function value($filed)
	{
		return $this->first()[$filed] ?? null;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function exists(): bool
	{
		return $this->execute($this->builder->one())->fetchColumn() !== false;
	}


	/**
	 * @return bool
	 * @throws Exception
	 */
	public function delete(): bool
	{
		return $this->execute($this->builder->all())->delete();
	}
}
