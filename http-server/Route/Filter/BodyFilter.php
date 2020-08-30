<?php


namespace HttpServer\Route\Filter;


use Exception;

/**
 * Class BodyFilter
 * @package BeReborn\Route\Filter
 */
class BodyFilter extends Filter
{

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function check()
	{
		return $this->validator();
	}

}
