<?php


namespace Annotation\Model;


use Annotation\Attribute;
use Database\ActiveRecord;

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
	 * @param array $handler
	 * @return ActiveRecord
	 */
	public function execute(array $handler): ActiveRecord
	{
		/** @var ActiveRecord $activeRecord */
		[$activeRecord, $method] = $handler;

		return $activeRecord->addSets($this->name, $method);
	}


}
