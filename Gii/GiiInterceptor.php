<?php

declare(strict_types=1);

namespace Gii;


use Exception;
use Snowflake\Snowflake;

/**
 * Class GiiInterceptor
 * @package Gii
 */
class GiiInterceptor extends GiiBase
{

	public ?string $tableName = null;


	/**
	 * @return bool|array
	 * @throws Exception
	 */
	public function generate(): bool|array
	{

		$managerName = $this->input->get('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}
		$html = '<?php
		
		
namespace App\Http\Interceptor;

';
		$file = APP_PATH . 'app/Http/Interceptor/' . $managerName . 'Interceptor.php';
		if (file_exists($file)) {
			try {
				$class = new \ReflectionClass('App\\Http\\Interceptor\\' . $managerName . 'Interceptor');

				$html .= $this->getImports($file, $class);
			} catch (\Throwable $exception) {
				return logger()->addError($exception, 'throwable');
			}
		} else {
			$html .= '
use Closure;
use HttpServer\Http\Request;
use HttpServer\IInterface\Interceptor;
';
		}


		$managerName = ucfirst($managerName);
		$html .= '
 /**
 * Class ' . $managerName . 'Interceptor
 * @package App\Http\Interceptor
 */
class ' . $managerName . 'Interceptor implements Interceptor
{';

		if (isset($class)) {
			$html .= $this->getClassProperty($class);
			$html .= $this->getClassMethods($class);

			$html .= '

}';
		} else {
			$html .= '
	
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
		}


		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . 'Interceptor.php'];
	}

}
