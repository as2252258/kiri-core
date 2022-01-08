<?php


namespace Kiri\Annotation;


use Exception;
use Kiri\Core\Str;
use Kiri\Kiri;
use ReflectionException;
use ReflectionProperty;

/**
 * Class Inject
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)] class Inject extends Attribute
{


    /**
     * Inject constructor.
     * @param string $value
     * @param array $construct
     */
    public function __construct(public string $value, public array $construct = [])
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
        $injectValue = static::parseInjectValue();
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
        $method = Kiri::getDi()->getClassReflectionProperty($class, $method);
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
        if (!Kiri::app()->has($this->value)) {
            if (!empty($this->construct)) {
                return Kiri::getDi()->create($this->value, $this->construct);
            }
            return Kiri::getDi()->get($this->value);
        } else {
            return Kiri::app()->get($this->value);
        }
    }

}
