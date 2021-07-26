<?php


namespace Annotation\Model;


use Attribute;
use Exception;
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
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = null): bool
    {
        $annotation = Snowflake::getAnnotation();
        $annotation->addGets($class::class, $this->name, $method);
        return true;
    }


}
