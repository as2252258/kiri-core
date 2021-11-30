<?php


namespace Note\Route;


use Note\Attribute;

/**
 * Class Document
 * @package Note\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Document extends Attribute
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


	public function __construct(array $request, array $response)
	{
	}


}
