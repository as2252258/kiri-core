<?php


namespace Gii;

use Database\Db;
use Snowflake\Snowflake;

/**
 * Class GiiModel
 * @package Gii
 */
class GiiModel extends GiiBase
{

	public $classFileName;
	public $tableName;
	public $visible;
	public $res;
	public $fields;

	/**
	 * ModelFile constructor.
	 * @param $classFileName
	 * @param $tableName
	 * @param $visible
	 * @param $res
	 * @param $fields
	 */
	public function __construct($classFileName, $tableName, $visible, $res, $fields)
	{
		$this->classFileName = $classFileName;
		$this->tableName = $tableName;
		$this->visible = $visible;
		$this->res = $res;
		$this->fields = $fields;
	}

	/**
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function generate()
	{
		$class = '';
		$modelPath = $this->getModelPath();
		$managerName = $this->classFileName;

		$namespace = rtrim($modelPath['namespace'], '\\');
		$classFileName = rtrim($modelPath['namespace'], '\\') . '\\' . $managerName;

		$prefix = str_replace('_', '', $this->db->tablePrefix);
		$managerName = str_replace(ucfirst($prefix), '', $managerName);

		if (file_exists($modelPath['path'] . '/' . $managerName . '.php')) {
			try {
				$class = new \ReflectionClass($modelPath['namespace'] . '\\' . $managerName);
			} catch (\Exception $e) {
				var_dump($e->getMessage());
			}
		}


		$html = $this->getUseContent($class, $classFileName);
		if (empty($html)) {
			$html = '<?php
namespace ' . $namespace . ';

use Exception;
use Snowflake\Core\JSON;
use Database\Connection;
use Database\ActiveRecord;';
		}

		$createSql = $this->setCreateSql($this->tableName);

		if (strpos($html, $createSql) === false) {
			$html .= '
' . $this->setCreateSql($this->tableName);
		}

		$html .= '

/**
 * Class ' . $managerName . '
 * @package Inter\mysql
 *' . implode('', $this->visible) . '
 * @sql
 */
class ' . $managerName . ' extends ActiveRecord
{';

		if (!empty($class)) {
			foreach ($class->getConstants() as $key => $val) {
				if (is_numeric($val)) {
					$html .= '
    const ' . $key . ' = ' . $val . ';' . "\n";
				} else {
					$html .= '
    const ' . $key . ' = \'' . $val . '\';' . "\n";
				}
			}

			foreach ($class->getDefaultProperties() as $key => $val) {
				$property = $class->getProperty($key);
				if ($property->class != $class->getName()) continue;
				if (is_array($val)) {
					$val = '[\'' . implode('\', \'', $val) . '\']';
				} else if (!is_numeric($val)) {
					$val = '\'' . $val . '\'';
				}

				if ($property->isProtected()) {
					$debug = 'protected';
				} else if ($property->isPrivate()) {
					$debug = 'private';
				} else {
					$debug = 'public';
				}


				if ($property->isStatic()) {
					$html .= '
    ' . $debug . ' static $' . $key . ' = ' . $val . ';' . "\n";
				} else {
					$html .= '
    ' . $debug . ' $' . $key . ' = ' . $val . ';' . "\n";
				}

			}
		} else {
			$primary = $this->createPrimary($this->fields);
			if (!empty($primary)) {
				$html .= $primary . "\n";
			}
		}

		$html .= $this->createTableName($this->tableName) . "\n";

		$html .= $this->createRules($this->fields);


		$html .= '        
        
    /**
     * @return array
     */
    public function attributes() : array
    {
        return [' . implode('', $this->res) . '
        ];
    }' . "\n";

		$out = ['rules', 'tableName', 'attributes'];
		if (is_object($class)) {
			$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

			$classFileName = str_replace(APP_PATH, '', $class->getFileName());

			$content = [];
			if (!empty($methods)) foreach ($methods as $key => $val) {
				if ($val->class != $class->getName()) continue;
				if (in_array($val->name, $out)) continue;
				$over = "
	" . $val->getDocComment() . "\n";

				$func = $this->getFuncLineContent($class, $classFileName, $val->name) . "\n";

				$content[] = $over . $func;
			}
			if (!empty($content)) {
				$html .= implode($content);
			}
		} else {
			$html .= $this->createDatabaseSource();
			$other = $this->generate_json_function($html, $this->fields);
			if (!empty($other)) {
				$html .= implode($other);
			}
		}

		$html .= '
}';

		$file = rtrim($modelPath['path'], '/') . '/' . $managerName . '.php';
		if (file_exists($file)) {
			unlink($file);
		}

