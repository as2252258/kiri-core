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
use Snowflake\Core\ArrayAccess;
use Snowflake\Error\Logger;
use Snowflake\Snowflake;

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
    public function rules()
    {
        return [];
    }

    /**
     * @param string $column
     * @param int $value
     * @return ActiveRecord|false
     * @throws Exception
     */
    public function increment(string $column, int $value)
    {
        if (!$this->mathematics(self::INCR, [$column => $value])) {
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
    public function decrement(string $column, int $value)
    {
        if (!$this->mathematics(self::DECR, [$column => $value])) {
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
    public function increments(array $columns)
    {
        if (!$this->mathematics(self::INCR, $columns)) {
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
    public function decrements(array $columns)
    {
        if (!$this->mathematics(self::DECR, $columns)) {
            return false;
        }
        foreach ($columns as $key => $attribute) {
            $this->$key -= $attribute;
        }
        return $this;
    }

    /**
     * @param $attributes
     * @param $condition
     * @return bool|ActiveRecord|mixed
     * @throws Exception
     */
    public static function findOrCreate(array $condition, array $attributes = [])
    {
        $select = static::find()->where($condition)->first();
        if (!empty($select)) {
            return $select;
        }
        if (empty($attributes)) {
            return \logger()->addError(FIND_OR_CREATE_MESSAGE, 'mysql');
        }
        $select = new static();
        $select->attributes = $attributes;
        return $select->save();
    }


    /**
     * @param $action
     * @param $columns
     * @param array $condition
     * @return array|bool|int|string|null
     * @throws Exception
     */
    private function mathematics($action, $columns, $condition = [])
    {
        if (empty($condition)) {
            $condition = [$this->getPrimary() => $this->getPrimaryValue()];
        }
        return static::getDb()->createCommand()
            ->mathematics(self::getTable(), [$action => $columns], $condition)
            ->exec();
    }

    /**
     * @param $column
     * @param $value
     * @param array $condition
     * @return bool
     * @throws Exception
     */
    public static function incrAll($column, $value, $condition = [])
    {
        return static::getDb()->createCommand()
            ->mathematics(self::getTable(), [self::INCR => [$column, $value]], $condition)
            ->exec();
    }


    /**
     * @param $column
     * @param $value
     * @param array $condition
     * @return bool
     * @throws Exception
     */
    public static function decrAll($column, $value, $condition = [])
    {
        return static::getDb()->createCommand()
            ->mathematics(self::getTable(), [self::DECR => [$column, $value]], $condition)
            ->exec();
    }


    /**
     * @param array $attributes
     * @return bool
     * @throws
     */
    public function update(array $attributes)
    {
        return $this->save($attributes);
    }

    /**
     * @param array $params
     * @param array $condition
     * @return bool|static
     * @throws Exception
     */
    public static function insertOrUpdate(array $params, array $condition)
    {
        $first = static::findOrCreate($params, $condition);
        $first->attributes = $params;
        if (!$first->save()) {
            return false;
        }
        return $first;
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function batchInsert(array $data): bool
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
    public function delete()
    {
        $conditions = $this->_oldAttributes;
        if (empty($conditions)) {
            return $this->addError("Delete condition do not empty.", 'mysql');
        }
        $primary = $this->getPrimary();

        if (!empty($primary)) {
            $sul = static::deleteAll([$primary => $this->getAttribute($primary)]);
        } else {
            $sul = static::deleteAll($conditions);
        }
        if (!$sul) {
            return false;
        }
        if (method_exists($this, 'afterDelete')) {
            $this->afterDelete();
        }
        return true;
    }


    /**
     * @param       $condition
     * @param array $attributes
     *
     * @return bool
     * @throws Exception
     */
    public static function batchUpdate($condition, $attributes = [])
    {
        $condition = static::find()->where($condition);
        return $condition->batchUpdate($attributes);
    }

    /**
     * @param       $condition
     * @param array $attributes
     *
     * @return array|mixed|null|Collection
     * @throws Exception
     */
    public static function findAll($condition, $attributes = [])
    {
        $query = static::find()->where($condition);
        if (!empty($attributes)) {
            $query->bindParams($attributes);
        }
        return $query->all();
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    private function resolveObject($data)
    {
        if (is_numeric($data) || !is_string($data)) {
            return $data;
        }
        if (!method_exists($this, $data)) {
            return $data;
        }
        return ArrayAccess::toArray($this->{$data}());
    }


    /**
     * @return array|mixed
     * @throws Exception
     */
    public function toArray()
    {
        $data = [];
        foreach ($this->_attributes as $key => $val) {
            $data[$key] = $this->getAttribute($key);
        }
        return array_merge($data, $this->runRelate());
    }

    /**
     * @return array
     * @throws Exception
     */
    private function runRelate()
    {
        $relates = [];
        if (empty($this->_relate)) {
            return $relates;
        }
        foreach ($this->_relate as $key => $val) {
            $relates[$key] = $this->resolveObject($val);
        }
        return $relates;
    }


    /**
     * @param $modelName
     * @param $foreignKey
     * @param $localKey
     * @return mixed|ActiveQuery
     * @throws Exception
     */
    public function hasOne(string $modelName, $foreignKey, $localKey)
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
     * @return mixed|ActiveQuery
     * @throws Exception
     */
    public function hasCount($modelName, $foreignKey, $localKey)
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
     * @return mixed|ActiveQuery
     * @throws Exception
     */
    public function hasMany($modelName, $foreignKey, $localKey)
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
     * @return mixed|ActiveQuery
     * @throws Exception
     */
    public function hasIn($modelName, $foreignKey, $localKey)
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
    public function afterDelete()
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
    public function beforeDelete()
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
