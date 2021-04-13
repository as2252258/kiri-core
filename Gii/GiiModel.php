<?php
declare(strict_types=1);


namespace Gii;

use Database\Db;
use Exception;
use ReflectionException;
use Snowflake\Snowflake;

/**
 * Class GiiModel
 * @package Gii
 */
class GiiModel extends GiiBase
{

	public ?string $classFileName;
	public ?array $visible;
	public ?array $res;
	public ?array $fields;

	/**
	 * ModelFile constructor.
	 * @param $classFileName
	 * @param $tableName
	 * @param $visible
	 * @param $res
	 * @param $fields
	 */
	public function __construct(string $classFileName, string $tableName, array $visible, array $res, array $fields)
	{
		$this->classFileName = $classFileName;
		$this->tableName = $tableName;
		$this->visible = $visible;
		$this->res = $res;
		$this->fields = $fields;
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function generate(): string
	{
		$class = '';
		$modelPath = $this->getModelPath();
		$managerName = $this->classFileName;

		$namespace = rtrim($modelPath['namespace'], '\\');

		$prefix = str_replace('_', '', $this->db->tablePrefix);
		$managerName = str_replace(ucfirst($prefix), '', $managerName);

		if (file_exists($modelPath['path'] . '/' . $managerName . '.php')) {
			try {
				$className = str_replace('\\\\', '\\', "{$modelPath['namespace']}\\{$managerName}");

				$class = Snowflake::getDi()->getReflect($className);

				$html = '<?php
namespace ' . $namespace . ';

';
				$imports = $this->getImports($modelPath['path'] . '/' . $managerName . '.php', $class);
				if (!empty($imports)) {
					$html .= $imports . PHP_EOL;
				}

				if (!str_contains($imports, 'Annotation\Model\Set')) {
					$html .= 'use Annotation\Model\Set;' . PHP_EOL;
				}
				if (!str_contains($imports, 'Annotation\Model\Get')) {
					$html .= 'use Annotation\Model\Get;' . PHP_EOL;
				}
			} catch (\Throwable $e) {
				logger()->addError($e, 'throwable');
			}
		}


		if (!isset($html) || empty($html)) {
			$html = '<?php
namespace ' . $namespace . ';


use Exception;
use Annotation\Target;
use Snowflake\Core\JSON;
use Database\Connection;
use Annotation\Model\Get;
use Annotation\Model\Set;
use Annotation\Model\Relation;
use Database\ActiveRecord;
' . PHP_EOL;
		}

		$createSql = $this->setCreateSql($this->tableName);

		if (!str_contains($html, $createSql)) {
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
#[Target] class ' . $managerName . ' extends ActiveRecord
{

';

		if (!empty($class)) {
			$html .= $this->getClassProperty($class);
		}

		$primary = $this->createPrimary($this->fields);
		if (!empty($primary)) {
			$html .= $primary . "\n";
		}

		$html .= $this->createTableName($this->tableName) . "\n";

		$html .= $this->createRules($this->fields);

		if (is_object($class)) {
			$html .= $this->getClassMethods($class, ['rules', 'tableName', 'attributes']);
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


	/**
	 * @param $html
	 * @param $fields
	 * @return array
	 */
	private function generate_json_function($html, $fields): array
	{
		$strings = [];
		foreach ($fields as $field) {
			if ($field['Type'] === 'json') {
				$function = '
	/**
	 * @param $value
	 * @return int|bool|string
	 * @throws Exception
    */
	public function set' . ucfirst($field['Field']) . 'Attribute($value): int|bool|string
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
	 * @return array|null|bool
	 */
	#[Get(\'' . $field['Field'] . '\')]
	public function get' . ucfirst($field['Field']) . 'Attribute($value): array|null|bool
	{
		$value = stripcslashes($value);
		if ( is_string($value) ) {
			return JSON::decode($value, true); 
		}
		return $value;
	}
	';

				if (!str_contains($html, 'set' . ucfirst($field['Field']) . 'Attribute')) {
					$strings[] = $function;
				}

				if (!str_contains($html, 'get' . ucfirst($field['Field']) . 'Attribute')) {
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
	private function createTableName($field): string
	{

		$prefixed = $this->db->tablePrefix;
		if (!empty($prefixed)) {
			if (str_starts_with($field, $prefixed)) {
				$field = str_replace($prefixed, '', $field);

				$field = '{{%' . $field . '}}';
			}
		}

		return '
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return \'' . $field . '\';
    }
    ';
	}

	/**
	 * @param $fields
	 * @return string
	 * 创建效验规则
	 */
	private function createRules($fields): string
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
	public function getLength($val): string
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
			if (is_string($key) && str_contains($key, ',')) {
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
	public function getUnique($fields): string
	{
		$data = [];
		foreach ($fields as $_key => $_val) {
			if ($_val['Extra'] == 'auto_increment') continue;
			if (str_contains($_val['Type'], 'unique')) {
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
	public function getRequired($val): string
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
	private function createPrimary($fields): ?string
	{
		$field = $this->getPrimaryKey($fields);
		if (empty($field)) {
			return null;
		}
		return '
	public ?string $primary = \'' . $field . '\';';
	}

	/**
	 * @return string
	 */
	private function createDatabaseSource(): string
	{
		return '
    /**
	 * @return Connection
	 * @throws Exception
	 */
    public static function getDb(): Connection
    {
	    return static::setDatabaseConnect(\'' . $this->db->id . '\');
    }
';
	}

	/**
	 * @param $table
	 * @return string
	 * @throws Exception
	 */
	private function setCreateSql($table): string
	{
		$text = Db::showCreateSql($table, $this->db)['Create Table'] ?? '';

		$_tmp = [];
		foreach (explode(PHP_EOL, $text) as $val) {
			$_tmp[] = '// ' . $val;
		}

		return implode(PHP_EOL, $_tmp);
	}


}
