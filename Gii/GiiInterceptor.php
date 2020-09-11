<?php


namespace Gii;


use Exception;
use Snowflake\Snowflake;

/**
 * Class GiiInterceptor
 * @package Gii
 */
class GiiInterceptor extends GiiBase
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
		
		
namespace App\Http\Interceptor;

use Closure;
use HttpServer\Http\Request;
use HttpServer\IInterface\Interceptor;


';

		$managerName = ucfirst($managerName);
		$html .= '


 /**
 * Class ' . $managerName . '
 * @package App\Http\Interceptor
 */
class ' . $managerName . 'Interceptor implements Interceptor
{
	
	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function Interceptor(Request $request, Closure $closure)
	{
		return $closure($request);
	}


}';

		$file = APP_PATH . '/app/Interceptor/' . $managerName . 'Interceptor.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . 'Interceptor.php'];
	}

}
