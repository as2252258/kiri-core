<?php


namespace Annotation\Model;


use Annotation\Annotation;
use Annotation\IAnnotation;
use Attribute;
use Database\ActiveRecord;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;


/**
 * Class Get
 * @package Annotation\Model
 */
#[Attribute(Attribute::TARGET_METHOD)] class Get implements IAnnotation
{


	/**
	 * Get constructor.
	 * @param string $name
	 */
	public function __construct(
		public string $name
	)
	{
	}


	/**
	 * @param array $handler
	 * @return Annotation
	 * @throws ComponentException
	 */
	public function execute(array $handler): Annotation
	{
		// TODO: Implement execute() method.
		$annotation = Snowflake::app()->getAttributes();
		$annotation->addMethodAttribute($handler, $this->name);

		return $annotation;
	}


}
