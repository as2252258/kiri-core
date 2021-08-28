<?php


namespace Annotation\Route;


use Annotation\Attribute;

/**
 * Class Document
 * @package Annotation\Route
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


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return array
	 */
    public static function execute(mixed $params, mixed $class, mixed $method = null): array
	{
		// TODO: Implement execute() method.
		return [$params->request, $params->response];
	}

}
