<?php
declare(strict_types=1);


namespace Gii;

use Exception;
use ReflectionException;
use Snowflake\Snowflake;

/**
 * Class GiiController
 * @package Gii
 */
class GiiController extends GiiBase
{

	public string $className = '';

	public array $fields = [];


	/**
	 * GiiController constructor.
	 * @param $className
	 * @param $fields
	 */
	public function __construct($className, $fields)
	{
		$this->className = $className;
		$this->fields = $fields;
	}


	/**
	 * @return string|bool
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function generate(): string|bool
	{
		$path = $this->getControllerPath();
		$modelPath = $this->getModelPath();

		$managerName = $this->className;

		$namespace = rtrim($path['namespace'], '\\');
		$model_namespace = rtrim($modelPath['namespace'], '\\');

		$prefix = str_replace('_', '', $this->db->tablePrefix);
		$managerName = str_replace(ucfirst($prefix), '', $managerName);

		$class = '';
		$controller = str_replace('\\\\', '\\', "$namespace\\{$managerName}Controller");

		$html = "<?php
namespace {$namespace};

";
		if (file_exists($path['path'] . '/' . $managerName . 'Controller.php')) {
			try {
				$class = new \ReflectionClass($controller);

				$import = $this->getImports($path['path'] . '/' . $managerName . 'Controller.php', $class);
			} catch (\Throwable $Exception) {
				exit(logger()->addError($Exception, 'throwable'));
			}
		} else {
			$import = "use Snowflake;
use Exception;
use Annotation\Target;
use Annotation\Route\Middleware;
use Annotation\Route\Route;
use Snowflake\Core\Str;
use Snowflake\Core\JSON;
use HttpServer\Http\Request;
use HttpServer\Http\Response;
use HttpServer\Controller;
use JetBrains\PhpStorm\ArrayShape;
use {$model_namespace}\\{$managerName};
";
		}
		if (!empty($import)) {
			$html .= $import;
		}

		$controllerName = $managerName;

		$historyModel = "use {$model_namespace}\\{$managerName};";
		if (!str_contains($html, $historyModel)) {
			$html .= $historyModel;
		}

		$html .= "
		
/**
 * Class {$controllerName}Controller
 *
 * @package controller
 */
#[Target] class {$controllerName}Controller extends Controller
{

";


		$funcNames = [];
		if (is_object($class)) {
			$html .= $this->getClassProperty($class);
			$html .= $this->getClassMethods($class);
		}

		$default = ['loadParam', 'actionAdd', 'actionUpdate', 'actionDetail', 'actionDelete', 'actionBatchDelete', 'actionList'];

		foreach ($default as $key => $val) {
			if (str_contains($html, ' function ' . $val . '(')) {
				continue;
			}
			$html .= $this->{'controllerMethod' . str_replace('action', '', $val)}($this->fields, $managerName, $managerName, $path) . "\n";
		}

		$html .= '
}';

