<?php

declare(strict_types=1);

namespace Gii;


use Exception;
use Kiri\Kiri;

/**
 * Class GiiLimits
 * @package Gii
 */
class GiiLimits extends GiiBase
{

	public ?string $tableName = '';


	/**
	 * @return string[]
	 * @throws Exception
	 */
	public function generate(): array
	{

		$managerName = $this->input->get('name', null);
		if (empty($managerName)) {
			throw new Exception('文件名称不能为空~');
		}
		$html = '<?php
		
		
namespace App\Http\Limits;

use Closure;
use Http\Http\Request;
use Http\IInterface\Limits;


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

		$file = APP_PATH . 'app/Http/Limits/' . $managerName . 'Limits.php';
		if (file_exists($file)) {
			throw new Exception('File exists.');
		}

		Kiri::writeFile($file, $html);
		return [$managerName . 'Limits.php'];
	}

}
