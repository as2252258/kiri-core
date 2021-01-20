<?php


namespace Annotation\Model;


use Attribute;
use Database\ActiveRecord;


/**
 * Class Get
 * @package Annotation\Model
 */
#[Attribute(Attribute::TARGET_METHOD)] class Get
{


	public function __construct(
		public string $name
	)
	{
		var_dump($this->name);
	}


}
