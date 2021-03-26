<?php


namespace Database\Traits;


use Database\ActiveQuery;
use Database\Sql;
use Exception;
use JetBrains\PhpStorm\Pure;


/**
 * Class CaseWhen
 * @package Database\Traits
 */
class When
{

	public ActiveQuery|QueryTrait $query;


	private array $_condition = [];

	private string $else = '';


	/**
	 * CaseWhen constructor.
	 * @param string $column
	 * @param ActiveQuery|QueryTrait $activeQuery
	 */
	public function __construct(public string $column, public ActiveQuery|QueryTrait $activeQuery)
	{
		$this->_condition[] = 'CASE ' . $column;
	}


	/**
	 * @param array|string $condition
	 * @param string $then
	 * @return $this
	 * @throws Exception
	 */
	public function when(array|string $condition, string $then): static
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