		Snowflake::writeFile($file, $html);
		return $managerName . '.php';
	}


	private function generate_json_function($html, $fields)
	{
		$strings = [];
		foreach ($fields as $field) {
			if ($field['Type'] === 'json') {
				$function = '
	/**
	 * @param $value
	 * @return false|string
	 */
	public function set' . ucfirst($field['Field']) . 'Attribute($value) 
	{
		if ( !is_string($value) ) {
			return JSON::encode($value); 
		}
		return $value;
	}
	';

				$get_function = '
	/**
	 * @param $value
	 * @return mixed
	 */
	public function get' . ucfirst($field['Field']) . 'Attribute($value) 
	{
		$value = stripcslashes($value)
		if ( is_string($value) ) {
			return JSON::decode($value, true); 
		}
		return $value;
	}
	';

				if (strpos($html, 'set' . ucfirst($field['Field']) . 'Attribute') === false) {
					$strings[] = $function;
				}

				if (strpos($html, 'get' . ucfirst($field['Field']) . 'Attribute') === false) {
					$strings[] = $get_function;
				}
			}
		}

		return $strings;
	}


	/**
	 * @param $field
	 * @return string
	 * 创建表名称
	 */
	private function createTableName($field)
	{

		$prefixed = $this->db->tablePrefix;
		if (!empty($prefixed)) {
			$field = str_replace($prefixed, '', $field);
			$field = '{{%' . $field . '}}';
		}

		return '
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return \'' . $field . '\';
    }
    ';
	}

	/**
	 * @param $fields
	 * @return string
	 * 创建效验规则
	 */
	private function createRules($fields)
	{
		$data = [];
		foreach ($fields as $key => $val) {
			if ($val['Extra'] == 'auto_increment') continue;
			$type = preg_replace('/\(.*?\)|\s+\w+/', '', $val['Type']);
			foreach ($this->type as $_key => $_val) {
				if (in_array($type, $_val)) {
					$type = lcfirst(str_replace('get', '', $_key));
					break;
				}
			}
			$data[$type][] = $val;
		}

		$_field_one = '';
		$required = $this->getRequired($fields);
		if (!empty($required)) {
			$_field_one .= $required;
		}
		foreach ($data as $key => $val) {
			$field = '[\'' . implode('\', \'', array_column($val, 'Field')) . '\']';
			if (count($val) == 1) {
				$field = '\'' . current($val)['Field'] . '\'';
			}
			$_field_one .= '
			[' . $field . ', \'' . $key . '\'],';
		}
		foreach ($data as $key => $val) {
			$length = $this->getLength($val);
			if (!empty($length)) {
				$_field_one .= $length . ',';
			}
		}
		$required = $this->getUnique($fields);
		if (!empty($required)) {
			$_field_one .= $required;
		}
		return '
	/**
	 * @return array
	 */
    public function rules(): array
    {
        return [' . $_field_one . '
        ];
    }
        ';
	}

	/**
	 * @param $val
	 * @return string
	 */
	public function getLength($val)
	{
		$data = [];
		foreach ($val as $key => $_val) {
			$preg = preg_match('/(\w+)\((.*?)\)/', $_val['Type'], $results);
			if ($preg && isset($results[2])) {
				$results[] = $_val['Field'];

				$data[$results[2]][] = $results;
			}
		}
		if (empty($data)) return '';
		$string = [];
		foreach ($data as $key => $_val) {
			if (strpos($key, ',') !== false) {
				$key = '[' . $key . ']';
			}
			if (count($_val) == 1) {
				[$typeRule, $type, $rule, $field] = current($_val);
				$_tmp = '
			[\'' . $field . '\', \'' . ($type == 'enum' ? 'enum' : 'maxLength') . '\' => ' . $key . ']';
			} else {
				$_tmp = '
			[[\'' . implode('\', \'', array_column($_val, 3)) . '\'], \'maxLength\' => ' . $key . ']';
			}
			$string[] = $_tmp;
		}
		return implode(',', $string);
	}

	/**
	 * @param $fields
	 * @return string
	 */
	public function getUnique($fields)
	{
		$data = [];
		foreach ($fields as $_key => $_val) {
			if ($_val['Extra'] == 'auto_increment') continue;
			if (strpos($_val['Type'], 'unique') !== FALSE) {
				$data[] = $_val['Field'];
			}
		}
		if (empty($data)) {
			return '';
		}
		return '
			[[\'' . implode('\', \'', $data) . '\'], \'unique\'],';
	}

	/**
	 * @param $val
	 * @return string
	 */
	public function getRequired($val)
	{
		$data = [];
		foreach ($val as $_key => $_val) {
			if ($_val['Extra'] == 'auto_increment') continue;
			if ($_val['Key'] == 'PRI' || $_val['Key'] == 'UNI' || $this->checkIsRequired($_val) === 'true') {
				array_push($data, $_val['Field']);
			}
		}
		if (empty($data)) {
			return '';
		}
		return '
			[[\'' . implode('\', \'', $data) . '\'], \'required\'],';
	}

	/**
	 * 用来生成文档的
	 * 格式
	 * @param $fields
	 * @return null|string
	 * array(
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 *      'field' ,'字段類型' ,'是否必填' ,'字段长度' , '字段解释',
	 * )
	 */
	private function createPrimary($fields)
	{
		$field = $this->getPrimaryKey($fields);
		if (empty($field)) {
			return null;
		}
		return '
	public $primary = \'' . $field . '\';';
	}

	/**
	 * @return string
	 */
	private function createDatabaseSource()
	{
		return '
    /**
	 * @return mixed|Connection
	 * @throws Exception
	 */
    public static function getDb()
    {
	    return static::setDatabaseConnect(\'' . $this->db->id . '\');
    }
';
	}

	/**
	 * @param $table
	 * @return string
	 * @throws \Exception
	 */
	private function setCreateSql($table)
	{
		$text = Db::showCreateSql($table, $this->db)['Create Table'] ?? '';

		$_tmp = [];
		foreach (explode(PHP_EOL, $text) as $val) {
			$_tmp[] = '// ' . $val;
		}

		return implode(PHP_EOL, $_tmp);
	}


}
