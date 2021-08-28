<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use Http\HttpFilter;
use ReflectionException;
use Kiri\Exception\ComponentException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;

/**
 * Class Filter
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Filter extends Attribute
{

	/**
	 * Filter constructor.
	 * @param array $rules
	 */
	public function __construct(array $rules)
	{
	}



}
