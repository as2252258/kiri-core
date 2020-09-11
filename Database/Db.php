<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 15:40
 */

namespace Database;

use Database\Traits\QueryTrait;
use Exception;
use Snowflake\Snowflake;

/**
 * Class Db
 * @package Database
 */
class Db
{
	use QueryTrait;

	private static $db;

	private static $isActive = false;

	/**
	 * @return bool
	 */
	public static function transactionsActive()
	{
		return static::$isActive;
	}

	/**
	 * @throws Exception
	 */
	public static function beginTransaction()
	{
		static::$isActive = true;
	}

	/**
	 * @throws Exception
	 */
	public static function commit()
	{
		$event = Snowflake::app()->event;
		$event->trigger(Connection::TRANSACTION_COMMIT);
		$event->offName(Connection::TRANSACTION_COMMIT);
		static::$isActive = false;
	}

	/**
	 * @throws Exception
	 */
	public static function rollback()
	{
		$event = Snowflake::app()->event;
		$event->trigger(Connection::TRANSACTION_ROLLBACK);
		$event->offName(Connection::TRANSACTION_ROLLBACK);
		static::$isActive = false;
	}

	/**
	 * @param $table
	 *
	 * @return static
	 */
	public static function table($table)
	{
		$db = new Db();
		$db->from($table);
		return $db;
	}

	/**
	 * @param string $column
	 * @param string $alias
	 * @return string
	 */
	public static function any_value(string $column, string $alias = '')
	{
		if (empty($alias)) {
			$alias = $column . '_any_value';
		}
		return 'ANY_VALUE(' . $column . ') as ' . $alias;
	}

	/**
	 * @param Connection|null $db
	 * @return mixed
	 * @throws Exception
	 */
	public function get(Connection $db = NULL)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}
		$query = $db->getSchema()->getQueryBuilder();
		return $db->createCommand($query->getQuery($this))
			->all();
	}

	/**
	 * @param $column
	 * @return string
	 */
	public static function raw($column)
	{
		return '`' . $column . '`';
	}

	/**
	 * @param Connection|null $db
	 * @return array|mixed
	 * @throws Exception
	 */
	public function find(Connection $db = NULL)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}
		$query = $db->getSchema()->getQueryBuilder();
		return $db->createCommand($query->getQuery($this))
			->one();
	}

	/**
	 * @param Connection|NULL $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function count(Connection $db = NULL)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}
		$query = $db->getSchema()->getQueryBuilder();
		return $db->createCommand($query->count($this))
			->rowCount();
	}

	/**
	 * @param Connection|NULL $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function exists(Connection $db = NULL)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}
		$query = $db->getSchema()->getQueryBuilder();
		return $db->createCommand($query->getQuery($this))
			->fetchColumn();
	}

	/**
	 * @param string $sql
	 * @param array $attributes
	 * @param Connection|null $db
	 * @return array|bool|int|string|null
	 * @throws Exception
	 */
	public static function findAllBySql(string $sql, array $attributes = [], Connection $db = NULL)
	{
		return $db->createCommand($sql, $attributes)->all();
	}

	/**
	 * @param string $sql
	 * @param array $attributes
	 * @param Connection|NULL $db
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function findBySql(string $sql, array $attributes = [], Connection $db = NULL)
	{
		return $db->createCommand($sql, $attributes)->one();
	}

	/**
	 * @param string $field
	 * @return array|null
	 * @throws Exception
	 */
	public function values(string $field)
	{
		$data = $this->get();
		if (empty($data) || empty($field)) {
			return NULL;
		}
		$first = current($data);
		if (!isset($first[$field])) {
			return NULL;
		}
		return array_column($data, $field);
	}

	/**
	 * @param $field
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public function value($field)
	{
		$data = $this->find();
		if (!empty($field) && isset($data[$field])) {
			return $data[$field];
		}
		return $data;
	}

	/**
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function delete($db = null)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}

		$query = $db->getBuild()->builder($this);

		return $db->createCommand($query)->delete();
	}

	/**
	 * @param $table
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public static function drop($table, $db = null)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}
		return $db->createCommand('DROP TABLE ' . $table)->delete();
	}

	/**
	 * @param $table
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public static function truncate($table, $db = null)
	{

		if (empty($db)) {
			$db = Snowflake::app()->database;
		}

		return $db->createCommand('TRUNCATE ' . $table)->exec();
	}

	/**
	 * @param $table
	 * @param Connection|NULL $db
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function showCreateSql($table, Connection $db = NULL)
	{

		if (empty($db)) {
			$db = Snowflake::app()->database;
		}


		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW CREATE TABLE ' . $table)->one();
	}

	/**
	 * @param $table
	 * @param Connection|NULL $db
	 * @return bool|int|null
	 * @throws Exception
	 */
	public static function desc($table, Connection $db = NULL)
	{
		if (empty($db)) {
			$db = Snowflake::app()->database;
		}

		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW FULL FIELDS FROM ' . $table)->all();
	}


	/**
	 * @param string $table
	 * @param Connection|NULL $db
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function show(string $table, Connection $db = NULL)
	{
		if (empty($table)) {
			return null;
		}

		if (empty($db)) {
			$db = Snowflake::app()->database;
		}

		$table = ['	const TABLE = \'select * from %s  where REFERENCED_TABLE_NAME=%s\';'];

		return $db->createCommand((new Sql())
			->select('*')
			->from('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
			->where(['REFERENCED_TABLE_NAME' => $table])
			->getSql())->one();
	}

}
