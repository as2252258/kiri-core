<?php


namespace Gii;

use App\Async\UserOnline;
use Database\Db;
use Exception;
use HttpServer\IInterface\Task;
use Snowflake\Snowflake;

/**
 * Class GiiModel
 * @package Gii
 */
class GiiTask extends GiiBase
{

	public $classFileName;
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
		
		
namespace App\Async;

use HttpServer\IInterface\Task;

';

		$html .= '

/**
 * Class ' . ucfirst($managerName) . '
 * @package App\Async
 */
class ' . ucfirst($managerName) . ' implements Task
{
	
	protected $params = [];


	/**
	 * @return mixed|void
	 */
	public function onHandler()
	{
		// TODO: Implement handler() method.
	}


	/**
	 * @param $params
	 * @return $this
	 */
	public function setParams(array $params)
	{
		$this->params = $params;
		return $this;
	}


	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}


}';

		$file = APP_PATH . '/app/Async/' . $managerName . '.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return [$managerName . '.php'];
	}

}
