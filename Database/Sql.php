<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/6/27 0027
 * Time: 17:49
 */
declare(strict_types=1);

namespace Database;


use Database\Orm\Select;
use Database\Traits\QueryTrait;
use Exception;

/**
 * Class Sql
 * @package Database
 */
class Sql
{

	use QueryTrait;

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getSql(): string
	{
		return (new Select())->getQuery($this);
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function getCondition(): string
	{
		return (new Select())->getWhere($this);
	}


}
