<?php


namespace Annotation;


use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\NotFindPropertyException;
use Snowflake\Snowflake;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{

	private array $_annotations = [];


	private array $_classes = [];


	private array $_targets = [];


	private array $_methods = [];


	/**
	 * @param array $handler
	 * @param $name
	 */
	public function addMethodAttribute(array $handler, $name)
	{
		$this->_methods[get_class($handler[0])][$name] = $handler;
	}


	/**
	 * @param string $className
	 * @return array 根据类名获取注解
	 * 根据类名获取注解
	 */
	public function getMethods(string $className): array
	{
		return $this->_methods[$className] ?? [];
	}


	/**
	 * @param string $path
	 * @param string $namespace
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException|NotFindPropertyException
	 */
	public function readControllers(string $path, string $namespace, string $alias = 'root'): static
	{
		$this->debug('scan dir ' . $path . ' ing...');
		return $this->scanDir(glob($path . '*'), $namespace, $alias);
	}


	/**
	 * @param string $alias
	 * @return array
	 */
	public function getAlias(string $alias = 'root'): array
	{
		if (!isset($this->_annotations[$alias])) {
			return [];
		}
		return $this->_annotations[$alias];
	}


	/**
	 * @param array $paths
	 * @param string $namespace
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException|NotFindPropertyException
	 */
	private function scanDir(array $paths, string $namespace, string $alias): static
	{
		if (!isset($this->_annotations[$alias])) {
			$this->_annotations[$alias] = [];
		}
		foreach ($paths as $path) {
			$explode = explode('/', $path);

			$explode_pop = array_pop($explode);
			if (is_file($path)) {
				if (!str_contains($path, '.php')) {
					continue;
				}
				$explode_pop = str_replace('.php', '', $explode_pop);
				$this->getReflect($namespace . '\\' . $explode_pop, $alias);
			} else {
				$this->scanDir(glob($path . '/*'), $namespace . '\\' . $explode_pop, $alias);
			}
		}
		return $this;
	}


	/**
	 * @param string $class
	 * @param string $alias
	 * @return array
	 * @throws ReflectionException
	 * @throws NotFindPropertyException
	 */
	private function getReflect(string $class, string $alias): array
	{
		$reflect = $this->reflectClass($class);
		if (empty($reflect)) {
			return [];
		}

		$constructor = $reflect->getConstructor();
		if (!empty($constructor) && count($constructor->getParameters()) > 0) {
			return [];
		}

		$object = $reflect->newInstance();
		$this->resolveMethod($reflect, $class, $alias, $object);
		return $this->targets($reflect);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $class
	 * @param $alias
	 * @param $object
	 * @throws NotFindPropertyException
	 */
	private function resolveMethod(ReflectionClass $reflect, $class, $alias, $object)
	{
		foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->class != $class) {
				continue;
			}

			$tmp = $this->resolveAnnotations($method, $alias, $object);
			if (empty($tmp)) {
				continue;
			}

			$this->_classes[$reflect->getName()][$method->getName()] = $tmp;
		}
		$this->resolveProperty($reflect, $object);
	}


	/**
	 * @param ReflectionClass $reflectionClass
	 * @param $object
	 * @throws NotFindPropertyException
	 */
	private function resolveProperty(ReflectionClass $reflectionClass, $object)
	{
		$property = $reflectionClass->getProperties();
		foreach ($property as $value) {
			if ($value->isStatic()) continue;
			$attributes = $value->getAttributes();
			if (count($attributes) < 1) {
				continue;
			}
			foreach ($attributes as $attribute) {
				/** @var IAnnotation $annotation */
				$annotation = $this->instance($attribute);
				if (empty($annotation)) {
					continue;
				}
				$annotation = $annotation->execute([$object, $value->getName()]);
				if ($value->isPublic()) {
					$object->{$value->getName()} = $annotation;
				} else {
					$name = 'set' . ucfirst($value->getName());
					if (!method_exists($object, $name)) {
						throw new NotFindPropertyException('set property need method ' . $name);
					}
					$object->$name($annotation);
				}
			}
		}
	}


	/**
	 * @param string $class
	 * @return ReflectionClass|null
	 * @throws ReflectionException
	 */
	private function reflectClass(string $class): ?ReflectionClass
	{
		return Snowflake::getDi()->getReflect($class);
	}


	/**
	 * @param ReflectionMethod $method
	 * @param $alias
	 * @param $object
	 * @return array
	 */
	private function resolveAnnotations(ReflectionMethod $method, $alias, $object): array
	{
		$attributes = $method->getAttributes();
		if (count($attributes) < 1) {
			return [];
		}

		$names = [];
		foreach ($attributes as $attribute) {
			/** @var IAnnotation $class */
			$class = $this->instance($attribute);
			if ($class === null) {
				continue;
			}
			$names[$attribute->getName()] = $class->execute([$object, $method->getName()]);
		}

		$tmp['handler'] = [$object, $method->getName()];
		$tmp['attributes'] = $names;

		$this->_annotations[$alias][] = $tmp;

		return $tmp;
	}


	/**
	 * @param $className
	 * @param string $method
	 * @return array
	 */
	public function getByClass($className, $method = ''): array
	{
		if (!isset($this->_classes[$className])) {
			return [];
		}
		if (empty($method)) {
			return $this->_classes[$className];
		}
		foreach ($this->_classes[$className] as $_method => $class) {
			if ($method == $_method) {
				return [$class];
			}
		}
		return [];
	}


	/**
	 * @param ReflectionClass $reflect
	 * @return array
	 */
	private function targets(ReflectionClass $reflect): array
	{
		$name = $reflect->getName();
		if (!isset($this->_classes[$name])) {
			$this->_classes[$name] = [];
		}
		$attributes = $reflect->getAttributes();
		if (count($attributes) > 0) {
			if (!isset($this->_targets[$name])) {
				$this->_targets[$name] = [];
			}
			foreach ($attributes as $attribute) {
				$class = $this->instance($attribute);
				if ($class === null) {
					continue;
				}
				$this->_targets[$name][] = $class;
			}
		}
		return [];
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @return array|object|null
	 */
	private function instance(ReflectionAttribute $attribute): array|object|null
	{
		if (!class_exists($attribute->getName())) {
			return null;
		}
		return $attribute->newInstance();
	}


}
