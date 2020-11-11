<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */
declare(strict_types=1);
namespace Database;


/**
 * Interface IOrm
 * @package Database
 */
interface IOrm
{

	/**
	 * @param $param
	 * @param null $db
	 * @return ActiveRecord
	 */
	public static function findOne($param, $db = NULL);


    /**
     * @return string
     */
	public static function className();


    /**
     * @return ActiveQuery
     * return a sql queryBuilder
     */
	public static function find();


	/**
	 * @param $dbName
	 * @return Connection
	 */
	public static function setDatabaseConnect($dbName);

//	public static function deleteAll($condition, $attributes);

//	public static function updateAll($condition, $attributes);

}
