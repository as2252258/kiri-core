<?php


namespace Annotation\Model;


use Annotation\Annotation;
use Attribute;
use Database\ActiveRecord;


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
	public function __construct(
		public string $name
	)
	{
	}


	/**
	 * @param array $handler
	 * @return ActiveRecord
	 */
	public function execute(array $handler): ActiveRecord
	{
		/** @var ActiveRecord $activeRecord */
		[$activeRecord, $method] = $handler;

		return $activeRecord->addGets($this->name, $method);
	}


}
