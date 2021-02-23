<?php


namespace Annotation;


use Exception;
use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\NotFindClassException;
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
	 * @throws ReflectionException|NotFindPropertyException|NotFindClassException
	 */
	public function readControllers(string $path, string $namespace, string $alias = 'root'): static
	{
		return $this->scanDir(glob($path . '*'), $namespace, $alias);
	}


	/**
	 * @param array $paths
	 * @param string $namespace
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException|NotFindPropertyException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function scanDir(array $paths, string $namespace, string $alias): static
	{
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
	 * @throws Exception
	 */
	private function getReflect(string $class, string $alias): array
	{
		try {
			$reflect = $this->reflectClass($class);
			if (empty($reflect) || !$reflect->isInstantiable()) {
				return [];
			}
			$object = $reflect->newInstance();
			$this->resolveMethod($reflect, $class, $alias, $object);
			return $this->targets($reflect);
		} catch (\Throwable $throwable) {
			$this->addError($throwable);
			return [];
		}
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
			$this->resolveAnnotations($method, $alias, $object);
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
					var_dump($name);
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
	 * @throws ReflectionException|NotFindClassException
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

		$name = get_class($object) . '_' . $method->getName();
		if (!isset($this->_annotations[$name])) {
			$this->_annotations[$name] = [];
		}

		foreach ($attributes as $attribute) {
			/** @var IAnnotation $class */
			$class = $this->instance($attribute);
			if ($class === null) {
				continue;
			}
			$this->_annotations[$name][] = $class->execute([$object, $method->getName()]);
		}
		return [];
	}


	/**
	 * @param $class
	 * @param $method
	 * @return mixed
	 */
	#[Pure] public function getAnnotationByMethod($class, $method): array
	{
		if (is_object($class)) {
			$class = get_class($class);
		}
		if (!isset($this->_annotations[$class . '_' . $method])) {
			return [];
		}
		return $this->_annotations[$class . '_' . $method];
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
