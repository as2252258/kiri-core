<?php


namespace Annotation\Model;


use Annotation\Annotation;
use Attribute;
use Database\ActiveRecord;
use Snowflake\Snowflake;


/**
 * Class Get
 * @package Annotation\Model
 */
#[Attribute(Attribute::TARGET_METHOD)] class Get extends \Annotation\Attribute
{


    /**
     * Get constructor.
     * @param string $name
     */
    public function __construct(public string $name)
    {
    }


    /**
     * @param array $handler
     * @return ActiveRecord
     */
    public function execute(mixed $class, mixed $method = null): bool
    {
        $annotation = Snowflake::getAnnotation();
        $annotation->addGets($class::class, $this->name, $method);
        return true;
    }


}