		$file = $path['path'] . '/' . $controllerName . 'Controller.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return $controllerName . 'Controller.php';
	}


	/**
	 * @return array
	 */
	private function getControllerPath(): array
	{
		$dbName = $this->db->id;
		if (empty($dbName) || $dbName == 'db') {
			$dbName = '';
		}

		$module = empty($this->module) ? '' : $this->module;
		$modelPath['namespace'] = $this->controllerNamespace . $module;
		$modelPath['path'] = $this->controllerPath . $module;
		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		if (!empty($dbName)) {
			$modelPath['namespace'] = $this->controllerNamespace . ucfirst($dbName);
			$modelPath['path'] = $this->controllerPath . ucfirst($dbName);
		}

		$modelPath['namespace'] = rtrim($modelPath['namespace'], '\\');
		$modelPath['path'] = rtrim($modelPath['path'], '\\');

		if (!is_dir($modelPath['path'])) {
			mkdir($modelPath['path']);
		}
		return $modelPath;
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @param $path
	 * @return string
	 * 新增
	 */
	public function controllerMethodAdd($fields, $className, $object, $path): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/add", method: "POST")]
	#[Middleware(middleware: [])]
	public function actionAdd(): string
	{
		$model = new ' . $className . '();
		$model->attributes = $this->loadParam();
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(0, $model->toArray());
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @return string
	 * 通用
	 */
	public function controllerMethodLoadParam($fields, $className, $object = NULL): string
	{
		return '
	/**
	 * @return array
	 * @throws Exception
	 */
	#[ArrayShape([])]
	private function loadParam(): array
	{
		return [' . $this->getData($fields) . '
		];
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @param array $path
	 * @return string
	 * 构建更新
	 */
	public function controllerMethodUpdate($fields, $className, $object = NULL, $path = []): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/update", method: "POST")]
	#[Middleware(middleware: [])]
	public function actionUpdate(): string
	{
		$model = ' . $className . '::findOne($this->input->post(\'id\', 0));
		if (empty($model)) {
			return JSON::to(500, SELECT_IS_NULL);
		}
		$model->attributes = $this->loadParam();
		
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(0, $model->toArray());
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @param array $path
	 * @return string
	 * 构建更新
	 */
	public function controllerMethodBatchDelete($fields, $className, $object = NULL, $path = []): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/batch-delete", method: "POST")]
	#[Middleware(middleware: [])]
	public function actionBatchDelete(): string
	{
		$_key = $this->input->array(\'ids\');		
		if (empty($_key)) {
			return JSON::to(500, PARAMS_IS_NULL);
		}
		
		$model = ' . $className . '::find()->in(\'id\', $_key);
		if (!$model->delete()) {
			return JSON::to(500, DB_ERROR_BUSY);
        }
        return JSON::to(0, $_key);
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @param array $path
	 * @return string
	 * 构建详情
	 */
	public function controllerMethodDetail($fields, $className, $managerName, $path = []): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/detail", method: "POST")]
	#[Middleware(middleware: [])]
    public function actionDetail(): string
    {
        $model = ' . $managerName . '::findOne($this->input->get(\'id\'));
        if (empty($model)) {
            return JSON::to(404, SELECT_IS_NULL);
        }
        return JSON::to(0, $model->toArray());
    }';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @param $path
	 * @return string
	 * 构建删除操作
	 */
	public function controllerMethodDelete($fields, $className, $managerName, $path): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);

		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/delete", method: "POST")]
	#[Middleware(middleware: [])]
    public function actionDelete(): string
    {
		$_key = $this->input->int(\'id\', true);
		
		$model = ' . $managerName . '::findOne($_key);
		if (empty($model)) {
			return JSON::to(500, SELECT_IS_NULL);
		}
        if (!$model->delete()) {
			return JSON::to(500, $model->getLastError());
        }
        return JSON::to(0, $model);
    }';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @param array $path
	 * @return string
	 * 构建查询列表
	 */
	public function controllerMethodList($fields, $className, $managerName, $path = []): string
	{
		$_path = str_replace(CONTROLLER_PATH, '', $path['path']);
		$_path = lcfirst(rtrim($_path, '/')) . '/' . lcfirst($className);


		$_path = ltrim($_path,'/');

		return '
    /**
	 * @return string
	 * @throws Exception
	 */
	#[Route(uri: "' . $_path . '/list", method: "POST")]
	#[Middleware(middleware: [])]
    public function actionList(): string
    {        
        //分页处理
	    $count   = $this->input->get(\'count\', -1);
	    $order   = $this->input->get(\'order\', \'id\');
	    if (!empty($order)) {
	        $order .= !$this->input->get(\'isDesc\', 0) ? \' asc\' : \' desc\';
	    } else {
	        $order = \'id desc\';
	    }
	    
	    //列表输出
	    $model = ' . $managerName . '::find()->where($this->input->gets())->orderBy($order);
        if ((int) $count === 1) {
		    $count = $model->count();
	    }
	    if ($count != -100) {
		    $model->limit($this->input->offset() ,$this->input->size());
	    }
	    
		$data = $model->all()->toArray();
		
        return JSON::to(0, $data, $count);
    }
    ';
	}

	private function getData($fields): string
	{
		$html = '';

		$length = $this->getMaxLength($fields);


		foreach ($fields as $key => $val) {
			preg_match('/\((\d+)(,(\d+))*\)/', $val['Type'], $number);
			$type = strtolower(preg_replace('/\(\d+(,\d+)*\)/', '', $val['Type']));

			$first = preg_replace('/\s+\w+/', '', $type);
			if ($val['Field'] == 'id') continue;
			if ($type == 'timestamp') continue;
			$_field = [];
			$_field['required'] = $this->checkIsRequired($val);
			foreach ($this->type as $_key => $value) {
				if (!in_array(strtolower($first), $value)) continue;
				$comment = '//' . $val['Comment'];
				$_field['type'] = $_key;

				if ($type == 'date' || $type == 'datetime' || $type == 'time') {
					$_tps = match ($type) {
						'date' => '$this->input->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d\'))',
						'time' => '$this->input->' . $_key . '(\'' . $val['Field'] . '\', date(\'H:i:s\'))',
						default => '$this->input->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d H:i:s\'))',
					};
					$html .= '
            \'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => ' . str_pad($_tps . ',', 60, ' ', STR_PAD_RIGHT) . $comment;
				} else {
					$tmp = 'null';
					if (isset($number[0])) {
						if (strpos(',', $number[0])) {
							$tmp = '[' . $number[1] . ',' . $number[3] . ']';
							$_field['min'] = $number[1];
							$_field['max'] = $number[3];
						} else {
							$tmp = '[0,' . $number[1] . ']';
							$_field['min'] = 0;
							$_field['max'] = $number[1];
						}
					}
					if ($key == 'string') {
						$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . $tmp . ')';
					} else if ($type == 'int') {
						if ($number[0] == 10) {
							$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', time())';
						} else {
							$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
						}
					} else if ($type == 'float') {
						$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . ($number[3] ?? '2') . ')';
					} else if ($key == 'email') {
						$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					} else if ($key == 'timestamp') {
						$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', time())';
					} else {
						$_tps = '$this->input->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					}
					$html .= '
            \'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => ' . str_pad($_tps . ',', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
			$this->rules[$val['Field']] = $_field;
		}
		return $html;
	}


	/**
	 * @param $fields
	 * @return int
	 */
	private function getMaxLength($fields): int
	{
		$length = 0;
		foreach ($fields as $key => $val) {
			if (mb_strlen($val['Field'] . ' >=') > $length) $length = mb_strlen($val['Field'] . ' >=');
		}
		return $length;
	}

	/**
	 * @param $fields
	 * @return string
	 */
	private function getWhere($fields): string
	{
		$html = '';

		$length = $this->getMaxLength($fields);

		foreach ($fields as $key => $val) {
			preg_match('/\d+/', $val['Type'], $number);

			$type = strtolower(preg_replace('/\(\d+\)/', '', $val['Type']));

			$first = preg_replace('/\s+\w+/', '', $type);

			if ($type == 'timestamp') continue;
			if ($type == 'json') continue;

			foreach ($this->type as $_key => $value) {
				if (!in_array(strtolower($first), $value)) continue;
				$comment = '//' . $val['Comment'];
				if ($type == 'date' || $type == 'datetime' || $type == 'time') {
					$_tps = '$this->input->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' <=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' >=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				} else {

					$_tps = '$this->input->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . '\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
		}
		return $html;
	}
}
