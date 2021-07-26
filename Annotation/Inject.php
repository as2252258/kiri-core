<?php


namespace Annotation;


use Exception;
use HttpServer\Http\Context;
use ReflectionException;
use ReflectionProperty;
use Snowflake\Core\Str;
use Snowflake\Snowflake;

/**
 * Class Inject
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)] class Inject extends Attribute
{


    /**
     * Inject constructor.
     * @param string $className
     * @param array $args
     */
    public function __construct(private string $className, public bool $withContext = false, private array $args = [])
    {
    }


    /**
     * @param mixed $class
     * @param mixed|null $method
     * @return bool
     * @throws ReflectionException
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): bool
    {
        if (!($method = $this->getProperty($class, $method))) {
            return false;
        }
        /** @var ReflectionProperty $class */
        $injectValue = $this->parseInjectValue();
        if ($method->isPrivate() || $method->isProtected()) {
            $this->setter($class, $method, $injectValue);
        } else {
            $class->{$method->getName()} = $injectValue;
        }
        return true;
    }


    /**
     * @param $class
     * @param $method
     * @param $injectValue
     */
    private function setter($class, $method, $injectValue)
    {
        $method = 'set' . ucfirst(Str::convertUnderline($method->getName()));
        if (!method_exists($class, $method)) {
            return;
        }
        $class->$method($injectValue);
    }


    /**
     * @param $class
     * @param $method
     * @return ReflectionProperty|bool
     */
    private function getProperty($class, $method): ReflectionProperty|bool
    {
        if ($method instanceof ReflectionProperty && !$method->isStatic()) {
            return $method;
        }
        if (is_object($class)) $class = $class::class;
        $method = Snowflake::getDi()->getClassProperty($class, $method);
        if (!$method || $method->isStatic()) {
            return false;
        }
        return $method;
    }


    /**
     * @return mixed
     * @throws Exception
     */
    private function parseInjectValue(): mixed
    {
        if ($this->withContext) {
            return Context::getContext($this->className);
        }
        if (class_exists($this->className)) {
            return Snowflake::getDi()->get($this->className, $this->args);
        } else if (Snowflake::app()->has($this->className)) {
            return Snowflake::app()->get($this->className);
        } else {
            return $this->className;
        }
    }

}
