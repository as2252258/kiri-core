<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 15:40
 */
declare(strict_types=1);

namespace Database;

use Database\Traits\QueryTrait;
use Exception;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class Db
 * @package Database
 */
class Db implements ISqlBuilder
{
	use QueryTrait;


	private static bool $_inTransaction = false;

	/**
	 * @return bool
	 */
	public static function transactionsActive(): bool
	{
		return static::$_inTransaction === true;
	}

	/**
	 * @throws Exception
	 */
	public static function beginTransaction()
	{
		static::$_inTransaction = true;
	}

	/**
	 * @throws Exception
	 */
	public static function commit()
	{
		if (!static::transactionsActive()) {
			return;
		}
		Event::trigger(Connection::TRANSACTION_COMMIT);
		Event::offName(Connection::TRANSACTION_COMMIT);
		static::$_inTransaction = false;
	}

	/**
	 * @throws Exception
	 */
	public static function rollback()
	{
		if (!static::transactionsActive()) {
			return;
		}
		Event::trigger(Connection::TRANSACTION_ROLLBACK);
		Event::offName(Connection::TRANSACTION_ROLLBACK);
		static::$_inTransaction = false;
	}

	/**
	 * @param $table
	 *
	 * @return static
	 */
	public static function table($table): Db|static
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
	public static function any_value(string $column, string $alias = ''): string
	{
		if (empty($alias)) {
			$alias = $column . '_any_value';
		}
		return 'ANY_VALUE(' . $column . ') as ' . $alias;
	}


	/**
	 * @param string $column
	 * @return string
	 */
	public static function increment(string $column): string
	{
		return '+ ' . $column;
	}


	/**
	 * @param string $column
	 * @return string
	 */
	public static function decrement(string $column): string
	{
		return '- ' . $column;
	}


	/**
	 * @param Connection|null $db
	 * @return mixed
	 * @throws Exception
	 */
	public function get(Connection $db = NULL): mixed
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}
		return $db->createCommand(SqlBuilder::builder($this)->one())
			->all();
	}

	/**
	 * @param $column
	 * @return string
	 */
	public static function raw($column): string
	{
		return '`' . $column . '`';
	}

	/**
	 * @param Connection|null $db
	 * @return mixed
	 * @throws Exception
	 */
	public function find(Connection $db = NULL): mixed
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}
		return $db->createCommand(SqlBuilder::builder($this)->all())
			->one();
	}

	/**
	 * @param Connection|NULL $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function count(Connection $db = NULL): bool|int
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}
		return $db->createCommand(SqlBuilder::builder($this)->count())
			->exec();
	}

	/**
	 * @param Connection|NULL $db
	 * @return bool|int
	 * @throws Exception
	 */
	public function exists(Connection $db = NULL): bool|int
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}
		return $db->createCommand(SqlBuilder::builder($this)->one())
			->fetchColumn();
	}

	/**
	 * @param string $sql
	 * @param array $attributes
	 * @param Connection|null $db
	 * @return array|bool|int|string|null
	 * @throws Exception
	 */
	public static function findAllBySql(string $sql, array $attributes = [], Connection $db = NULL): int|bool|array|string|null
	{
		return $db->createCommand($sql, $db?->database, $attributes)->all();
	}

	/**
	 * @param string $sql
	 * @param array $attributes
	 * @param Connection|NULL $db
	 * @return string|array|bool|int|null
	 * @throws Exception
	 */
	public static function findBySql(string $sql, array $attributes = [], Connection $db = NULL): string|array|bool|int|null
	{
		return $db->createCommand($sql, $db?->database, $attributes)->one();
	}

	/**
	 * @param string $field
	 * @return array|null
	 * @throws Exception
	 */
	public function values(string $field): ?array
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
	 * @return mixed
	 * @throws Exception
	 */
	public function value($field): mixed
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
	public function delete($db = null): bool|int
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
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
	public static function drop($table, $db = null): bool|int
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}
		return $db->createCommand('DROP TABLE ' . $table)->delete();
	}

	/**
	 * @param $table
	 * @param null $db
	 * @return bool|int
	 * @throws Exception
	 */
	public static function truncate($table, $db = null): bool|int
	{

		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}

		return $db->createCommand('TRUNCATE ' . $table)->exec();
	}

	/**
	 * @param $table
	 * @param Connection|NULL $db
	 * @return mixed
	 * @throws Exception
	 */
	public static function showCreateSql($table, Connection $db = NULL): mixed
	{

		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}


		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW CREATE TABLE `' . $db->database . '`.' . $table)->one();
	}

	/**
	 * @param $table
	 * @param Connection|NULL $db
	 * @return bool|int|null
	 * @throws Exception
	 */
	public static function desc($table, Connection $db = NULL): bool|int|null
	{
		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}

		if (empty($table)) {
			return null;
		}

		return $db->createCommand('SHOW FULL FIELDS FROM ' . $table)->all();
	}


	/**
	 * @param string $table
	 * @param Connection|NULL $db
	 * @return mixed
	 * @throws Exception
	 */
	public static function show(string $table, Connection $db = NULL): mixed
	{
		if (empty($table)) {
			return null;
		}

		if (empty($db)) {
			$db = Snowflake::app()->get('db');
		}

		$table = ['	const TABLE = \'select * from %s  where REFERENCED_TABLE_NAME=%s\';'];

		return $db->createCommand((new Query())
			->select('*')
			->from('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
			->where(['REFERENCED_TABLE_NAME' => $table])
			->getSql())->one();
	}

}
