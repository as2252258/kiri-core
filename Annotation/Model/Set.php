<?php


namespace Annotation\Model;


use Annotation\Attribute;
use Database\ActiveRecord;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Set extends Attribute
{


	/**
	 * Set constructor.
	 * @param string $name
	 */
	public function __construct(public string $name)
	{
	}


    /**
     * @param mixed $class
     * @param mixed|null $method
     * @return bool
     * @throws \Exception
     */
    public function execute(mixed $class, mixed $method = null): bool
	{
        $annotation = Snowflake::getAnnotation();
        $annotation->addSets($class::class, $this->name, $method);
		return true;
	}


}
