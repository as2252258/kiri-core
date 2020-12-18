<?php


namespace Annotation\Route;


/**
 * Class Document
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Document
{

	const INTEGER = 'int';
	const STRING = 'string';
	const BOOLEAN = 'bool';
	const FLOAT = 'float';

	const ALIAS = [
		self::INTEGER => '整数',
		self::STRING  => '字符串',
		self::BOOLEAN => '布尔值',
		self::FLOAT   => '浮点',
	];


	public function __construct(
		public array $docs
	)
	{
	}

}
