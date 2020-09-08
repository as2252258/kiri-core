<?php


namespace Gii;

use Snowflake\Snowflake;

/**
 * Class GiiController
 * @package Gii
 */
class GiiController extends GiiBase
{

	public $className;

	public $fields;

	public function __construct($className, $fields)
	{
		$this->className = $className;
		$this->fields = $fields;
	}


	/**
	 * @return string
	 * @throws \Exception
	 */
	public function generate()
	{
		$path = $this->getControllerPath();
		$modelPath = $this->getModelPath();

		$managerName = $this->className;

		$namespace = rtrim($path['namespace'], '\\');
		$model_namespace = rtrim($modelPath['namespace'], '\\');

		$prefix = str_replace('_', '', $this->db->tablePrefix);
		$managerName = str_replace(ucfirst($prefix), '', $managerName);

		$class = '';
		$controller = $namespace . '\\' . $managerName . 'Controller';
		if (file_exists($path['path'] . '/' . $managerName . 'Controller.php')) {
			$class = new \ReflectionClass($controller);
		}

		$controllerName = $managerName;

		$html = $this->getUseContent($class, $controller);


		if (empty($html)) {


			$html .= "<?php
namespace {$namespace};

use Snowflake;
use Code;
use exception;
use Snowflake\Core\Str;
use Snowflake\Core\JSON;
use Snowflake\Http\Request;
use Snowflake\Http\Response;
use components\ActiveController;
use {$model_namespace}\\{$managerName};
";
		}

		$historyModel = "use {$model_namespace}\\{$managerName};";
		if (strpos($html, $historyModel) === false) {
			$html .= $historyModel;
		}

		$html .= "
		
/**
 * Class {$controllerName}Controller
 *
 * @package controller
 */
class {$controllerName}Controller extends ActiveController
{

";

		$funcNames = [];
		if (is_object($class)) {
			$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
			$funcNames = array_column($methods, 'name');

			$classFileName = str_replace(APP_PATH, '', $class->getFileName());

			if (!empty($methods)) foreach ($methods as $key => $val) {
				if ($val->class != $class->getName()) continue;
				$html .= "
	" . $val->getDocComment() . "\n";
				$html .= $this->getFuncLineContent($class, $classFileName, $val->name) . "\n";
			}
		}
		if (!$this->input->get('--controller-empty', false)) {
			$default = ['actionLoadParam', 'actionAdd', 'actionUpdate', 'actionDetail', 'actionDelete', 'actionBatchDelete', 'actionList'];

			foreach ($default as $key => $val) {
				if (in_array($val, $funcNames)) continue;
				$html .= $this->{'controllerMethod' . str_replace('action', '', $val)}($this->fields, $managerName, $managerName) . "\n";
			}
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
	private function getControllerPath()
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
	 * @return string
	 * 新增
	 */
	public function controllerMethodAdd($fields, $className, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
	public function actionAdd()
	{
		$model = new ' . $className . '();
		$model->attributes = $this->loadParam();
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(Code::SUCCESS, $model->toArray());
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @return string
	 * 通用
	 */
	public function controllerMethodLoadParam($fields, $className, $object = NULL)
	{
		return '
	/**
	 * @return array
	 * @throws Exception
	 */
	private function loadParam()
	{
		return [' . $this->getData($fields) . '
		];
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @return string
	 * 构建更新
	 */
	public function controllerMethodUpdate($fields, $className, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
	public function actionUpdate()
	{
		$model = ' . $className . '::findOne(Input()->post(\'id\', 0));
		if (empty($model)) {
			return JSON::to(500, \'指定数据不存在\');
		}
		$model->attributes = $this->loadParam();
		
		if (!$model->save()) {
			return JSON::to(500, $model->getLastError());
		}
		return JSON::to(Code::SUCCESS, $model->toArray());
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param null $object
	 * @return string
	 * 构建更新
	 */
	public function controllerMethodBatchDelete($fields, $className, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
	public function actionBatchDelete()
	{
		$_key = Input()->array(\'ids\');
		$pass = Input()->string(\'password\', true, 32);		
		if (empty($_key)) {
			return JSON::to(500, \'IDS集合不能为空\');
		}
		
		$user = $this->request->identity;
		if (strcmp(Str::encrypt($pass), $user->password)) {
			return JSON::to(500, \'密码错误\');
		}
		
		$model = ' . $className . '::find()->in(\'id\', $_key);
        if(!$model->delete()){
			return JSON::to(500, \'系统繁忙, 请稍后再试!\');
        }
        return JSON::to(Code::SUCCESS, $model->toArray());
	}';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @return string
	 * 构建详情
	 */
	public function controllerMethodDetail($fields, $className, $managerName)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionDetail()
    {
        $model = ' . $managerName . '::findOne(Input()->get(\'id\'));
        if(empty($model)){
            return JSON::to(404, \'Data Not Exists\');
        }
        return JSON::to(Code::SUCCESS, $model->toArray());
    }';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @return string
	 * 构建删除操作
	 */
	public function controllerMethodDelete($fields, $className, $managerName)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionDelete()
    {
		$_key = Input()->int(\'id\', true);
		$pass = Input()->string(\'password\', true, 32);		
		
		$user = $this->request->identity;
		if (strcmp(Str::encrypt($pass), $user->password)) {
			return JSON::to(500, \'密码错误\');
		}
		
		$model = ' . $managerName . '::findOne($_key);
		if (empty($model)) {
			return JSON::to(500, \'指定数据不存在\');
		}
        if(!$model->delete()){
			return JSON::to(500, $model->getLastError());
        }
        return JSON::to(Code::SUCCESS, $model);
    }';
	}

	/**
	 * @param $fields
	 * @param $className
	 * @param $managerName
	 * @param null $object
	 * @return string
	 * 构建查询列表
	 */
	public function controllerMethodList($fields, $className, $managerName, $object = NULL)
	{
		return '
    /**
	 * @return array
	 * @throws exception
	 */
    public function actionList()
    {
        $pWhere = array();' . $this->getWhere($fields) . '
        
        //分页处理
	    $count   = Input()->get(\'count\', -1);
	    $order   = Input()->get(\'order\', \'id\');
	    if(!empty($order)) {
	        $order .= !Input()->get(\'isDesc\', 0) ? \' asc\' : \' desc\';
	    }else{
	        $order = \'id desc\';
	    }
	    
	    //列表输出
	    $model = ' . $managerName . '::find()->where($pWhere)->orderBy($order);
	    
        if((int) $count === 1){
		    $count = $model->count();
	    }
	    if($count != -100){
		    $model->limit(Input()->offset() ,Input()->size());
	    }
	    
		$data = $model->all()->toArray();
		
        return JSON::to(Code::SUCCESS, $data, $count);
    }
    ';
	}

	private function getData($fields)
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
					switch ($type) {
						case 'date':
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d\'))';
							break;
						case 'time':
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'H:i:s\'))';
							break;
						default:
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', date(\'Y-m-d H:i:s\'))';
					}
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
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . $tmp . ')';
					} else if ($type == 'int') {
						if ($number[0] == 10) {
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', time())';
						} else {
							$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
						}
					} else if ($type == 'float') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ', ' . ($number[3] ?? '2') . ')';
					} else if ($key == 'email') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					} else if ($key == 'timestamp') {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', time())';
					} else {
						$_tps = 'Input()->' . $_key . '(\'' . $val['Field'] . '\', ' . $_field['required'] . ')';
					}
					$html .= '
            \'' . str_pad($val['Field'] . '\'', $length, ' ', STR_PAD_RIGHT) . ' => ' . str_pad($_tps . ',', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
			$this->rules[$val['Field']] = $_field;
		}
		return $html;
	}

	private function getMaxLength($fields)
	{
		$length = 0;
		foreach ($fields as $key => $val) {
			if (mb_strlen($val['Field'] . ' >=') > $length) $length = mb_strlen($val['Field'] . ' >=');
		}
		return $length;
	}

	private function getWhere($fields)
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
					$_tps = 'Input()->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' <=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . ' >=\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				} else {

					$_tps = 'Input()->get(\'' . $val['Field'] . '\', null)';
					$html .= '
        $pWhere[\'' . str_pad($val['Field'] . '\']', $length, ' ', STR_PAD_RIGHT) . ' = ' . str_pad($_tps . ';', 60, ' ', STR_PAD_RIGHT) . $comment;
				}
			}
		}
		return $html;
	}
}
