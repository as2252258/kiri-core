<?php


namespace Annotation;


use Exception;
use ReflectionProperty;
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
    public function __construct(private string $className, private array $args = [])
    {
    }


    /**
     * @param array $handler
     * @return mixed
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): mixed
    {
        $injectValue = $this->parseInjectValue();
        if (!($method instanceof ReflectionProperty)) {
            $method = new ReflectionProperty($class, $method);
        }

        /** @var ReflectionProperty $class */
        if ($class->isPrivate() || $class->isProtected()) {
            $method = 'set' . ucfirst($class->getName());
            if (!method_exists($class, $method)) {
                return false;
            }
            $class->$method($injectValue);
        } else {
            $class->{$method->getName()} = $injectValue;
        }
        return true;
    }


    /**
     * @return mixed
     * @throws Exception
     */
    private function parseInjectValue(): mixed
    {
        if (class_exists($this->className)) {
            $injectValue = Snowflake::createObject($this->className, $this->args);
        } else if (Snowflake::app()->has($this->className)) {
            $injectValue = Snowflake::app()->get($this->className);
        } else {
            $injectValue = $this->className;
        }
//		if (!empty($this->args) && is_object($injectValue)) {
//			Snowflake::configure($injectValue, $this->args);
//		}
        return $injectValue;
    }

}
