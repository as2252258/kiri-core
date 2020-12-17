<?php
declare(strict_types=1);

namespace Database\Condition;


use Database\ActiveQuery;
use Database\Base\ConditionClassMap;
use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Core\Str;
use Snowflake\Snowflake;

/**
 * Class ArrayCondition
 * @package Database\Condition
 */
class ArrayCondition extends Condition
{

	private array $math = ['like', 'in', 'or', 'eq', 'neq', 'gt', 'ngt', 'lt', 'nlt'];

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function builder(): mixed
	{
		if ($this->value instanceof Condition) {
			return $this->value->builder();
		}

		$conditions = [];

		$classMap = ConditionClassMap::$conditionMap;
		foreach ($this->value as $key => $value) {
			if ($value === null) {
				continue;
			}
			if ($value instanceof Condition) {
				$value = $value->builder();
			} else if (isset($value[0]) && isset($classMap[strtoupper($value[0])])) {
				$value = $this->buildOperaCondition($value);
			} else {
				$value = $this->buildHashCondition($key, $value);
			}
			if (empty($value)) {
				continue;
			}
			$conditions[] = Str::encode($value);
		}
		if (is_array($conditions)) {
			$conditions = implode(' AND ', $conditions);
		}
		return $conditions;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	#[Pure] private function isMath($value): bool
	{
		return isset($value[0]) && in_array($value[0], $this->math);
	}

	/**
	 * @param array $value
	 * @return mixed
	 * @throws Exception
	 */
	public function buildOperaCondition(array $value): mixed
	{
		[$option['opera'], $option['column'], $option['value']] = $value;
		$strPer = strtoupper($option['opera']);
		if (isset($this->conditionMap[$strPer])) {
			$class = ConditionClassMap::$conditionMap[$strPer];
			if (!is_array($class)) {
				$class = ['class' => $class];
			}
			$option = array_merge($option, $class);
		} else if ($value instanceof ActiveQuery) {
			$option['value'] = $value->getBuild()->getQuery($value);
			$option['class'] = ChildCondition::class;
		} else {
			$option['class'] = DefaultCondition::class;
		}
		/** @var Condition $class */
		$class = Snowflake::createObject($option);
		return $conditions[] = $class->builder();
	}

	/**
	 * @param $key
	 * @param $value
	 * @return string
	 */
	public function buildHashCondition($key, $value): string
	{
		return $this->resolve($key, $value);
	}

}
