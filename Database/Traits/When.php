<?php


namespace Database\Traits;


use Database\ActiveQuery;
use Database\ISqlBuilder;
use Database\Query;
use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Exception\NotFindClassException;


/**
 * Class CaseWhen
 * @package Database\Traits
 */
class When
{

	public ActiveQuery|ISqlBuilder $query;


	private array $_condition = [];

	private string $else = '';


	/**
	 * CaseWhen constructor.
	 * @param string $column
	 * @param ActiveQuery|ISqlBuilder $activeQuery
	 */
	public function __construct(public string $column, public ActiveQuery|ISqlBuilder $activeQuery)
	{
		$this->_condition[] = 'CASE ' . $column;
	}


	/**
	 * @param array|\Closure $condition
	 * @param string $then
	 * @return $this
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function when(array|\Closure $condition, string $then): static
	{
		$this->_condition[] = sprintf('WHEN %s THEN %s', $this->activeQuery->makeNewSqlGenerate()
			->where($condition)
			->getCondition(), $then);

		return $this;
	}


	/**
	 * @param string $alias
	 */
	public function else(string $alias)
	{
		$this->else = $alias;
	}


	/**
	 * @return string
	 */
	#[Pure] public function end(): string
	{
		if (empty($this->_condition)) {
			return '';
		}
		$prefix = implode(' ', $this->_condition);
		if (!empty($this->else)) {
			$prefix .= ' ELSE ' . $this->else;
		}
		return $prefix . ' END';
	}

}
