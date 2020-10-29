<?php
declare(strict_types=1);


namespace Gii;


use Exception;
use Snowflake\Snowflake;

/**
 * Class GiiMiddleware
 * @package Gii
 */
class GiiMiddleware extends GiiBase
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
		
		
namespace App\Http\Middleware;

use Closure;
use HttpServer\Http\Request;
use HttpServer\IInterface\Middleware;

';

		$managerName = ucfirst($managerName);
		$html .= '
 /**
 * Class ' . $managerName . 'Middleware
 * @package App\Http\Middleware
 */
class ' . $managerName . 'Middleware implements Middleware
{
	
	/**
	 * @param Request $request
	 * @param Closure $closure
	 * @return mixed
	 */
	public function handler(Request $request, Closure $closure)
	{
		return $closure($request);
	}


}';

		$file = APP_PATH . 'app/Http/Middleware/' . $managerName . 'Middleware.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . 'Middleware.php'];
	}

}
