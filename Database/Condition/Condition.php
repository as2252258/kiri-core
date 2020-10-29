<?php
declare(strict_types=1);

namespace Database\Condition;


use Snowflake\Abstracts\BaseObject;
use Snowflake\Core\Str;

/**
 * Class Condition
 * @package Database\Condition
 */
abstract class Condition extends BaseObject
{

	protected string $column = '';
	protected string $opera = '=';

	/** @var array|mixed */
	protected $value;

	const INT_TYPE = ['bit', 'bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'timestamp'];

	protected array $attributes = [];

	abstract public function builder();

	/**
	 * @param string $column
	 */
	public function setColumn(string $column): void
	{
		$this->column = $column;
	}

	/**
	 * @param string $opera
	 */
	public function setOpera(string $opera): void
	{
		$this->opera = $opera;
	}

	/**
	 * @param $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}

	/**
	 * @param $column
	 * @param $value
	 * @param $oprea
	 *
	 * @return string
	 *
	 * $query = new Build();
	 * $query->where('id', '2');
	 * $query->where(['id' => 3]);
	 * $query->where('id', '<', 4);
	 * $query->orWhere('id', '=', 5);
	 * $query->orWhere('id', '=', 6);
	 * $query->ANDWhere('id', '=', 7);
	 * $sql = '(((id=2 AND id=3 AND id<4) OR id=5) OR id=6) AND i(d=7)';
	 */
	protected function resolve($column, $value = null, $oprea = '=')
	{
		if ($value === NULL) {
			return '';
		}

		$value = Str::encode($value);
		if (trim($oprea) == 'like') {
			return $column . ' ' . $oprea . ' \'%' . $value . '%\'';
		}
		$columns = $this->column[$column] ?? '';
		if (empty($columns)) {
			return $this->typeBuilder($column, $value, $oprea);
		}

		$explode = explode('(', $columns);
		$explode = array_shift($explode);
		if (strpos($explode, ' ') !== false) {
			$explode = explode(' ', $explode)[0];
		}
		if (!in_array(trim($explode), static::INT_TYPE)) {
			$str = $column . ' ' . $oprea . ' \'' . $value . '\'';
		} else {
			$str = $column . ' ' . $oprea . ' ' . $value;
		}
		return $str;
	}


	/**
	 * @param array $param
	 * @return array
	 */
	protected function format($param)
	{
		if (!is_array($param)) {
			return null;
		}
		$_tmp = [];
		foreach ($param as $value) {
			if ($value === null) {
				continue;
			}
			$value = Str::encode($value);
			if (is_numeric($value)) {
				$_tmp[] = Str::encode($value);
			} else {
				$_tmp[] = '\'' . Str::encode($value) . '\'';
			}
		}
		return $_tmp;
	}


	/**
	 * @param $column
	 * @param null $value
	 * @param string $oprea
	 * @return string
	 */
	public function typeBuilder($column, $value = null, $oprea = '=')
	{
		if (is_numeric($value)) {
			if ($value != (int)$value) {
				return $column . ' ' . $oprea . ' \'' . $value . '\'';
			}
			return $column . ' ' . $oprea . ' ' . $value;
		} else {
			$encode = '\'' . Str::encode($value) . '\'';
			return $column . ' ' . $oprea . ' ' . $encode;
		}
	}

}
