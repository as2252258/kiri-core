<?php


namespace Gii;


use Exception;
use Snowflake\Snowflake;

/**
 * Class GiiLimits
 * @package Gii
 */
class GiiLimits extends GiiBase
{

	public $tableName;
	public $visible;
	public $res;
	public $fields;


	/**
	 * @return string[]
	 * @throws Exception
	 */
	public function generate()
	{

		$managerName = $this->input->get('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}
		$html = '<?php
		
		
namespace App\Http\Limits;

use Closure;
use HttpServer\Http\Request;
use HttpServer\IInterface\Limits;


';

		$managerName = ucfirst($managerName);
		$html .= '


 /**
 * Class ' . $managerName . 'Limits
 * @package App\Http\Limits
 */
class ' . $managerName . 'Limits implements Limits
{
	
	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function next(Request $request, Closure $closure)
	{
		return $closure($request);
	}


}';

		$file = APP_PATH . '/app/Interceptor/' . $managerName . 'Limits.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . 'Limits.php'];
	}

}
