<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Snowflake\Di;

use ReflectionClass;
use Snowflake\Abstracts\BaseObject;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;

/**
 * Class Container
 * @package Snowflake\Di
 */
class Container extends BaseObject
{

    /**
     * @var array
     *
     * instance class by className
     */
    private array $_singletons = [];

    /**
     * @var array
     *
     * class new instance construct parameter
     */
    private array $_constructs = [];

    /**
     * @var array
     *
     * implements \ReflectClass
     */
    private array $_reflection = [];

    /**
     * @var array
     *
     * The construct parameter
     */
    private array $_param = [];


    /**
     * @var array
     *
     * The method attributes
     */
    private array $_attributes = [];


    /**
     * @param       $class
     * @param array $constrict
     * @param array $config
     *
     * @return mixed
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    public function get($class, $constrict = [], $config = []): mixed
    {
        if (isset($this->_singletons[$class])) {
            return $this->_singletons[$class];
        } else if (!isset($this->_constructs[$class])) {
            return $this->resolve($class, $constrict, $config);
        }
        $definition = $this->_constructs[$class];
        if (is_callable($definition, TRUE)) {
            return call_user_func($definition, $this, $constrict, $config);
        } else if (is_array($definition)) {
            $object = $this->resolveDefinition($definition, $class, $config, $constrict);
        } else if (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new NotFindClassException($class);
        }
        return $this->_singletons[$class] = $object;
    }

    /**
     * @param $definition
     * @param $class
     * @param $config
     * @param $constrict
     * @return mixed
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    private function resolveDefinition($definition, $class, $config, $constrict): mixed
    {
        if (!isset($definition['class'])) {
            throw new NotFindClassException($class);
        }
        $_className = $definition['class'];
        unset($definition['class']);

        $config = array_merge($definition, $config);
        $definition = $this->mergeParam($class, $constrict);

        if ($_className === $class) {
            $object = $this->resolve($class, $definition, $config);
        } else {
            $object = $this->get($class, $definition, $config);
        }
        return $object;
    }

    /**
     * @param $class
     * @param $constrict
     * @param $config
     *
     * @return object
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    private function resolve($class, $constrict, $config): object
    {
        /**
         * @var ReflectionClass $reflect
         * @var array $dependencies
         */
        list($reflect, $dependencies) = $this->resolveDependencies($class);
        foreach ($constrict as $index => $param) {
            $dependencies[$index] = $param;
        }
        if (!$reflect->isInstantiable()) {
            throw new NotFindClassException($reflect->getName());
        }
        if (empty($config)) {
            return $reflect->newInstanceArgs($dependencies ?? []);
        }
        if (!empty($dependencies) && $reflect->implementsInterface('Snowflake\Abstracts\Configure')) {
            $dependencies[count($dependencies) - 1] = $config;
            return $reflect->newInstanceArgs($dependencies);
        }
        $this->_param[$class] = $config;
        $object = $reflect->newInstanceArgs($dependencies ?? []);
        foreach ($config as $key => $val) {
            $object->{$key} = $val;
        }
        if (method_exists($object, 'afterInit')) {
            call_user_func([$object, 'afterInit']);
        }
        return $object;
    }

    /**
     * @param $class
     *
     * @return array
     * @throws ReflectionException
     */
    private function resolveDependencies($class): array
    {
        $dependencies = [];
        if (isset($this->_reflection[$class])) {
            $reflection = $this->_reflection[$class];
        } else {
            $reflection = new ReflectionClass($class);
            $this->_reflection[$class] = $reflection;
        }
        $constructs = $reflection->getConstructor();
        if (empty($constructs) || count($constructs->getParameters()) < 1) {
            return [$reflection, []];
        }
        foreach ($constructs->getParameters() as $key => $param) {
            if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
                break;
            } else if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                $c = $param->getClass();
                var_dump($c);
                $dependencies[] = $c === NULL ? NULL : $c->getName();
            }
        }
        $this->_constructs[$class] = $dependencies;
        return [$reflection, $dependencies];
    }


    /**
     * @param $class
     * @return mixed
     * @throws ReflectionException
     */
    public function getReflect($class): ReflectionClass
    {
        if (!isset($this->_reflection[$class])) {
            $this->resolveDependencies($class);
        }
        return $this->_reflection[$class];
    }


    /**
     * @param string $class
     * @return array
     * @throws ReflectionException
     */
    public function getAttributes(string $class): array
    {
        $reflection = $this->getReflect($class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $classAttribute = $reflection->getAttributes();

        foreach ($methods as $method) {
            $this->_attributes[$reflection->getName()][$method->getName()] = $method->getAttributes();
        }
        return $this->_attributes[$reflection->getName()];
    }


    /**
     * @param $class
     */
    public function unset($class)
    {
        if (is_array($class) && isset($class['class'])) {
            $class = $class['class'];
        } else if (is_object($class)) {
            $class = get_class($class);
        }
        unset(
            $this->_reflection[$class], $this->_singletons[$class],
            $this->_param[$class], $this->_constructs[$class]
        );
    }

    /**
     * @return $this
     */
    public function flush(): static
    {
        $this->_reflection = [];
        $this->_singletons = [];
        $this->_param = [];
        $this->_constructs = [];
        return $this;
    }

    /**
     * @param $class
     * @param $newParam
     *
     * @return mixed
     */
    private function mergeParam($class, $newParam): array
    {
        if (empty($this->_param[$class])) {
            return $newParam;
        } else if (empty($newParam)) {
            return $this->_param[$class];
        }
        $old = $this->_param[$class];
        foreach ($newParam as $key => $val) {
            $old[$key] = $val;
        }
        return $old;
    }
}
