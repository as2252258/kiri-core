<?php
declare(strict_types=1);
namespace Database\Mysql;


use Snowflake\Abstracts\Component;
use Database\Connection;
use Database\Orm\Change;
use Database\Orm\Select;

/**
 * Class Schema
 * @package Database\Mysql
 */
class Schema extends Component
{

	/** @var ?Connection */
	public ?Connection $db;

	/** @var ?Select */
	private ?Select $_builder = null;

	/** @var ?Columns $_column*/
	private ?Columns $_column = null;

	/** @var ?Change */
	private ?Change $_change = null;

	/**
	 * @return Select
	 */
	public function getQueryBuilder()
	{
		if ($this->_builder === null) {
			$this->_builder = new Select();
		}
		return $this->_builder;
	}

	/**
	 * @return Change
	 */
	public function getChange()
	{
		if ($this->_change === null) {
			$this->_change = new Change();
		}
		return $this->_change;
	}


	/**
	 * @return Columns
	 */
	public function getColumns()
	{
		if ($this->_column === null) {
			$this->_column = new Columns(['db' => $this->db]);
		}

		return $this->_column;
	}
}
