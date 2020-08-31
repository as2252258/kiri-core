<?php


namespace Database;


/**
 * Class Filters
 * @package Yoc\db
 */
class Filters
{

	private $_filters = [];

	/**
	 * Filters constructor.
	 * @param $data
	 */
	public function __construct($data)
	{
		$this->_filters = $data;
	}

	/**
	 * @return Collection
	 */
	public function get()
	{
		return new Collection($this->_filters);
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->_filters);
	}

}
