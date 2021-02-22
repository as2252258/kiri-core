<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Snowflake\Di;

use HttpServer\Http\HttpHeaders;
use ReflectionClass;
use Snowflake\Abstracts\BaseObject;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

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
        }
        if (!isset($this->_constructs[$class])) {
            return $this->resolve($class, $constrict, $config);
        }
        $definition = $this->_constructs[$class];
        if (is_callable($definition, TRUE)) {
            return call_user_func($definition, $this, $constrict, $config);
        } else if (is_array($definition)) {
            return $this->_singletons[$class] = $this->resolve($class, $definition, $config);
        } else if (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new NotFindClassException($class);
        }
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
        $reflect = $this->resolveDependencies($class, $constrict);
        if (!$reflect->isInstantiable()) {
            throw new NotFindClassException($reflect->getName());
        }

        $dependencies = $this->_constructs[$class] ?? [];
        if (empty($config)) {
            return $reflect->newInstanceArgs($dependencies);
        }
        $this->_param[$class] = $config;

        if (!empty($dependencies) && $reflect->implementsInterface('Snowflake\Abstracts\Configure')) {
            $dependencies[count($dependencies) - 1] = $config;
            return $reflect->newInstanceArgs($dependencies);
        }

        return $this->onAfterInit($reflect->newInstanceArgs($dependencies), $config);
    }

    /**
     * @param $object
     * @param $config
     * @return mixed
     */
    private function onAfterInit($object, $config)
    {
        Snowflake::configure($object, $config);
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
    private function resolveDependencies($class, $constrict = []): ?ReflectionClass
    {
        if (!isset($this->_reflection[$class])) {
            $reflection = new ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                return null;
            }
            $this->_reflection[$class] = $reflection;
        } else {
            $reflection = $this->_reflection[$class];
        }

        if (!is_null($construct = $reflection->getConstructor())) {
            $this->_constructs[$class] = $this->resolveMethodParam($construct, $constrict);
        } else {
            $this->_constructs[$class] = $constrict;
        }

        if ($class == HttpHeaders::class) {
            var_dump($constrict, $this->_constructs[$class]);
        }
        return $reflection;
    }


    /**
     * @param \ReflectionMethod|null $method
     * @return array
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    private function resolveMethodParam(?\ReflectionMethod $method, $default = []): array
    {
        $array = [];
        foreach ($method->getParameters() as $key => $parameter) {
            if (!is_null($default[$key] ?? null)) {
                $array[] = $default[$key];
            } else if ($parameter->isDefaultValueAvailable()) {
                $array[] = $parameter->getDefaultValue();
            } else {
                $type = $parameter->getType();
                if (is_string($type) && class_exists($type)) {
                    $type = Snowflake::createObject($type);
                }
                $array[] = match ($parameter->getType()) {
                    'string' => '',
                    'int', 'float' => 0,
                    '', null, 'object', 'mixed' => NULL,
                    'bool' => false,
                    default => $type
                };
            }
        }
        return $array;
    }


    /**
     * @param $class
     * @return mixed
     * @throws ReflectionException
     */
    public function getReflect($class): ?ReflectionClass
    {
        $reflect = $this->_reflection[$class] ?? null;
        if (!is_null($reflect)) {
            return $reflect;
        }
        return $this->resolveDependencies($class);
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
