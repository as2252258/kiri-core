<?php


namespace Annotation\Model;


use Annotation\Attribute;
use Database\ActiveRecord;
use JetBrains\PhpStorm\Pure;


/**
 * Class Relation
 * @package Annotation\Model
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Relation extends Attribute
{


	/**
	 * Relation constructor.
	 * @param string $name
	 */
	public function __construct(public string $name)
	{
	}


	/**
	 * @param array $handler
	 * @return mixed
	 */
	#[Pure] public function execute(array $handler): mixed
	{
//		/** @var ActiveRecord $activeRecord */
//		[$activeRecord, $method] = $handler;
//
//		$activeRecord->setRelate($this->name, $method);

		return parent::execute($handler);
	}

}
