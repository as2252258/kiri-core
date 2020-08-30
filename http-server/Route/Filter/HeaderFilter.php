<?php


namespace HttpServer\Route\Filter;

use Exception;

/**
 * Class HeaderFilter
 * @package BeReborn\Route\Filter
 */
class HeaderFilter extends Filter
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
