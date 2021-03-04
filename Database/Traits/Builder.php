<?php


namespace Database\Traits;


use Database\ActiveQuery;
use Database\Base\ConditionClassMap;
use Database\Condition\HashCondition;
use Database\Condition\OrCondition;
use Database\SqlBuilder;
use Exception;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

trait Builder
{


    /**
     * @param $alias
     * @return string
     */
    private function builderAlias($alias): string
    {
        return " AS " . $alias;
    }

    /**
     * @param $table
     * @return string
     * @throws Exception
     */
    private function builderFrom($table): string
    {
        if ($table instanceof ActiveQuery) {
            $table = '(' . SqlBuilder::builder($table)->get($table) . ')';
        }
        return " FROM " . $table;
    }

    /**
     * @param $join
     * @return string
     */
    #[Pure] private function builderJoin($join): string
    {
        if (!empty($join)) {
            return ' ' . implode(' ', $join);
        }
        return '';
    }


    /**
     * @param null $select
     * @return string
     */
    #[Pure] private function builderSelect($select = NULL): string
    {
        if (empty($select)) {
            return "SELECT *";
        }
        if (is_array($select)) {
            return "SELECT " . implode(',', $select);
        } else {
            return "SELECT " . $select;
        }
    }


    /**
     * @param $group
     * @return string
     */
    private function builderGroup($group): string
    {
        if (empty($group)) {
            return '';
        }
        return ' GROUP BY ' . $group;
    }

    /**
     * @param $order
     * @return string
     */
    #[Pure] private function builderOrder($order): string
    {
        if (!empty($order)) {
            return ' ORDER BY ' . implode(',', $order);
        } else {
            return '';
        }
    }

    /**
     * @param ActiveQuery $query
     * @return string
     */
    #[Pure] private function builderLimit(ActiveQuery $query): string
    {
        if (!is_numeric($query->limit) || $query->limit < 1) {
            return "";
        }
        if ($query->offset !== null) {
            return ' LIMIT ' . $query->offset . ',' . $query->limit;
        }
        return ' LIMIT ' . $query->limit;
    }


    /**
     * @param $where
     * @return string
     * @throws Exception
     */
    private function where($where): string
    {
        $_tmp = [];
        if (empty($where)) return '';
        if (is_string($where)) return $where;
        foreach ($where as $key => $value) {
            if (is_string($key)) {
                $_value = sprintf('%s=\'%s\'', $key, $value);
            } else if (is_string($value)) {
                $_value = $value;
            } else {
                $_value = $this->_arrayMap($value, $_tmp);
            }
            if (empty($_value)) continue;
            $_tmp[] = $_value;
        }
        if (!empty($_tmp)) {
            return sprintf(' WHERE %s', implode(' AND ', $_tmp));
        }
        return '';
    }


    /**
     * @param $condition
     * @param $array
     * @return string
     * @throws NotFindClassException
     * @throws ReflectionException
     * @throws ReflectionException
     */
    private function _arrayMap($condition, $array): string
    {
        if (!isset($condition[0])) {
            return implode(' AND ', $this->_hashMap($condition));
        }
        $stroppier = strtoupper($condition[0]);
        if (str_contains($stroppier, 'OR')) {
            if (!is_string($condition[2])) {
                $condition[2] = $this->_hashMap($condition[2]);
            }
            $builder = Snowflake::createObject(['class' => OrCondition::class, 'value' => $condition[2], 'column' => $condition[1], 'oldParams' => $array]);
        } else if (isset(ConditionClassMap::$conditionMap[$stroppier])) {
            $defaultConfig = ConditionClassMap::$conditionMap[$stroppier];
            $create = array_merge($defaultConfig, ['column' => $condition[1], 'value' => $condition[2]]);
            $builder = Snowflake::createObject($create);
        } else {
            $builder = Snowflake::createObject(['class' => HashCondition::class, 'value' => $condition]);
        }

        $array[] = $builder->builder();

        return implode(' AND ', $array);
    }


    /**
     * @param $condition
     * @return array
     */
    private function _hashMap($condition): array
    {
        $_array = [];
        foreach ($condition as $key => $value) {
            $value = is_numeric($value) ? $value : '\'' . $value . '\'';
            if (!is_numeric($key)) {
                $_array[] = sprintf('%s=%s', $key, $value);
            } else {
                $_array[] = $value;
            }
        }
        return $_array;
    }


}
