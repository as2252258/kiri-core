<?php
declare(strict_types=1);


namespace Gii;


use Database\Connection;
use JetBrains\PhpStorm\ArrayShape;

use ReflectionClass;
use Snowflake\Abstracts\Input;

/**
 * Class GiiBase
 * @package Gii
 */
abstract class GiiBase
{

	public array $fileList = [];


	/** @var Input */
	protected Input $input;

	public string $modelPath = APP_PATH . 'app/Models/';
	public string $modelNamespace = 'App\Models\\';

	public string $controllerPath = APP_PATH . 'app/Http/Controllers/';
	public string $controllerNamespace = 'App\\Http\\Controllers\\';

	public ?string $module = null;

	public array $rules = [];
	public array $type = [
		'int'       => ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'],
		'string'    => ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum'],
		'date'      => ['date'],
		'time'      => ['time'],
		'year'      => ['year'],
		'datetime'  => ['datetime'],
		'timestamp' => ['timestamp'],
		'float'     => ['float', 'double', 'decimal',],
	];
	public ?string $tableName = NULL;

	public ?Connection $db = null;

	/**
	 * @param string $modelPath
	 */
	public function setModelPath(string $modelPath): void
	{
		$this->modelPath = $modelPath;
	}

	/**
	 * @param string $modelNamespace
	 */
	public function setModelNamespace(string $modelNamespace): void
	{
		$this->modelNamespace = $modelNamespace;
	}

	/**
	 * @param string $controllerPath
	 */
	public function setControllerPath(string $controllerPath): void
	{
		$this->controllerPath = $controllerPath;
	}

	/**
	 * @param $module
	 */
	public function setModule($module)
	{
		$this->module = $module;
	}

	/**
	 * @param string $controllerNamespace
	 */
	public function setControllerNamespace(string $controllerNamespace): void
	{
		$this->controllerNamespace = $controllerNamespace;
	}


	/**
	 * @param Input $input
	 */
	public function setInput(Input $input)
	{
		$this->input = $input;
	}


	/**
	 * @param ReflectionClass $object
	 * @param                  $className
	 *
	 * @return string
	 */
	public function getUseContent(ReflectionClass $object, $className): string
	{
		if (empty($object)) {
			return '';
		}
		$file = $this->getFilePath($className);
		if (!file_exists($file)) {
			return '';
		}
		$content = file_get_contents($file);
		$explode = explode(PHP_EOL, $content);
		$exists = array_slice($explode, 0, $object->getStartLine());
		$_tmp = [];
		foreach ($exists as $key => $val) {
			if (trim($val) == '/**') {
				break;
			}
			$_tmp[] = $val;
		}
		return trim(implode(PHP_EOL, $_tmp));
	}


	/**
	 * @param string $fileName
	 * @param ReflectionClass $class
	 * @return string
	 */
	protected function getImports(string $fileName, ReflectionClass $class): string
	{
		$startLine = 1;
		$array = [];
		$fileOpen = fopen($fileName, 'r');
		while (($content = fgets($fileOpen)) !== false) {
			if (str_starts_with($content, 'use ')) {
				$array[] = $content;
			}
			if ($startLine == $class->getStartLine()) {
				break;
			}
			++$startLine;
		}
		return implode($array);
	}


	/**
	 * @param $fields
	 * @return mixed 返回表主键
	 * 返回表主键
	 */
	public function getPrimaryKey($fields): mixed
	{
		$condition = ['PRI', 'UNI'];
		foreach ($fields as $field) {
			if ($field['Extra'] == 'auto_increment') {
				return $field['Field'];
			}
			if (in_array($field['Key'], $condition)) {
				return $field['Field'];
			}
		}
		return null;
	}

	/**
	 * @param $className
	 * @return string
	 */
	private function getFilePath($className): string
	{
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}
		if (strpos($className, '\\')) {
			$className = str_replace('\\', '/', $className);
		}

		return APP_PATH . $className;
	}

	/**
	 * @param ReflectionClass $object
	 * @param                  $className
	 * @param                  $method
	 * @return string
	 * @throws \Exception
	 */
	public function getFuncLineContent(ReflectionClass $object, $className, $method): string
	{
		$fun = $object->getMethod($method);

		$content = file_get_contents($this->getFilePath($className));
		$explode = explode(PHP_EOL, $content);
		$exists = array_slice($explode, $fun->getStartLine() - 1, $fun->getEndLine() - $fun->getStartLine() + 1);
		return implode(PHP_EOL, $exists);
	}


	/**
	 * @return array
	 */
	protected function getModelPath(): array
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'db') {
			$dbName = '';
		}

		$modelPath = [
			'namespace' => $this->modelNamespace,
			'path'      => $this->modelPath,
		];
		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		if (!empty($dbName)) {
			$modelPath['namespace'] = $this->modelNamespace . ucfirst($dbName);
			$modelPath['path'] = $this->modelPath . ucfirst($dbName);
		}

		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		return $modelPath;
	}

	/**
	 * @param $db
	 */
	public function setConnection($db)
	{
		$this->db = $db;
	}

	/**
	 * @param $val
	 * @return string
	 */
	protected function checkIsRequired($val): string
	{
		return strtolower($val['Null']) == 'no' && $val['Default'] === NULL ? 'true' : 'false';
	}

	/**
	 * @return array
	 */
	public function getFileLists(): array
	{
		return $this->fileList;
	}

}
