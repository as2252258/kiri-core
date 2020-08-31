<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 17:22
 */

namespace Database\Mysql;


use Snowflake\Abstracts\Component;
use Database\Connection;
use Exception;
use Snowflake\Core\JSON;

/**
 * Class Columns
 * @package Database\Mysql
 */
class Columns extends Component
{

	private $columns = [];

	/** @var Connection $db */
	public $db;
	public $table = '';
	private $_primary = [];
	private $_auto_increment = [];

	/**
	 * @param string $table
	 * @return $this
	 * @throws Exception
	 */
	public function table($table)
	{
		return $this->structure($this->table = $table);
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @param $key
	 * @param $val
	 * @return float|int|mixed|string
	 * @throws Exception
	 */
	public function fieldFormat($key, $val)
	{
		return $this->encode($val, $this->get_fields($key));
	}

	/**
	 * @param $data
	 * @return array
	 * @throws
	 */
	public function populate($data)
	{
		$column = $this->get_fields();
		foreach ($data as $key => $val) {
			if (!isset($column[$key])) {
				continue;
			}
			$data[$key] = $this->decode($val, $column[$key]);
		}
		return $data;
	}

	/**
	 * @param $val
	 * @param $format
	 * @return float|int|mixed|string
	 * @throws
	 */
	public function decode($val, $format = null)
	{
		if (empty($format)) {
			return $val;
		}
		$format = strtolower($format);
		if ($this->isInt($format)) {
			return (int)$val;
		} else if ($this->isJson($format)) {
			return JSON::decode($val, true);
		} else if ($this->isFloat($format)) {
			return (float)$val;
		} else {
			return stripslashes($val);
		}
	}

	/**
	 * @param $val
	 * @param $format
	 * @return float|int|mixed|string
	 * @throws
	 */
	public function encode($val, $format = null)
	{
		if (empty($format)) {
			return $val;
		}
		$format = strtolower($format);
		if ($this->isInt($format)) {
			return (int)$val;
		} else if ($this->isJson($format)) {
			return JSON::encode($val);
		} else if ($this->isFloat($format)) {
			return (float)$val;
		} else {
			return addslashes($val);
		}
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function isInt($format)
	{
		return in_array($format, ['int', 'bigint', 'tinyint', 'smallint', 'mediumint']);
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function isFloat($format)
	{
		return in_array($format, ['float', 'double', 'decimal']);
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function isJson($format)
	{
		return in_array($format, ['json']);
	}

	/**
	 * @param $format
	 * @return bool
	 */
	public function isString($format)
	{
		return in_array($format, ['varchar', 'char', 'text', 'longtext', 'tinytext', 'mediumtext']);
	}


	/**
	 * @return array
	 * @throws
	 */
	public function format()
	{
		return $this->columns('Default', 'Field');
	}

	/**
	 * @return int|string|null
	 * @throws Exception
	 */
	public function getAutoIncrement()
	{
		return $this->_auto_increment[$this->table] ?? null;
	}

	/**
	 * @return array|null|string
	 *
	 * @throws Exception
	 */
	public function getPrimaryKeys()
	{
		if (isset($this->_auto_increment[$this->table])) {
			return $this->_auto_increment[$this->table];
		}
		return $this->_primary[$this->table] ?? null;
	}

	/**
	 * @param $name
	 * @param null $index
	 * @return array
	 * @throws Exception
	 */
	private function columns($name, $index = null)
	{
		if (empty($index)) {
			return array_column($this->getColumns(), $name);
		} else {
			return array_column($this->getColumns(), $name, $index);
		}
	}

	/**
	 * @return array|bool|int|mixed|string
	 * @throws Exception
	 */
	private function getColumns()
	{
		return $this->structure($this->getTable())->columns[$this->getTable()];
	}


	/**
	 * @param $table
	 * @return $this
	 * @throws Exception
	 */
	private function structure($table)
	{
		if (isset($this->columns[$table])) {
			return $this;
		}
		$sql = $this->db->getBuild()->getColumn($table);
		$column = $this->db->createCommand($sql)->all();
		if (empty($column)) {
			throw new Exception("The table " . $table . " not exists.");
		}
		foreach ($column as $key => $item) {
			$column[$key]['Type'] = $this->clean($item['Type']);
			if ($item['Key'] === 'PRI') {
				if (!isset($this->_primary[$table])) {
					$this->_primary[$table] = [];
				}
				$this->_primary[$table][] = $item['Field'];
			}
			if ($item['Extra'] === 'auto_increment') {
				$this->_auto_increment[$table] = $item['Field'];
			}
		}
		$this->columns[$table] = $column;
		return $this;
	}


	/**
	 * @param $type
	 * @return string
	 */
	private function clean($type)
	{
		if (strpos($type, ')') === false) {
			return $type;
		}

		$replace = preg_replace('/\(\d+(,\d+)?\)(\s+\w+)*/', '', $type);
		if (strpos(' ', $replace) !== FALSE) {
			$replace = explode(' ', $replace)[1];
		}
		return $replace;
	}

	/**
	 * @param $field
	 * @return array|string
	 * @throws Exception
	 */
	public function get_fields($field = null)
	{
		$fields = $this->columns('Type', 'Field');
		if (empty($field)) {
			return $fields;
		}
		if (!isset($fields[$field])) {
			return null;
		}
		return strtolower($fields[$field]);
	}

}
