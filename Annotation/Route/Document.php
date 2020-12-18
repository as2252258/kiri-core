<?php


namespace Annotation\Route;


/**
 * Class Document
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Document
{

	public function __construct(
		public array $docs
	)
	{
	}

}
