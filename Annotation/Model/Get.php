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


	/**
	 * Get constructor.
	 * @param string $name
	 */
	public function __construct(
		public string $name
	)
	{
	}


}
